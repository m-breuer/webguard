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
            $table->dropUnique(['date']); // Drop the existing unique index on 'date'
            $table->unique(['monitoring_id', 'date'], 'monitoring_daily_results_monitoring_id_date_unique'); // Add composite unique index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_daily_results', function (Blueprint $table) {
            $table->dropUnique(['monitoring_id', 'date']); // Drop the composite unique index
            $table->unique('date'); // Re-add the unique index on 'date'
        });
    }
};
