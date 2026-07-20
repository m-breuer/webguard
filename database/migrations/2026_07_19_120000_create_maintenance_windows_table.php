<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_windows', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('monitoring_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('monitoring_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('recurrence', 20);
            $table->dateTime('repeat_until')->nullable();
            $table->string('timezone', 64);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['monitoring_id', 'enabled', 'starts_at']);
            $table->index(['monitoring_group_id', 'enabled', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_windows');
    }
};
