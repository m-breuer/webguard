type ConfirmDialogDetail = {
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    onConfirm: () => void;
};

type ConfirmableFormLabels = {
    title: string;
    confirm: string;
    cancel: string;
};

const confirmedForms = new WeakSet<HTMLFormElement>();

const trimText = (value: string | null | undefined): string => value?.trim() ?? '';

export function registerConfirmableForms(labels: ConfirmableFormLabels): void {
    document.addEventListener('submit', (event: SubmitEvent): void => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message = trimText(form.dataset.confirmMessage);

        if (message === '' || confirmedForms.has(form)) {
            confirmedForms.delete(form);
            return;
        }

        event.preventDefault();

        const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        const submitterLabel = trimText(submitter?.textContent);

        window.dispatchEvent(new CustomEvent<ConfirmDialogDetail>('app:confirm', {
            detail: {
                title: trimText(form.dataset.confirmTitle) || labels.title,
                message,
                confirmText: trimText(form.dataset.confirmConfirmLabel) || submitterLabel || labels.confirm,
                cancelText: trimText(form.dataset.confirmCancelLabel) || labels.cancel,
                onConfirm: (): void => {
                    confirmedForms.add(form);

                    if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
                        form.requestSubmit(submitter);

                        return;
                    }

                    form.requestSubmit();
                },
            },
        }));
    });
}

export default function confirmDialog() {
    return {
        open: false,
        title: '',
        message: '',
        confirmText: '',
        cancelText: '',
        onConfirm: null as (() => void) | null,

        init(): void {
            window.addEventListener('app:confirm', (event: Event): void => {
                const detail = (event as CustomEvent<ConfirmDialogDetail>).detail;

                this.title = detail.title ?? '';
                this.message = detail.message;
                this.confirmText = detail.confirmText ?? '';
                this.cancelText = detail.cancelText ?? '';
                this.onConfirm = detail.onConfirm;
                this.open = true;
                document.body.classList.add('overflow-y-hidden');
                window.setTimeout((): void => {
                    document.querySelector<HTMLElement>('[data-confirm-cancel-button]')?.focus();
                }, 0);
            });
        },

        close(): void {
            this.open = false;
            this.onConfirm = null;
            document.body.classList.remove('overflow-y-hidden');
        },

        cancel(): void {
            this.close();
        },

        confirm(): void {
            const action = this.onConfirm;
            this.close();
            action?.();
        },
    };
}
