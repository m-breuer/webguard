import { initializeMonitoringTypeFields } from './monitoring-type-fields';

type FormModalLoader = {
    content: string;
    error: string;
    errorMessage: string;
    loading: boolean;
    init(): void;
    load(trigger: HTMLElement): Promise<void>;
    submit(form: HTMLFormElement): Promise<void>;
};

const loaders = new Map<string, FormModalLoader>();
let globalListenerRegistered = false;

const registerGlobalListener = (): void => {
    if (globalListenerRegistered) {
        return;
    }

    document.addEventListener('click', (event: MouseEvent): void => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const trigger = target.closest<HTMLElement>('[data-form-modal-trigger]');
        const modalName = trigger?.dataset.formModalName;
        const loader = modalName ? loaders.get(modalName) : undefined;

        if (!trigger || !loader) {
            return;
        }

        event.preventDefault();
        void loader.load(trigger);
    });

    document.addEventListener('submit', (event: SubmitEvent): void => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const modal = form.closest<HTMLElement>('[data-form-modal]');
        const modalName = modal?.dataset.formModal;
        const loader = modalName ? loaders.get(modalName) : undefined;

        if (!loader || !form.querySelector('input[name="modal_form"]')) {
            return;
        }

        event.preventDefault();
        void loader.submit(form);
    });

    globalListenerRegistered = true;
};

export default (): FormModalLoader => ({
    content: '',
    error: '',
    errorMessage: '',
    loading: false,

    init(): void {
        registerGlobalListener();
        this.errorMessage = (this as any).$el.dataset.formModalError ?? '';

        const modal = (this as any).$el.querySelector('[data-form-modal]') as HTMLElement | null;
        const modalName = modal?.dataset.formModal;
        if (modalName) {
            loaders.set(modalName, this);
        }
    },

    async load(trigger: HTMLElement): Promise<void> {
        const url = trigger.getAttribute('href') ?? trigger.dataset.formModalUrl;
        const modalName = trigger.dataset.formModalName;

        if (!url || !modalName) {
            return;
        }

        const requestUrl = new URL(url, window.location.origin);
        requestUrl.searchParams.set('modal', trigger.dataset.formModalParam ?? '1');
        this.content = '';
        this.error = '';
        this.loading = true;
        window.dispatchEvent(new CustomEvent('open-form-modal', { detail: modalName }));

        try {
            const response = await fetch(requestUrl.toString(), {
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Form modal request failed with status ${response.status}`);
            }

            this.content = await response.text();
            await window.Alpine.nextTick();

            const contentElement = (this as any).$refs.content as HTMLElement | undefined;
            if (contentElement) {
                window.Alpine.initTree(contentElement);
                initializeMonitoringTypeFields(contentElement);
            }
        } catch {
            this.error = this.errorMessage;
        } finally {
            this.loading = false;
        }
    },

    async submit(form: HTMLFormElement): Promise<void> {
        this.error = '';
        const modalName = form.closest<HTMLElement>('[data-form-modal]')?.dataset.formModal;

        try {
            const response = await fetch(form.action, {
                body: new FormData(form),
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                method: form.method,
            });

            if (!response.ok || !response.redirected) {
                throw new Error(`Form modal submission failed with status ${response.status}`);
            }

            window.location.assign(response.url);
        } catch {
            this.error = this.errorMessage;
            window.dispatchEvent(new CustomEvent('form-modal-submission-failed', { detail: modalName }));
        }
    },
});
