import { error } from "@sveltejs/kit";
import type { PublicStatusPayload } from "$lib/api/public-status";

export async function load({ fetch, params, url }) {
    const response = await fetch(`/api/public/status/${encodeURIComponent(params.id)}`, {
        headers: { Accept: "application/json" },
    });

    if (!response.ok) {
        error(response.status, "This public status page is unavailable.");
    }

    const subscription = url.searchParams.get("subscription");

    return {
        ...(await response.json() as { data: PublicStatusPayload }).data,
        subscriptionNotice: subscription === "confirmation-sent"
            ? "Check your inbox to confirm your subscription."
            : subscription === "confirmed"
                ? "Your subscription has been confirmed."
                : subscription === "unsubscribed"
                    ? "You have been unsubscribed."
                    : null,
    };
}
