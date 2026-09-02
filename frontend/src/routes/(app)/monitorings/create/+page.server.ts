import { error } from "@sveltejs/kit";
import type { MonitoringFormOptions } from "$lib/api/monitoring";

export async function load({ fetch }) {
    const response = await fetch("/api/monitorings/form-options", {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
    });

    if (!response.ok) {
        error(response.status, "Monitoring options could not be loaded.");
    }

    return { form: (await response.json() as { data: MonitoringFormOptions }).data };
}
