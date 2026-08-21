import { loadFirstPartySession } from "$lib/server/session";

export async function load({ fetch }) {
    return {
        session: await loadFirstPartySession(fetch),
    };
}
