<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitoring_notifications', function (Blueprint $table): void {
            $table->index(
                ['type', 'read', 'created_at', 'id'],
                'idx_notifications_type_read_created_id'
            );

            $table->index(
                ['monitoring_id', 'type', 'read', 'created_at', 'id'],
                'idx_notifications_monitoring_type_read_created_id'
            );
        });

        Schema::table('notification_channel_deliveries', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'created_at', 'id'],
                'idx_notification_channel_deliveries_user_created_id'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_channel_deliveries', function (Blueprint $table): void {
            $table->dropIndex('idx_notification_channel_deliveries_user_created_id');
        });

        Schema::table('monitoring_notifications', function (Blueprint $table): void {
            $table->dropIndex('idx_notifications_monitoring_type_read_created_id');
            $table->dropIndex('idx_notifications_type_read_created_id');
        });
    }
};
