import { loadGuestAuthContext, setAuthPageHeaders } from "$lib/server/auth";

export async function load({ fetch, setHeaders, url }) {
    setAuthPageHeaders(setHeaders);
    return {
        ...(await loadGuestAuthContext(fetch)),
        initialEmail: url.searchParams.get("email") ?? "",
    };
}
