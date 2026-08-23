import { error } from "@sveltejs/kit";
import type { AdminActivityLog, Paginated } from "$lib/api/admin";
export async function load({ fetch }) { const response = await fetch("/api/v1/internal/ui/admin/activity-logs", { headers: { Accept: "application/json" } }); if (!response.ok) error(response.status, "Activity logs could not be loaded."); return { logs: await response.json() as { data: Paginated<AdminActivityLog> } }; }
