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
            $table->unsignedSmallInteger('check_interval_seconds')->default(300)->after('response_time');
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->unsignedSmallInteger('check_interval_seconds')->default(300)->after('response_time');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->dropColumn('check_interval_seconds');
        });

        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->dropColumn('check_interval_seconds');
        });
    }
};
