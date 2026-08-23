import type { HandleFetch } from "@sveltejs/kit";

const correlationIdPattern = /^(?:[\da-f]{32}|[\da-f]{8}-(?:[\da-f]{4}-){3}[\da-f]{12})$/i;

export const handleFetch: HandleFetch = async ({ event, fetch, request }) => {
    const requestId = event.request.headers.get("x-request-id");

    if (requestId && correlationIdPattern.test(requestId) && new URL(request.url).origin === event.url.origin) {
        request.headers.set("X-Request-Id", requestId);
    }

    return fetch(request);
};
