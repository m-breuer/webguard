<?php

declare(strict_types=1);

use App\Enums\NotificationDeliveryStatus;
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
        Schema::create('notification_channel_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('monitoring_notification_id')->nullable()->constrained('monitoring_notifications')->nullOnDelete();
            $table->string('channel', 32);
            $table->string('event_type', 64);
            $table->enum('status', array_column(NotificationDeliveryStatus::cases(), 'value'));
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'channel', 'event_type'], 'idx_notification_channel_deliveries_user_channel_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_channel_deliveries');
    }
};
