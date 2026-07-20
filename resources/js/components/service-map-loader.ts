interface ServiceMapLoaderComponent {
    loading: boolean;
    loadPage(this: ServiceMapLoaderComponent, url: string): Promise<void>;
    init(this: ServiceMapLoaderComponent): void;
}

const readServices = (element: HTMLElement): unknown[] => {
    try {
        return JSON.parse(element.dataset.services ?? '[]') as unknown[];
    } catch {
        return [];
    }
};

export default (): ServiceMapLoaderComponent => ({
    loading: false,

    async loadPage(this: ServiceMapLoaderComponent, url: string): Promise<void> {
        if (this.loading) return;

        this.loading = true;
        const root = (this as any).$el as HTMLElement;
        const endpoint = new URL(url, window.location.origin);
        endpoint.searchParams.set('service_fragment', '1');

        const response = await fetch(endpoint.toString()).catch(() => null);
        if (!response?.ok) {
            this.loading = false;
            return;
        }

        const responseText = await response.text();
        const parsedDocument = new DOMParser().parseFromString(responseText, 'text/html');
        const nextList = parsedDocument.querySelector<HTMLElement>('#dashboard-service-list');
        const nextPagination = parsedDocument.querySelector<HTMLElement>('#dashboard-service-pagination');
        const currentList = root.querySelector<HTMLElement>('#dashboard-service-list');
        const currentPagination = root.querySelector<HTMLElement>('#dashboard-service-pagination');

        if (!nextList || !currentList) {
            this.loading = false;
            return;
        }

        currentList.replaceWith(nextList);
        if (currentPagination && nextPagination) {
            currentPagination.replaceWith(nextPagination);
        } else if (!currentPagination && nextPagination) {
            root.append(nextPagination);
        } else if (currentPagination && !nextPagination) {
            currentPagination.remove();
        }

        root.dataset.services = JSON.stringify(readServices(nextList));
        window.dispatchEvent(new CustomEvent('signal-room:services-updated', {
            detail: { services: readServices(nextList) },
        }));
        window.Alpine?.initTree(nextList);
        if (nextPagination) window.Alpine?.initTree(nextPagination);
        this.loading = false;
    },

    init(this: ServiceMapLoaderComponent): void {
        const root = (this as any).$el as HTMLElement;
        window.dispatchEvent(new CustomEvent('signal-room:services-updated', {
            detail: { services: readServices(root) },
        }));
    },
});
