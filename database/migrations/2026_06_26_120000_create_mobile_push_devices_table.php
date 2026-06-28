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
        Schema::create('mobile_push_devices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('platform', 16);
            $table->string('push_provider', 32)->default('fcm');
            $table->text('push_token');
            $table->char('token_hash', 64);
            $table->string('device_name')->nullable();
            $table->string('app_version', 64)->nullable();
            $table->string('locale', 16)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('notifications_authorized_at')->nullable();
            $table->timestamp('last_registered_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['push_provider', 'token_hash'], 'uniq_mobile_push_provider_token_hash');
            $table->index(['user_id', 'enabled', 'revoked_at'], 'idx_mobile_push_devices_user_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_push_devices');
    }
};
