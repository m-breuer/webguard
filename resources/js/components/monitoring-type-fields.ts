const formSelector = '[data-monitoring-type-form]';
const typeControlSelector = '[data-monitoring-type-control]';
const typeFieldsSelector = '[data-monitoring-type-fields]';

const typesFor = (element: HTMLElement): string[] =>
    (element.dataset.monitoringTypeFields ?? '').split(' ').filter(Boolean);

const setDisabled = (container: HTMLElement, disabled: boolean): void => {
    container.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('input, select, textarea').forEach((field) => {
        if (field.dataset.monitoringTypeInitiallyDisabled === undefined) {
            field.dataset.monitoringTypeInitiallyDisabled = field.disabled ? 'true' : 'false';
        }

        field.disabled = disabled || field.dataset.monitoringTypeInitiallyDisabled === 'true';
    });
};

const updateForm = (form: HTMLElement): void => {
    const typeControl = form.querySelector<HTMLSelectElement>(typeControlSelector);
    if (!typeControl) {
        return;
    }

    const type = typeControl.value;

    form.querySelectorAll<HTMLElement>(typeFieldsSelector).forEach((container) => {
        const visible = typesFor(container).includes(type);
        container.hidden = !visible;
        setDisabled(container, !visible);
    });

    const targetContainer = form.querySelector<HTMLElement>('[data-monitoring-target-container]');
    const target = form.querySelector<HTMLInputElement>('[data-monitoring-target-field]');
    const targetIsGenerated = form.dataset.monitoringTargetGeneratedTypes?.split(' ').includes(type) ?? false;

    if (targetContainer && target) {
        targetContainer.hidden = targetIsGenerated;
        target.disabled = targetIsGenerated;
        target.required = !targetIsGenerated;

        const placeholders = JSON.parse(form.dataset.monitoringTargetPlaceholders ?? '{}') as Record<string, string>;
        target.placeholder = placeholders[type] ?? '';

        if (!form.dataset.monitoringExisting) {
            const urlTypes = form.dataset.monitoringUrlTypes?.split(' ') ?? [];
            const clearingTypes = form.dataset.monitoringTargetClearingTypes?.split(' ') ?? [];

            if (urlTypes.includes(type) && (!target.value || !target.value.startsWith('http'))) {
                target.value = 'https://';
            } else if (clearingTypes.includes(type)) {
                target.value = '';
            }
        }
    }
};

const formsWithin = (root: ParentNode): HTMLElement[] => {
    const forms = Array.from(root.querySelectorAll<HTMLElement>(formSelector));

    if (root instanceof HTMLElement && root.matches(formSelector)) {
        forms.unshift(root);
    }

    return forms;
};

let changeListenerRegistered = false;

export const initializeMonitoringTypeFields = (root: ParentNode = document): void => {
    formsWithin(root).forEach(updateForm);

    if (changeListenerRegistered) {
        return;
    }

    document.addEventListener('change', (event: Event): void => {
        const control = event.target;
        if (!(control instanceof HTMLSelectElement) || !control.matches(typeControlSelector)) {
            return;
        }

        const form = control.closest<HTMLElement>(formSelector);
        if (form) {
            updateForm(form);
        }
    });

    changeListenerRegistered = true;
};
