<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instance_callback_idempotencies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('instance_code', 32);
            $table->string('endpoint', 64);
            $table->string('idempotency_key', 100);
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['instance_code', 'endpoint', 'idempotency_key']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instance_callback_idempotencies');
    }
};
