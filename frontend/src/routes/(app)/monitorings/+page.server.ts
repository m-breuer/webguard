import { error } from "@sveltejs/kit";
import type { MonitoringListResponse } from "$lib/api/monitoring";

const lifecycleStatuses = new Set(["active", "paused"]);

function positiveInteger(value: string | null): number | null {
    const parsed = Number(value);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

export async function load({ fetch, url }) {
    const params = new URLSearchParams();
    const page = positiveInteger(url.searchParams.get("page"));
    const search = url.searchParams.get("search")?.trim() ?? "";
    const lifecycleStatus = url.searchParams.get("lifecycle_status");

    if (page !== null && page > 1) {
        params.set("page", String(page));
    }

    if (search !== "") {
        params.set("search", search.slice(0, 120));
    }

    if (lifecycleStatus !== null && lifecycleStatuses.has(lifecycleStatus)) {
        params.set("lifecycle_status", lifecycleStatus);
    }

    const query = params.size > 0 ? `?${params}` : "";
    const response = await fetch(`/api/v1/internal/ui/monitorings${query}`, {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
    });

    if (!response.ok) {
        error(response.status, "Monitorings could not be loaded.");
    }

    return {
        filters: {
            lifecycleStatus: lifecycleStatus !== null && lifecycleStatuses.has(lifecycleStatus) ? lifecycleStatus : "",
            search,
        },
        monitorings: await response.json() as MonitoringListResponse,
    };
}
