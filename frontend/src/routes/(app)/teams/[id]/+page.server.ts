import { error } from "@sveltejs/kit";

export async function load({ fetch, params }) {
    const response = await fetch(`/api/v1/internal/ui/teams/${params.id}`, {
        headers: { Accept: "application/json" },
    });
    if (!response.ok)
        error(response.status, "Team workspace could not be loaded.");
    return (await response.json()) as { data: TeamWorkspace };
}

export interface TeamWorkspace {
    id: string;
    name: string;
    description: string | null;
    monitoring_count: number;
    can_manage: boolean;
    members: {
        id: string;
        name: string;
        email: string;
        role: "admin" | "member";
    }[];
    invitations: {
        id: string;
        email: string;
        role: "admin" | "member";
        expires_at: string | null;
    }[];
}
