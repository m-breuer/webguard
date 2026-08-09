<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_maintenance_operations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key', 100);
            $table->char('fingerprint', 64);
            $table->string('operation', 32);
            $table->foreignUlid('maintenance_window_id')->nullable()->constrained()->nullOnDelete();
            $table->json('result');
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key'], 'mobile_maintenance_operations_user_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_maintenance_operations');
    }
};
