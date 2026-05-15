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
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->string('server_health_token')->nullable()->unique()->after('heartbeat_last_ping_at');
            $table->timestamp('server_health_last_reported_at')->nullable()->after('server_health_token');
        });

        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->json('server_health_metrics')->nullable()->after('response_time');
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->json('server_health_metrics')->nullable()->after('response_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->dropColumn('server_health_metrics');
        });

        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->dropColumn('server_health_metrics');
        });

        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropUnique(['server_health_token']);
            $table->dropColumn([
                'server_health_token',
                'server_health_last_reported_at',
            ]);
        });
    }
};
