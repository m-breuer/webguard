type FormModalLoader = {
    content: string;
    error: string;
    errorMessage: string;
    loading: boolean;
    init(): void;
    load(trigger: HTMLElement): Promise<void>;
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
            await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()));
            const contentElement = (this as any).$refs.content as HTMLElement | undefined;
            if (contentElement) {
                window.Alpine.initTree(contentElement);
            }
        } catch {
            this.error = this.errorMessage;
        } finally {
            this.loading = false;
        }
    },
});
