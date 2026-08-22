export interface PaginationMeta {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface DashboardService {
    id: string;
    name: string;
    target: string;
    type: string | null;
    group: string;
    status: string;
    open_incident: boolean;
    last_checked_at: string | null;
    response_time_ms: number | null;
}

export interface DashboardResponse {
    data: {
        overall_state: string;
        summary: Record<"total" | "healthy" | "down" | "unknown" | "paused" | "maintenance", number>;
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
            status: "active" | "upcoming";
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
    meta: {
        as_of: string;
        service_pagination: PaginationMeta;
    };
}

export interface MonitoringSummary {
    id: string;
    name: string;
    target: string;
    type: string | null;
    lifecycle_status: "active" | "paused";
    groups: Array<{ id: string; name: string }>;
    latest_check: {
        status: "up" | "down" | "unknown";
        checked_at: string | null;
        response_time_ms: number | null;
    } | null;
    open_incident: boolean;
    maintenance: {
        starts_at: string | null;
        ends_at: string | null;
        has_recurring_window: boolean;
    };
}

export interface MonitoringListResponse {
    data: MonitoringSummary[];
    links: Record<string, string | null>;
    meta: PaginationMeta & { as_of: string };
}
