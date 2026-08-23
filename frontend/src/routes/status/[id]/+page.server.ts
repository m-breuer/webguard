import { error } from "@sveltejs/kit";
import type { PublicStatusPayload } from "$lib/api/public-status";

export async function load({ fetch, params }) {
    const response = await fetch(`/api/public/status/${encodeURIComponent(params.id)}`, {
        headers: { Accept: "application/json" },
    });

    if (!response.ok) {
        error(response.status, "This public status page is unavailable.");
    }

    return (await response.json() as { data: PublicStatusPayload }).data;
}
