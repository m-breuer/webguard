import { error } from "@sveltejs/kit";
import type { MonitoringDetailData, MonitoringSummary } from "$lib/api/monitoring";

export async function load({ fetch, params }) {
    const monitoringId = encodeURIComponent(params.id);
    const [response, detailResponse] = await Promise.all([
        fetch(`/api/v1/internal/ui/monitorings/${monitoringId}`, {
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        }),
        fetch(`/api/v1/internal/ui/monitorings/${monitoringId}/detail-data`, {
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        }),
    ]);

    if (!response.ok || !detailResponse.ok) {
        error(response.status, "Monitoring could not be loaded.");
    }

    return {
        monitoring: (await response.json() as { data: MonitoringSummary }).data,
        detail: (await detailResponse.json() as { data: MonitoringDetailData }).data,
    };
}
