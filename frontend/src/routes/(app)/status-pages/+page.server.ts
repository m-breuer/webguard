import { error } from "@sveltejs/kit";
import type { StatusPage, StatusPageMonitoring } from "$lib/api/status-pages";

export async function load({ fetch }) {
    const headers = { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" };
    const [statusPagesResponse, optionsResponse] = await Promise.all([
        fetch("/api/v1/internal/ui/status-pages?per_page=100", { headers }),
        fetch("/api/v1/internal/ui/status-pages/options", { headers }),
    ]);

    if (!statusPagesResponse.ok || !optionsResponse.ok) {
        error(statusPagesResponse.ok ? optionsResponse.status : statusPagesResponse.status, "Status pages could not be loaded.");
    }

    return {
        statusPages: await statusPagesResponse.json() as { data: StatusPage[] },
        options: await optionsResponse.json() as { data: { monitorings: StatusPageMonitoring[] } },
    };
}
