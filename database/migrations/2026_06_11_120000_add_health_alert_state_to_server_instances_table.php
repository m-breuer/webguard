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
        Schema::table('server_instances', function (Blueprint $table): void {
            $table->string('last_health_alert_status')->nullable()->after('last_seen_at');
            $table->timestamp('last_health_alerted_at')->nullable()->after('last_health_alert_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_instances', function (Blueprint $table): void {
            $table->dropColumn(['last_health_alert_status', 'last_health_alerted_at']);
        });
    }
};
