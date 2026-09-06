<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    public function up(): void
    {
        DB::transaction(static function (): void {
            // This intentionally clears the existing notification history; it cannot be restored by rollback.
            DB::table('monitoring_notification_states')->delete();
            DB::table('notification_channel_deliveries')->delete();
            DB::table('monitoring_notifications')->delete();
        });
    }

    public function down(): void
    {
        // Deleted notification history cannot be restored.
    }
};
