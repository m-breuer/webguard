import { error, redirect } from "@sveltejs/kit";

interface ConfirmationResponse {
    data: {
        is_public: boolean;
    };
}

export async function load({ fetch, params }) {
    const response = await fetch(`/api/public/status/${encodeURIComponent(params.id)}/subscribers/confirm/${encodeURIComponent(params.token)}`, {
        headers: { Accept: "application/json" },
        method: "POST",
    });

    if (!response.ok) {
        error(response.status, "This subscription confirmation is unavailable.");
    }

    const payload = await response.json() as ConfirmationResponse;

    redirect(303, payload.data.is_public ? `/status/${encodeURIComponent(params.id)}?subscription=confirmed` : "/");
}
