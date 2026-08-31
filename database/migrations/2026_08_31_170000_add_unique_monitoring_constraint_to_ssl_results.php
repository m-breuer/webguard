<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $duplicateMonitoringIds = DB::table('monitoring_ssl_results')
            ->select('monitoring_id')
            ->groupBy('monitoring_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('monitoring_id')
            ->pluck('monitoring_id');

        foreach ($duplicateMonitoringIds as $monitoringId) {
            $ids = DB::table('monitoring_ssl_results')
                ->where('monitoring_id', $monitoringId)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->pluck('id');

            DB::table('monitoring_ssl_results')
                ->whereIn('id', $ids->slice(1))
                ->delete();
        }

        Schema::table('monitoring_ssl_results', function (Blueprint $table): void {
            $table->unique('monitoring_id');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_ssl_results', function (Blueprint $table): void {
            $table->dropUnique(['monitoring_id']);
        });
    }
};
