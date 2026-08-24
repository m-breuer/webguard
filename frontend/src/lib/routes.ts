export const appRoutes = {
    dashboard: "/dashboard",
    monitorings: "/monitorings",
    monitoringGroups: "/monitoring-groups",
    statusPages: "/status-pages",
    incidents: "/incidents/analytics",
    maintenance: "/maintenance",
    teams: "/teams",
    notifications: "/notifications",
    profile: "/profile",
    profileNotifications: "/profile/notifications",
    adminDashboard: "/admin",
    adminUsers: "/admin/users",
    adminPackages: "/admin/packages",
    adminInstances: "/admin/server-instances",
    adminApi: "/admin/api",
    adminActivityLogs: "/admin/activity-logs",
} as const;

export function publicStatusRoute(identifier: string): string {
    return `/status/${encodeURIComponent(identifier)}`;
}
