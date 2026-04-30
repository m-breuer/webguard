<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->json('notification_channels')->nullable()->after('notification_on_failure');
            $table->unsignedSmallInteger('ssl_expiry_warning_days')->default(7)->after('notification_channels');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('monitoring_digest_enabled')->default(false)->after('notification_channels_hint_seen_at');
            $table->string('monitoring_digest_frequency', 16)->default('weekly')->after('monitoring_digest_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropColumn(['notification_channels', 'ssl_expiry_warning_days']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['monitoring_digest_enabled', 'monitoring_digest_frequency']);
        });
    }
};
