import { loadGuestAuthContext, setAuthPageHeaders } from "$lib/server/auth";

export async function load({ fetch, setHeaders }) {
    setAuthPageHeaders(setHeaders);
    return loadGuestAuthContext(fetch);
}
