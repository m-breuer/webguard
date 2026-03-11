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
            $table->index(['monitoring_id', 'type', 'read', 'created_at'], 'idx_notifications_monitoring_type_read_created_at');
            $table->index(['type', 'read', 'created_at'], 'idx_notifications_type_read_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_notifications', function (Blueprint $table): void {
            $table->dropIndex('idx_notifications_monitoring_type_read_created_at');
            $table->dropIndex('idx_notifications_type_read_created_at');
        });
    }
};
