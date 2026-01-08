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
        Schema::table('monitoring_response_results', function (Blueprint $table) {
            $table->index(['monitoring_id', 'created_at'], 'idx_monitoring_responses_monitoring_id_created_at');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->index(['monitoring_id', 'down_at'], 'idx_incidents_monitoring_id_down_at');
        });

        Schema::table('monitorings', function (Blueprint $table) {
            $table->index('user_id', 'idx_monitorings_user_id');
            $table->index('status', 'idx_monitorings_status');
            $table->index('type', 'idx_monitorings_type');
            $table->index('created_at', 'idx_monitorings_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_response_results', function (Blueprint $table) {
            $table->dropIndex('idx_monitoring_responses_monitoring_id_created_at');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex('idx_incidents_monitoring_id_down_at');
        });

        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropIndex('idx_monitorings_user_id');
            $table->dropIndex('idx_monitorings_status');
            $table->dropIndex('idx_monitorings_type');
            $table->dropIndex('idx_monitorings_created_at');
        });
    }
};
