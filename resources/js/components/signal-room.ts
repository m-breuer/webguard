type SignalRoomService = {
    id: string;
    name: string;
    target: string;
    status: string;
    statusLabel: string;
    group: string;
    lastCheck: string;
    responseTime: string;
    openIncident: boolean;
    href: string;
};

type SignalRoomConfig = {
    services: SignalRoomService[];
    initialServiceId: string | null;
};

type SignalRoomComponent = {
    services: SignalRoomService[];
    query: string;
    activeFilter: string;
    selectedServiceId: string | null;
    mobileDetailOpen: boolean;
    desktop: boolean;
    resizeHandler: () => void;
    init(): void;
    selectService(serviceId: string): void;
    closeDetail(): void;
    serviceVisible(service: SignalRoomService): boolean;
};

export default (config: SignalRoomConfig): SignalRoomComponent => ({
    services: config.services,
    query: '',
    activeFilter: 'all',
    selectedServiceId: null,
    mobileDetailOpen: false,
    desktop: false,
    resizeHandler: (): void => undefined,

    init(): void {
        const mediaQuery = window.matchMedia('(min-width: 1024px)');

        const updateViewport = (): void => {
            this.desktop = mediaQuery.matches;
            if (this.desktop && this.selectedServiceId === null) {
                this.selectedServiceId = config.initialServiceId;
            }
        };

        this.resizeHandler = updateViewport;
        updateViewport();
        mediaQuery.addEventListener('change', this.resizeHandler);

        const keydownHandler = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                this.closeDetail();
            }
        };

        window.addEventListener('keydown', keydownHandler);
        document.addEventListener('keydown', keydownHandler);

        (this as any).$el.addEventListener('alpine:destroy', () => {
            mediaQuery.removeEventListener('change', this.resizeHandler);
            window.removeEventListener('keydown', keydownHandler);
            document.removeEventListener('keydown', keydownHandler);
        });
    },

    selectService(serviceId: string): void {
        this.selectedServiceId = serviceId;
        this.mobileDetailOpen = true;
    },

    closeDetail(): void {
        this.mobileDetailOpen = false;
        if (!this.desktop) {
            this.selectedServiceId = null;
        }
    },

    serviceVisible(service: SignalRoomService): boolean {
        const searchable = `${service.name} ${service.target} ${service.group}`.toLocaleLowerCase();
        const queryMatches = this.query.trim() === '' || searchable.includes(this.query.trim().toLocaleLowerCase());
        const filterMatches = this.activeFilter === 'all'
            || (this.activeFilter === 'attention' && ['down', 'unknown'].includes(service.status))
            || service.status === this.activeFilter;

        return queryMatches && filterMatches;
    },
});
