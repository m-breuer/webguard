import { error } from "@sveltejs/kit";

export async function load({ fetch }) {
    const response = await fetch("/api/teams", {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
    });

    if (!response.ok) {
        error(response.status, "Teams could not be loaded.");
    }

    return await response.json() as { data: { teams: TeamSummary[] } };
}

interface TeamSummary {
    id: string;
    name: string;
    description: string | null;
    member_count: number;
    monitoring_count: number;
    role: "admin" | "member";
}
