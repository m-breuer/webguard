interface IncidentAnalyticsLoaderComponent {
    init(this: IncidentAnalyticsLoaderComponent): Promise<void>;
    showSectionError(this: IncidentAnalyticsLoaderComponent, section: HTMLElement): void;
}

export default (): IncidentAnalyticsLoaderComponent => ({
    async init(this: IncidentAnalyticsLoaderComponent): Promise<void> {
        const root = (this as any).$el as HTMLElement;
        const sections = Array.from(root.querySelectorAll<HTMLElement>('[data-analytics-section]'));

        await Promise.all(sections.map(async (section): Promise<void> => {
            const endpoint = new URL(section.dataset.endpoint ?? window.location.href, window.location.origin);
            endpoint.searchParams.set('async', '1');
            endpoint.searchParams.set('section', section.dataset.analyticsSection ?? '');

            const response = await fetch(endpoint.toString()).catch(() => null);
            if (!response?.ok) {
                this.showSectionError(section);
                return;
            }

            const documentText = await response.text();
            const parsedDocument = new DOMParser().parseFromString(documentText, 'text/html');
            const content = parsedDocument.querySelector<HTMLElement>('[data-analytics-section-content]');
            if (!content) {
                this.showSectionError(section);
                return;
            }

            section.replaceChildren(content);
        }));
    },

    showSectionError(this: IncidentAnalyticsLoaderComponent, section: HTMLElement): void {
        section.querySelector<HTMLElement>('[data-section-loading]')?.setAttribute('hidden', 'hidden');
        section.querySelector<HTMLElement>('[data-section-error]')?.removeAttribute('hidden');
    },
});
