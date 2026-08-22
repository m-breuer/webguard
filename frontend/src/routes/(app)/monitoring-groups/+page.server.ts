import { error } from "@sveltejs/kit";

export async function load({ fetch }) {
    const headers = { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" };
    const [groupsResponse, assignmentsResponse] = await Promise.all([
        fetch("/api/v1/internal/ui/monitoring-groups?per_page=100", { headers }),
        fetch("/api/v1/internal/ui/monitoring-groups/assignment-options?per_page=100", { headers }),
    ]);

    if (!groupsResponse.ok || !assignmentsResponse.ok) {
        error(groupsResponse.ok ? assignmentsResponse.status : groupsResponse.status, "Monitoring groups could not be loaded.");
    }

    return {
        groups: await groupsResponse.json() as { data: MonitoringGroup[] },
        assignments: await assignmentsResponse.json() as { data: MonitoringAssignment[] },
    };
}

export interface MonitoringAssignment { id: string; name: string; target: string; }
export interface MonitoringGroup {
    id: string;
    name: string;
    description: string | null;
    assignable_monitoring_count: number;
    assignments: MonitoringAssignment[];
}
