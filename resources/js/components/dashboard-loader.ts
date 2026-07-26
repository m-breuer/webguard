interface DashboardLoaderComponent {
    init(this: DashboardLoaderComponent): Promise<void>;
    showError(this: DashboardLoaderComponent, root: HTMLElement): void;
}

export default (): DashboardLoaderComponent => ({
    async init(this: DashboardLoaderComponent): Promise<void> {
        const root = (this as any).$el as HTMLElement;
        const endpoint = new URL(root.dataset.endpoint ?? window.location.href, window.location.origin);
        endpoint.searchParams.set('async', '1');

        const response = await fetch(endpoint.toString()).catch(() => null);
        if (!response?.ok) {
            this.showError(root);
            return;
        }

        const documentText = await response.text();
        const parsedDocument = new DOMParser().parseFromString(documentText, 'text/html');
        const content = parsedDocument.querySelector<HTMLElement>('[data-dashboard-content]');

        if (!content) {
            this.showError(root);
            return;
        }

        root.replaceWith(content);
        window.Alpine?.initTree(content);
    },

    showError(this: DashboardLoaderComponent, root: HTMLElement): void {
        root.querySelector<HTMLElement>('[data-dashboard-loading]')?.setAttribute('hidden', 'hidden');
        root.querySelector<HTMLElement>('[data-dashboard-error]')?.removeAttribute('hidden');
    },
});
