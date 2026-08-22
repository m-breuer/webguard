import { error } from "@sveltejs/kit";

export async function load({ fetch }) {
    const headers = { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" };
    const [capabilitiesResponse, oneOffResponse, recurringResponse] = await Promise.all([
        fetch("/api/v1/internal/ui/maintenance/capabilities", { headers }),
        fetch("/api/v1/internal/ui/maintenance/one-off?per_page=100", { headers }),
        fetch("/api/v1/internal/ui/maintenance/recurring?per_page=100", { headers }),
    ]);

    if (!capabilitiesResponse.ok || !oneOffResponse.ok || !recurringResponse.ok) {
        error(capabilitiesResponse.ok ? (oneOffResponse.ok ? recurringResponse.status : oneOffResponse.status) : capabilitiesResponse.status, "Maintenance data could not be loaded.");
    }

    return {
        capabilities: (await capabilitiesResponse.json() as { data: MaintenanceCapabilities }).data,
        oneOff: await oneOffResponse.json() as { data: MaintenanceWindow[] },
        recurring: await recurringResponse.json() as { data: MaintenanceWindow[] },
    };
}

export interface MaintenanceCapabilities {
    can_schedule: boolean;
    manageable_monitorings: Array<{ id: string; name: string; ownership: "private" | "team_admin" }>;
    monitoring_groups: Array<{ id: string; name: string; monitorings_count: number }>;
}

export interface MaintenanceWindow {
    id: string;
    kind: "one_off" | "recurring";
    state: "active" | "upcoming" | "expired" | "disabled";
    enabled: boolean;
    target: { type: "monitoring" | "monitoring_group"; id: string; name: string; };
    schedule: {
        starts_at: string | null;
        ends_at: string | null;
        timezone: string;
        recurrence: "weekly" | "monthly" | null;
        duration_minutes: number | null;
    };
    can_manage: boolean;
}
