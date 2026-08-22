import { error } from "@sveltejs/kit";
import type { MonitoringSummary } from "$lib/api/monitoring";

export async function load({ fetch, params }) {
    const response = await fetch(`/api/v1/internal/ui/monitorings/${encodeURIComponent(params.id)}`, {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
    });

    if (!response.ok) {
        error(response.status, "Monitoring could not be loaded.");
    }

    return { monitoring: (await response.json() as { data: MonitoringSummary }).data };
}
