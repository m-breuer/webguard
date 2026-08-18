const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([type="hidden"]):not([disabled])',
    'textarea:not([disabled])',
    'select:not([disabled])',
    'details',
    '[contenteditable]:not([contenteditable="false"])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

let activeModal: HTMLElement | null = null;
const previousFocus = new WeakMap<HTMLElement, HTMLElement>();
const modalStack: HTMLElement[] = [];
const isolatedElements = new WeakMap<HTMLElement, HTMLElement[]>();

function isVisible(element: HTMLElement): boolean {
    return element.isConnected
        && element.getClientRects().length > 0
        && window.getComputedStyle(element).visibility !== 'hidden';
}

function focusableElements(modal: HTMLElement): HTMLElement[] {
    return [...modal.querySelectorAll<HTMLElement>(focusableSelector)]
        .filter((element): boolean => isVisible(element) && element.tabIndex >= 0);
}

function focusFirstElement(modal: HTMLElement): void {
    const firstElement = focusableElements(modal)[0];

    (firstElement ?? modal).focus();
}

function isolateModal(modal: HTMLElement): void {
    const elements: HTMLElement[] = [];
    let currentElement: HTMLElement = modal;

    while (currentElement.parentElement) {
        const parentElement = currentElement.parentElement;

        [...parentElement.children].forEach((sibling): void => {
            if (sibling instanceof HTMLElement && sibling !== currentElement && ! sibling.inert) {
                sibling.inert = true;
                elements.push(sibling);
            }
        });

        currentElement = parentElement;
    }

    isolatedElements.set(modal, elements);
}

function restoreModalIsolation(modal: HTMLElement): void {
    isolatedElements.get(modal)?.forEach((element): void => {
        element.inert = false;
    });
}

function activateModal(modal: HTMLElement): void {
    if (activeModal === modal) {
        return;
    }

    if (activeModal) {
        restoreModalIsolation(activeModal);
    }

    const focusedElement = document.activeElement;
    if (focusedElement instanceof HTMLElement) {
        previousFocus.set(modal, focusedElement);
    }

    modalStack.push(modal);
    activeModal = modal;
    isolateModal(modal);

    window.requestAnimationFrame((): void => {
        if (activeModal === modal && ! modal.contains(document.activeElement)) {
            focusFirstElement(modal);
        }
    });
}

function deactivateModal(modal: HTMLElement): void {
    const modalIndex = modalStack.lastIndexOf(modal);
    if (modalIndex === -1) {
        return;
    }

    modalStack.splice(modalIndex, 1);
    restoreModalIsolation(modal);

    const nextActiveModal = modalStack.at(-1) ?? null;
    activeModal = nextActiveModal;

    if (nextActiveModal) {
        isolateModal(nextActiveModal);

        if (! nextActiveModal.contains(document.activeElement)) {
            focusFirstElement(nextActiveModal);
        }

        return;
    }

    const focusedElement = previousFocus.get(modal);

    if (focusedElement?.isConnected) {
        focusedElement.focus();
    }
}

document.addEventListener('modal:opened', (event: Event): void => {
    if (event.target instanceof HTMLElement) {
        activateModal(event.target);
    }
});

document.addEventListener('modal:closed', (event: Event): void => {
    if (event.target instanceof HTMLElement) {
        deactivateModal(event.target);
    }
});

document.addEventListener('focusin', (event: FocusEvent): void => {
    if (activeModal && event.target instanceof Node && ! activeModal.contains(event.target)) {
        focusFirstElement(activeModal);
    }
}, true);

document.addEventListener('keydown', (event: KeyboardEvent): void => {
    if (! activeModal) {
        return;
    }

    if (! activeModal.contains(document.activeElement)) {
        event.preventDefault();
        focusFirstElement(activeModal);

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const elements = focusableElements(activeModal);
    if (elements.length === 0) {
        event.preventDefault();
        activeModal.focus();

        return;
    }

    const currentIndex = elements.indexOf(document.activeElement as HTMLElement);
    const nextIndex = event.shiftKey
        ? (currentIndex <= 0 ? elements.length - 1 : currentIndex - 1)
        : (currentIndex + 1) % elements.length;

    event.preventDefault();
    elements[nextIndex]?.focus();
}, true);
