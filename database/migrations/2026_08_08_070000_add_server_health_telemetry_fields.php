<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->decimal('server_health_load_threshold_per_cpu', 6, 2)->nullable()->after('server_health_storage_threshold_percent');
            $table->unsignedInteger('server_health_service_response_time_threshold_ms')->nullable()->after('server_health_load_threshold_per_cpu');
            $table->unsignedSmallInteger('server_health_report_interval_minutes')->default(1)->after('server_health_service_response_time_threshold_ms');
            $table->unsignedSmallInteger('server_health_grace_minutes')->default(5)->after('server_health_report_interval_minutes');
        });

        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->uuid('server_health_report_id')->nullable()->after('server_health_metrics');
            $table->timestamp('server_health_sampled_at')->nullable()->after('server_health_report_id');
            $table->unique(['monitoring_id', 'server_health_report_id'], 'monitoring_server_health_report_unique');
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->uuid('server_health_report_id')->nullable()->after('server_health_metrics');
            $table->timestamp('server_health_sampled_at')->nullable()->after('server_health_report_id');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->dropColumn(['server_health_report_id', 'server_health_sampled_at']);
        });

        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->dropUnique('monitoring_server_health_report_unique');
            $table->dropColumn(['server_health_report_id', 'server_health_sampled_at']);
        });

        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropColumn([
                'server_health_load_threshold_per_cpu',
                'server_health_service_response_time_threshold_ms',
                'server_health_report_interval_minutes',
                'server_health_grace_minutes',
            ]);
        });
    }
};
