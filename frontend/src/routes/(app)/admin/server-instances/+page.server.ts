import { error } from "@sveltejs/kit";
import type { AdminServerInstance, Paginated } from "$lib/api/admin";
export async function load({ fetch }) { const response = await fetch("/api/v1/internal/ui/admin/server-instances", { headers: { Accept: "application/json" } }); if (!response.ok) error(response.status, "Server instances could not be loaded."); return { instances: await response.json() as { data: Paginated<AdminServerInstance> } }; }
