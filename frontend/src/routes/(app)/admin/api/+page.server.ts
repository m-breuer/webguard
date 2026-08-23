import { error } from "@sveltejs/kit";
import type { AdminApiLog, Paginated } from "$lib/api/admin";
export async function load({ fetch }) { const response = await fetch("/api/v1/internal/ui/admin/api-logs", { headers: { Accept: "application/json" } }); if (!response.ok) error(response.status, "API usage could not be loaded."); return { logs: await response.json() as { data: Paginated<AdminApiLog> } }; }
