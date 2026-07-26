export type InternalUiQueryValue = string | number | boolean | null | undefined;

export type InternalUiQuery = Record<string, InternalUiQueryValue | InternalUiQueryValue[]>;

export type DashboardService = {
    id: string;
    name: string;
    target: string;
    type: string | null;
    group: string;
    status: string;
    open_incident: boolean;
    last_checked_at: string | null;
    response_time_ms: number | null;
};

export type DashboardProjection = {
    overall_state: string;
    summary: Record<'total' | 'healthy' | 'down' | 'unknown' | 'paused' | 'maintenance', number>;
    services: DashboardService[];
    attention: Array<{
        type: string;
        count: number | null;
        monitoring_id: string | null;
        monitoring_name: string | null;
        monitoring_target: string | null;
    }>;
    maintenance: Array<{
        monitoring_id: string;
        monitoring_name: string;
        monitoring_target: string;
        status: string;
        starts_at: string | null;
        ends_at: string | null;
    }>;
    recent_incidents: Array<{
        id: string;
        monitoring_id: string | null;
        monitoring_name: string | null;
        monitoring_target: string | null;
        down_at: string | null;
        up_at: string | null;
        resolved: boolean;
    }>;
    trend: Array<{
        date: string;
        label: string;
        uptime_percentage: number | null;
        has_data: boolean;
    }>;
    failed_delivery_count: number;
    recommended_action: string;
    capabilities: {
        can_create_monitoring: boolean;
        can_manage_maintenance: boolean;
    };
};

export type DashboardResponse = {
    data: DashboardProjection;
    meta: {
        as_of: string;
        service_pagination: PaginationMeta;
    };
};

export type MonitoringProjection = {
    id: string;
    name: string;
    target: string;
    type: string | null;
    lifecycle_status: string;
    groups: Array<{ id: string; name: string }>;
    latest_check: {
        status: string;
        checked_at: string | null;
        response_time_ms: number | null;
    } | null;
    open_incident: boolean;
    maintenance: {
        starts_at: string | null;
        ends_at: string | null;
        has_recurring_window: boolean;
    };
};

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type MonitoringListResponse = {
    data: MonitoringProjection[];
    links: Record<string, string | null>;
    meta: PaginationMeta & { as_of: string };
};

export type MonitoringCardPayload = {
    status?: string;
    since?: string | null;
    heatmap?: unknown[];
};

export type MonitoringCardsResponse = {
    data: Record<string, MonitoringCardPayload>;
    summary: {
        attention: number;
        healthy: number;
        paused: number;
        maintenance: number;
    };
};

type InternalUiRequestOptions = {
    body?: unknown;
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    query?: InternalUiQuery;
    signal?: AbortSignal;
};

export class InternalUiApiError extends Error {
    constructor(
        message: string,
        public readonly status: number | null,
        public readonly errors: Record<string, string[]> = {},
    ) {
        super(message);
        this.name = 'InternalUiApiError';
    }
}

export function getDashboard(
    endpoint: string,
    servicePage: number | null,
    signal?: AbortSignal,
): Promise<DashboardResponse> {
    return request<DashboardResponse>(endpoint, {
        query: servicePage === null ? {} : { service_page: servicePage },
        signal,
    });
}

export function getMonitorings(
    endpoint: string,
    query: InternalUiQuery = {},
    signal?: AbortSignal,
): Promise<MonitoringListResponse> {
    return request<MonitoringListResponse>(endpoint, { query, signal });
}

export function getMonitoring(endpoint: string, signal?: AbortSignal): Promise<{ data: MonitoringProjection }> {
    return request<{ data: MonitoringProjection }>(endpoint, { signal });
}

export function getMonitoringCards(
    endpoint: string,
    ids: string[],
    signal?: AbortSignal,
): Promise<MonitoringCardsResponse> {
    return request<MonitoringCardsResponse>(endpoint, {
        query: { 'ids[]': ids },
        signal,
    });
}

export async function request<T>(endpoint: string, options: InternalUiRequestOptions = {}): Promise<T> {
    const method = options.method ?? 'GET';
    const url = new URL(endpoint, window.location.origin);
    appendQuery(url.searchParams, options.query ?? {});

    const headers = new Headers({ Accept: 'application/json' });
    const init: RequestInit = {
        credentials: 'same-origin',
        headers,
        method,
        signal: options.signal,
    };

    if (options.body !== undefined) {
        headers.set('Content-Type', 'application/json');
        init.body = JSON.stringify(options.body);
    }

    if (!['GET', 'HEAD'].includes(method)) {
        headers.set('X-CSRF-TOKEN', csrfToken());
    }

    let response: Response;
    try {
        response = await fetch(url, init);
    } catch {
        throw new InternalUiApiError('The request could not be completed.', null);
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw errorFromResponse(response.status, payload);
    }

    if (!payload || typeof payload !== 'object') {
        throw new InternalUiApiError('The server returned an invalid JSON response.', response.status);
    }

    return payload as T;
}

function appendQuery(params: URLSearchParams, query: InternalUiQuery): void {
    Object.entries(query).forEach(([key, value]) => {
        const values = Array.isArray(value) ? value : [value];

        values.forEach((item) => {
            if (item !== null && item !== undefined) {
                params.append(key, String(item));
            }
        });
    });
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

function errorFromResponse(status: number, payload: unknown): InternalUiApiError {
    if (!payload || typeof payload !== 'object') {
        return new InternalUiApiError('The request failed.', status);
    }

    const response = payload as { message?: unknown; errors?: unknown };
    const message = typeof response.message === 'string' ? response.message : 'The request failed.';
    const errors = typeof response.errors === 'object' && response.errors !== null
        ? Object.fromEntries(Object.entries(response.errors).filter(([, value]): value is string[] => Array.isArray(value) && value.every((item) => typeof item === 'string')))
        : {};

    return new InternalUiApiError(message, status, errors);
}
