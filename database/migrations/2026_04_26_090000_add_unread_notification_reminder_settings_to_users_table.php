<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('unread_notifications_reminder_enabled')->default(true)->after('monitoring_digest_frequency');
            $table->string('unread_notifications_reminder_frequency', 16)->default('daily')->after('unread_notifications_reminder_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['unread_notifications_reminder_enabled', 'unread_notifications_reminder_frequency']);
        });
    }
};
