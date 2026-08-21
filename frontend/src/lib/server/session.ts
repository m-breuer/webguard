import { error, redirect } from "@sveltejs/kit";
import type { FirstPartySession } from "$lib/api/models";

export async function loadFirstPartySession(fetcher: typeof fetch): Promise<FirstPartySession> {
    const response = await fetcher("/api/v1/internal/ui/session", {
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    });

    if (response.status === 401) {
        redirect(303, "/login");
    }

    if (!response.ok) {
        error(response.status, "Your session could not be loaded.");
    }

    const payload = (await response.json()) as { data: FirstPartySession };

    return payload.data;
}
