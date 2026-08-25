import { loadFirstPartySession } from "$lib/server/session";
import { setAuthPageHeaders } from "$lib/server/auth";

export async function load({ fetch, setHeaders }) {
    setAuthPageHeaders(setHeaders);
    return { session: await loadFirstPartySession(fetch) };
}
