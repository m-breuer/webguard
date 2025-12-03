<?php

use App\Enums\NotificationType;
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
        Schema::create('monitoring_notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('monitoring_id')->constrained('monitorings')->onDelete('cascade');
            $table->enum('type', NotificationType::values());
            $table->text('message');
            $table->boolean('read')->default(false);
            $table->boolean('sent')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_notifications');
    }
};
