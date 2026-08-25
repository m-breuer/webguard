import { error } from "@sveltejs/kit";
import type { AdminPackage, AdminUser, Paginated } from "$lib/api/admin";
export async function load({ fetch }) { const response = await fetch("/api/v1/internal/ui/admin/users", { headers: { Accept: "application/json" } }); if (!response.ok) error(response.status, "Users could not be loaded."); return { users: await response.json() as { data: Paginated<AdminUser>; options: { packages: AdminPackage[]; roles: string[] } } }; }
