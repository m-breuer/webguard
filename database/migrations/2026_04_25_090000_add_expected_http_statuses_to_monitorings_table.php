<?php

declare(strict_types=1);

use App\Enums\MonitoringType;
use App\Support\HttpStatusCodeRanges;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->string('expected_http_statuses')
                ->nullable()
                ->default(HttpStatusCodeRanges::DEFAULT)
                ->after('http_method');
        });

        DB::table('monitorings')
            ->whereNotIn('type', [MonitoringType::HTTP->value, MonitoringType::KEYWORD->value])
            ->update(['expected_http_statuses' => null]);
    }

    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropColumn('expected_http_statuses');
        });
    }
};
