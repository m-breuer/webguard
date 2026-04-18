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
            $table->string('heartbeat_token')->nullable()->unique()->after('preferred_location');
            $table->unsignedInteger('heartbeat_interval_minutes')->nullable()->after('heartbeat_token');
            $table->unsignedInteger('heartbeat_grace_minutes')->nullable()->after('heartbeat_interval_minutes');
            $table->timestamp('heartbeat_last_ping_at')->nullable()->after('heartbeat_grace_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropUnique(['heartbeat_token']);
            $table->dropColumn([
                'heartbeat_token',
                'heartbeat_interval_minutes',
                'heartbeat_grace_minutes',
                'heartbeat_last_ping_at',
            ]);
        });
    }
};
