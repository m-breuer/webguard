<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->string('location_code')->nullable()->after('monitoring_id');
            $table->index(['monitoring_id', 'location_code', 'created_at'], 'monitoring_responses_location_latest_idx');
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->string('location_code')->nullable()->after('monitoring_id');
        });

        Schema::table('incidents', function (Blueprint $table): void {
            $table->string('consensus_status')->nullable()->after('monitoring_id');
            $table->json('affected_locations')->nullable()->after('consensus_status');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropColumn(['consensus_status', 'affected_locations']);
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->dropColumn('location_code');
        });

        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->dropIndex('monitoring_responses_location_latest_idx');
            $table->dropColumn('location_code');
        });
    }
};
