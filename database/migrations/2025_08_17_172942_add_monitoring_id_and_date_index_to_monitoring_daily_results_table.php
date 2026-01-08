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
        Schema::table('monitoring_daily_results', function (Blueprint $table) {
            $table->index(['monitoring_id', 'date'], 'idx_monitoring_daily_results_monitoring_id_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_daily_results', function (Blueprint $table) {
            $table->dropIndex('idx_monitoring_daily_results_monitoring_id_date');
        });
    }
};
