import { error } from "@sveltejs/kit";
import type { AdminPackage, Paginated } from "$lib/api/admin";
export async function load({ fetch }) { const response = await fetch("/api/v1/internal/ui/admin/packages", { headers: { Accept: "application/json" } }); if (!response.ok) error(response.status, "Packages could not be loaded."); return { packages: await response.json() as { data: Paginated<AdminPackage> } }; }
