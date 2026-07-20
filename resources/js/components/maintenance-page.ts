type MaintenancePagination = {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
};

type MaintenanceOption = {
    id: string;
    name: string;
};

type MaintenanceGroup = MaintenanceOption & {
    monitorings_count: number;
};

type MaintenanceWindow = {
    id: string;
    name: string;
    target: string;
    groups: MaintenanceOption[];
    status: 'active' | 'upcoming' | 'expired' | 'none';
    maintenance_from: string | null;
    maintenance_until: string | null;
};

type MaintenanceApiResponse = {
    data: {
        windows: {
            data: MaintenanceWindow[];
            current_page: number;
            last_page: number;
            from: number | null;
            to: number | null;
            total: number;
            per_page: number;
        };
        monitoring_options: MaintenanceOption[];
        monitoring_groups: MaintenanceGroup[];
    };
};

type MaintenancePageLabels = {
    loading: string;
    error: string;
    clearConfirmation: string;
};

interface MaintenancePageComponent {
    endpoint: string;
    labels: MaintenancePageLabels;
    scope: 'monitoring' | 'group';
    monitoringId: string;
    monitoringGroupId: string;
    maintenanceFrom: string;
    maintenanceUntil: string;
    monitoringOptions: MaintenanceOption[];
    monitoringGroups: MaintenanceGroup[];
    windows: MaintenanceWindow[];
    pagination: MaintenancePagination;
    loading: boolean;
    submitting: boolean;
    error: string;
    message: string;
    load(this: MaintenancePageComponent, page?: number): Promise<void>;
    schedule(this: MaintenancePageComponent): Promise<void>;
    clearWindow(this: MaintenancePageComponent, monitoringId: string): Promise<void>;
    statusClasses(this: MaintenancePageComponent, status: MaintenanceWindow['status']): string;
}

const emptyPagination = (): MaintenancePagination => ({
    current_page: 1,
    last_page: 1,
    from: null,
    to: null,
    total: 0,
    per_page: 50,
});

const csrfToken = (): string => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

const requestHeaders = (): HeadersInit => ({
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrfToken(),
});

const errorMessage = async (response: Response, fallback: string): Promise<string> => {
    try {
        const payload = await response.json() as { message?: string; errors?: Record<string, string[]> };
        const validationMessage = Object.values(payload.errors ?? {})[0]?.[0];

        return validationMessage ?? payload.message ?? fallback;
    } catch {
        return fallback;
    }
};

export default (endpoint: string, labels: MaintenancePageLabels): MaintenancePageComponent => ({
    endpoint,
    labels,
    scope: 'monitoring',
    monitoringId: '',
    monitoringGroupId: '',
    maintenanceFrom: '',
    maintenanceUntil: '',
    monitoringOptions: [],
    monitoringGroups: [],
    windows: [],
    pagination: emptyPagination(),
    loading: true,
    submitting: false,
    error: '',
    message: '',

    async load(this: MaintenancePageComponent, page = 1): Promise<void> {
        this.loading = true;
        this.error = '';

        const url = new URL(this.endpoint, window.location.origin);
        url.searchParams.set('page', String(page));
        url.searchParams.set('per_page', '50');

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(this.labels.error);
            }

            const payload = await response.json() as MaintenanceApiResponse;
            this.monitoringOptions = payload.data.monitoring_options;
            this.monitoringGroups = payload.data.monitoring_groups;
            this.windows = payload.data.windows.data;
            this.pagination = {
                current_page: payload.data.windows.current_page,
                last_page: payload.data.windows.last_page,
                from: payload.data.windows.from,
                to: payload.data.windows.to,
                total: payload.data.windows.total,
                per_page: payload.data.windows.per_page,
            };
        } catch {
            this.error = this.labels.error;
        } finally {
            this.loading = false;
        }
    },

    async schedule(this: MaintenancePageComponent): Promise<void> {
        this.submitting = true;
        this.error = '';
        this.message = '';

        const payload: Record<string, string | null> = {
            scope: this.scope,
            monitoring_id: this.scope === 'monitoring' ? this.monitoringId : null,
            monitoring_group_id: this.scope === 'group' ? this.monitoringGroupId : null,
            maintenance_from: this.maintenanceFrom,
            maintenance_until: this.maintenanceUntil || null,
        };

        try {
            const response = await fetch('/api/maintenance', {
                method: 'POST',
                headers: requestHeaders(),
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                this.error = await errorMessage(response, this.labels.error);
                return;
            }

            const result = await response.json() as { message?: string };
            this.message = result.message ?? '';
            this.monitoringId = '';
            this.monitoringGroupId = '';
            this.maintenanceFrom = '';
            this.maintenanceUntil = '';
            await this.load(this.pagination.current_page);
        } catch {
            this.error = this.labels.error;
        } finally {
            this.submitting = false;
        }
    },

    async clearWindow(this: MaintenancePageComponent, monitoringId: string): Promise<void> {
        if (! window.confirm(this.labels.clearConfirmation)) {
            return;
        }

        this.submitting = true;
        this.error = '';
        this.message = '';

        try {
            const response = await fetch('/api/maintenance', {
                method: 'DELETE',
                headers: requestHeaders(),
                body: JSON.stringify({ monitoring_id: monitoringId }),
            });

            if (!response.ok) {
                this.error = await errorMessage(response, this.labels.error);
                return;
            }

            const result = await response.json() as { message?: string };
            this.message = result.message ?? '';
            const nextPage = this.windows.length === 1 && this.pagination.current_page > 1
                ? this.pagination.current_page - 1
                : this.pagination.current_page;
            await this.load(nextPage);
        } catch {
            this.error = this.labels.error;
        } finally {
            this.submitting = false;
        }
    },

    statusClasses(this: MaintenancePageComponent, status: MaintenanceWindow['status']): string {
        return {
            active: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            upcoming: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
            expired: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
            none: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
        }[status];
    },
});
