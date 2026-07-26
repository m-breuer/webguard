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
        service_pagination: {
            current_page: number;
            last_page: number;
            total: number;
            from: number | null;
            to: number | null;
        };
    };
};

export class InternalUiApiError extends Error {}

export async function getDashboard(
    endpoint: string,
    servicePage: number | null,
    signal?: AbortSignal,
): Promise<DashboardResponse> {
    const url = new URL(endpoint, window.location.origin);

    if (servicePage !== null) {
        url.searchParams.set('service_page', String(servicePage));
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
        },
        signal,
    }).catch(() => null);

    if (!response?.ok) {
        throw new InternalUiApiError('Dashboard request failed.');
    }

    const payload: unknown = await response.json().catch(() => null);
    if (!isDashboardResponse(payload)) {
        throw new InternalUiApiError('Dashboard response is invalid.');
    }

    return payload;
}

function isDashboardResponse(payload: unknown): payload is DashboardResponse {
    if (!payload || typeof payload !== 'object') {
        return false;
    }

    const response = payload as Partial<DashboardResponse>;

    return response.data !== undefined
        && response.meta !== undefined
        && typeof response.data === 'object'
        && typeof response.meta === 'object';
}
