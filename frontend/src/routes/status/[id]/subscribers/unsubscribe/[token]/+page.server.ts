import { fail, redirect } from "@sveltejs/kit";
import type { Actions } from "./$types";

interface UnsubscribeResponse {
    data: {
        is_public: boolean;
    };
}

interface ApiErrorResponse {
    errors?: Record<string, string[]>;
    message?: string;
}

export const actions = {
    default: async ({ fetch, params, request }) => {
        const formData = await request.formData();
        const email = String(formData.get("email") ?? "");
        const response = await fetch(`/api/public/status/${encodeURIComponent(params.id)}/subscribers/unsubscribe/${encodeURIComponent(params.token)}`, {
            body: formData,
            headers: { Accept: "application/json" },
            method: "DELETE",
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({})) as ApiErrorResponse;

            return fail(response.status === 422 ? 422 : 400, {
                email,
                error: payload.errors?.email?.[0] ?? payload.message ?? "The subscription could not be removed.",
            });
        }

        const payload = await response.json() as UnsubscribeResponse;

        redirect(303, payload.data.is_public ? `/status/${encodeURIComponent(params.id)}?subscription=unsubscribed` : "/");
    },
} satisfies Actions;
