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
    can_manage: boolean;
};

type RecurringMaintenanceWindow = {
    id: string;
    target: string | null;
    recurrence: 'weekly' | 'monthly';
    duration_minutes: number;
    timezone: string;
    starts_at: string;
    can_manage: boolean;
};

type MaintenanceStats = {
    total: number;
    active: number;
    upcoming: number;
    expired: number;
    none: number;
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
        stats: MaintenanceStats;
        recurring_windows: RecurringMaintenanceWindow[];
        can_manage_maintenance: boolean;
        monitoring_options: MaintenanceOption[];
        monitoring_groups: MaintenanceGroup[];
    };
};

type MaintenancePageLabels = {
    loading: string;
    error: string;
    clearConfirmation: string;
    clearRecurringConfirmation: string;
};

interface MaintenancePageComponent {
    endpoint: string;
    labels: MaintenancePageLabels;
    scope: 'monitoring' | 'group';
    mode: 'one_off' | 'recurring';
    monitoringId: string;
    monitoringGroupId: string;
    maintenanceFrom: string;
    maintenanceUntil: string;
    recurringStartsAt: string;
    recurrence: 'weekly' | 'monthly';
    recurringDurationMinutes: string;
    recurringRepeatUntil: string;
    recurringTimezone: string;
    monitoringOptions: MaintenanceOption[];
    monitoringGroups: MaintenanceGroup[];
    canManageMaintenance: boolean;
    windows: MaintenanceWindow[];
    recurringWindows: RecurringMaintenanceWindow[];
    stats: MaintenanceStats;
    pagination: MaintenancePagination;
    loading: boolean;
    submitting: boolean;
    error: string;
    message: string;
    load(this: MaintenancePageComponent, page?: number): Promise<void>;
    schedule(this: MaintenancePageComponent): Promise<void>;
    clearWindow(this: MaintenancePageComponent, monitoringId: string): Promise<void>;
    clearRecurringWindow(this: MaintenancePageComponent, windowId: string): Promise<void>;
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

const emptyStats = (): MaintenanceStats => ({
    total: 0,
    active: 0,
    upcoming: 0,
    expired: 0,
    none: 0,
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
    mode: 'one_off',
    monitoringId: '',
    monitoringGroupId: '',
    maintenanceFrom: '',
    maintenanceUntil: '',
    recurringStartsAt: '',
    recurrence: 'weekly',
    recurringDurationMinutes: '60',
    recurringRepeatUntil: '',
    recurringTimezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    monitoringOptions: [],
    monitoringGroups: [],
    canManageMaintenance: false,
    windows: [],
    recurringWindows: [],
    stats: emptyStats(),
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
            this.canManageMaintenance = payload.data.can_manage_maintenance;
            this.windows = payload.data.windows.data;
            this.recurringWindows = payload.data.recurring_windows;
            this.stats = payload.data.stats;
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
            mode: this.mode,
            scope: this.scope,
            monitoring_id: this.scope === 'monitoring' ? this.monitoringId : null,
            monitoring_group_id: this.scope === 'group' ? this.monitoringGroupId : null,
            maintenance_from: this.mode === 'one_off' ? this.maintenanceFrom : null,
            maintenance_until: this.mode === 'one_off' ? (this.maintenanceUntil || null) : null,
            recurring_starts_at: this.mode === 'recurring' ? this.recurringStartsAt : null,
            recurrence: this.mode === 'recurring' ? this.recurrence : null,
            recurring_duration_minutes: this.mode === 'recurring' ? this.recurringDurationMinutes : null,
            recurring_repeat_until: this.mode === 'recurring' ? (this.recurringRepeatUntil || null) : null,
            recurring_timezone: this.mode === 'recurring' ? this.recurringTimezone : null,
        };

        try {
            const response = await fetch(this.endpoint, {
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
            this.recurringStartsAt = '';
            this.recurringRepeatUntil = '';
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
            const response = await fetch(this.endpoint, {
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

    async clearRecurringWindow(this: MaintenancePageComponent, windowId: string): Promise<void> {
        if (! window.confirm(this.labels.clearRecurringConfirmation)) {
            return;
        }

        this.submitting = true;
        this.error = '';
        this.message = '';

        try {
            const response = await fetch(this.endpoint, {
                method: 'DELETE',
                headers: requestHeaders(),
                body: JSON.stringify({ maintenance_window_id: windowId }),
            });

            if (!response.ok) {
                this.error = await errorMessage(response, this.labels.error);
                return;
            }

            const result = await response.json() as { message?: string };
            this.message = result.message ?? '';
            await this.load(this.pagination.current_page);
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
