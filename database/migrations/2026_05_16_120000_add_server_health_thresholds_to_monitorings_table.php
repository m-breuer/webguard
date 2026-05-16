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
            $table->decimal('server_health_cpu_threshold_percent', 5, 2)->default(90)->after('server_health_last_reported_at');
            $table->decimal('server_health_ram_threshold_percent', 5, 2)->default(90)->after('server_health_cpu_threshold_percent');
            $table->decimal('server_health_storage_threshold_percent', 5, 2)->default(90)->after('server_health_ram_threshold_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropColumn([
                'server_health_cpu_threshold_percent',
                'server_health_ram_threshold_percent',
                'server_health_storage_threshold_percent',
            ]);
        });
    }
};
