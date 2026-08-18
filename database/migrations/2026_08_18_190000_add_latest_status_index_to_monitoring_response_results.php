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
            $table->index(
                ['monitoring_id', 'id', 'status'],
                'monitoring_responses_latest_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->dropIndex('monitoring_responses_latest_status_idx');
        });
    }
};
