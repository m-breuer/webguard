import { error } from "@sveltejs/kit";
import type { ApiEnvelope } from "$lib/api/models";

export interface NotificationChannelConfig {
    enabled: boolean;
    webhook_url?: string;
    bot_token?: string;
    chat_id?: string;
    url?: string;
}

export interface NotificationSettings {
    notification_channels: Record<string, NotificationChannelConfig>;
    monitoring_digest_enabled: boolean;
    monitoring_digest_frequency: "daily" | "weekly" | "monthly";
    unread_notifications_reminder_enabled: boolean;
    unread_notifications_reminder_frequency: "daily" | "weekly" | "monthly";
}

export async function load({ fetch }) {
    const response = await fetch(
        "/api/v1/internal/ui/profile/notification-settings",
        {
            headers: { Accept: "application/json" },
        },
    );

    if (!response.ok) {
        error(response.status, "Notification settings could not be loaded.");
    }

    return {
        settings: ((await response.json()) as ApiEnvelope<NotificationSettings>)
            .data,
    };
}
