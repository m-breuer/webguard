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
            $table->unsignedSmallInteger('http_status_code')->nullable()->after('status');
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->unsignedSmallInteger('http_status_code')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_response_results', function (Blueprint $table): void {
            $table->dropColumn('http_status_code');
        });

        Schema::table('monitoring_response_archived', function (Blueprint $table): void {
            $table->dropColumn('http_status_code');
        });
    }
};

