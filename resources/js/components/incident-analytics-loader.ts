interface IncidentAnalyticsLoaderComponent {
    endpoint: string;
    errorMessage: string;
    loading: boolean;
    error: string;
    init(this: IncidentAnalyticsLoaderComponent): Promise<void>;
}

export default (): IncidentAnalyticsLoaderComponent => ({
    endpoint: '',
    errorMessage: '',
    loading: true,
    error: '',

    async init(this: IncidentAnalyticsLoaderComponent): Promise<void> {
        const root = (this as any).$el as HTMLElement;
        this.endpoint = root.dataset.endpoint ?? window.location.href;
        this.errorMessage = root.dataset.error ?? 'Unable to load analytics.';

        const endpoint = new URL(this.endpoint, window.location.origin);
        endpoint.searchParams.set('async', '1');

        const response = await fetch(endpoint.toString()).catch(() => null);
        if (!response?.ok) {
            this.loading = false;
            this.error = this.errorMessage;
            return;
        }

        const documentText = await response.text();
        const parsedDocument = new DOMParser().parseFromString(documentText, 'text/html');
        const content = parsedDocument.querySelector<HTMLElement>('#incident-analytics-page-content');
        if (!content) {
            this.loading = false;
            this.error = this.errorMessage;
            return;
        }

        root.replaceWith(content);
    },
});
