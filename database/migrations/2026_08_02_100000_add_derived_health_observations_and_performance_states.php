<?php

declare(strict_types=1);

use App\Enums\MonitoringPerformanceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->unsignedInteger('response_time_threshold_ms')->nullable()->after('failure_confirmation_threshold');
            $table->unsignedTinyInteger('response_time_confirmation_threshold')->nullable()->after('response_time_threshold_ms');
        });

        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->json('vital_values')->nullable()->after('server_health_metrics');
            $table->string('status')->nullable()->default(null)->change();
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->json('vital_values')->nullable()->after('server_health_metrics');
        });

        Schema::create('monitoring_performance_states', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('monitoring_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default(MonitoringPerformanceStatus::NORMAL->value);
            $table->unsignedTinyInteger('consecutive_breaches')->default(0);
            $table->timestamp('degraded_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_performance_states');

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->dropColumn('vital_values');
        });

        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->dropColumn('vital_values');
            $table->string('status')->default('unknown')->nullable(false)->change();
        });

        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropColumn(['response_time_threshold_ms', 'response_time_confirmation_threshold']);
        });
    }
};
