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
    can_manage?: boolean;
    initial_results_wait_minutes?: number | null;
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

export interface MonitoringFormConfiguration {
    id: string;
    name: string;
    type: MonitoringType;
    target: string;
    status: "active" | "paused";
    port: number | null;
    keyword: string | null;
    dns_record_type: string | null;
    dns_expected_values: string[] | null;
    timeout: number | null;
    http_method: "get" | "post" | "put" | "patch" | "delete" | null;
    expected_http_statuses: string | null;
    preferred_locations: string[];
    group_ids: string[];
    can_assign_groups: boolean;
    notification_on_failure: boolean;
    notification_channels: string[] | null;
    failure_confirmation_threshold: number | null;
    ssl_expiry_warning_days: number | null;
    heartbeat_interval_minutes: number | null;
    heartbeat_grace_minutes: number | null;
    server_health_cpu_threshold_percent: number | null;
    server_health_ram_threshold_percent: number | null;
    server_health_storage_threshold_percent: number | null;
    server_health_report_interval_minutes: number | null;
    server_health_grace_minutes: number | null;
}

export type MonitoringType = "http" | "ping" | "keyword" | "port" | "heartbeat" | "server_health" | "domain_expiration" | "dns_record";

export interface MonitoringFormOptions {
    types: MonitoringType[];
    locations: string[];
    groups: Array<{ id: string; name: string }>;
    teams: Array<{ id: string; name: string }>;
    notification_channels: string[];
    monitoring: MonitoringFormConfiguration | null;
}

export interface MonitoringMutationResult {
    id: string;
    name: string;
    type: MonitoringType;
}
