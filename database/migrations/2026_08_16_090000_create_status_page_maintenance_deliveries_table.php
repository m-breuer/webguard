<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('status_page_maintenance_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('status_page_subscription_id')->constrained()->cascadeOnDelete();
            $table->char('fingerprint', 64);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['status_page_subscription_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_page_maintenance_deliveries');
    }
};
