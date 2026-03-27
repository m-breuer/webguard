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
        Schema::table('monitoring_daily_results', function (Blueprint $table): void {
            $table->integer('unknown_total')->default(0);
            $table->float('unknown_percentage')->default(0);
            $table->integer('unknown_minutes')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_daily_results', function (Blueprint $table): void {
            $table->dropColumn(['unknown_total', 'unknown_percentage', 'unknown_minutes']);
        });
    }
};
