<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_notifications', function (Blueprint $table): void {
            $table->string('expiry_notification_key', 64)->nullable()->after('message');
            $table->unique('expiry_notification_key', 'monitoring_notifications_expiry_notification_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_notifications', function (Blueprint $table): void {
            $table->dropUnique('monitoring_notifications_expiry_notification_key_unique');
            $table->dropColumn('expiry_notification_key');
        });
    }
};
