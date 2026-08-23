import { error, fail } from "@sveltejs/kit";
import type { PublicStatusPayload } from "$lib/api/public-status";
import type { Actions } from "./$types";

interface SubscriptionResponse {
    data: {
        message: string;
    };
}

interface ApiErrorResponse {
    errors?: Record<string, string[]>;
    message?: string;
}

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

export const actions = {
    default: async ({ fetch, params, request }) => {
        const formData = await request.formData();
        const email = String(formData.get("email") ?? "");
        const response = await fetch(`/api/public/status/${encodeURIComponent(params.id)}/subscribers`, {
            body: formData,
            headers: { Accept: "application/json" },
            method: "POST",
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({})) as ApiErrorResponse;

            return fail(response.status === 422 ? 422 : 400, {
                email,
                error: payload.errors?.email?.[0] ?? payload.message ?? "The subscription could not be started.",
            });
        }

        const payload = await response.json() as SubscriptionResponse;

        return {
            email,
            message: payload.data.message,
        };
    },
} satisfies Actions;
