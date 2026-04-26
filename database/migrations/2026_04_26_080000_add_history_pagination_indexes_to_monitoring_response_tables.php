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
        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->index(
                ['monitoring_id', 'created_at', 'id'],
                'idx_monitoring_response_results_monitoring_created_id'
            );
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->index(
                ['monitoring_id', 'created_at', 'id'],
                'idx_monitoring_response_archived_monitoring_created_id'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->dropIndex('idx_monitoring_response_results_monitoring_created_id');
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->dropIndex('idx_monitoring_response_archived_monitoring_created_id');
        });
    }
};
