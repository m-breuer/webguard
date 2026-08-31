export interface Pagination { current_page: number; last_page: number; total: number; }
export interface Paginated<T> { items: T[]; pagination: Pagination; }

export interface AdminUser { id: string; name: string; email: string; role: "admin" | "regular" | "demo"; package_id: string | null; package_limit: number | null; email_verified_at: string | null; created_at: string | null; }
export interface AdminPackage { id: string; monitoring_limit: number; price: number; is_selectable: boolean; created_at: string | null; }
export interface AdminServerInstance { id: string; code: string; display_name: string; country_code: string; region: string | null; ip_address: string; is_active: boolean; health: string; last_seen_at: string | null; }
export interface AdminApiLog { id: string; route: string | null; created_at: string | null; user_email: string | null; }
export interface AdminApiLogUser { id: string; email: string; }
export interface AdminActivityLog { id: string; description: string; log_name: string | null; event: string | null; subject_id: string | null; actor: string | null; changes: Record<string, unknown>; created_at: string | null; }
export interface AdminDashboard { users: number; packages: number; server_instances: number; active_server_instances: number; api_requests_last_24_hours: number; audit_events_last_24_hours: number; }
