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
        Schema::create('status_page_subscriptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('status_page_id')->constrained('status_pages')->cascadeOnDelete();
            $table->string('email');
            $table->string('confirmation_token_hash')->nullable();
            $table->string('unsubscribe_token')->unique();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['status_page_id', 'email']);
            $table->index(['status_page_id', 'verified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_page_subscriptions');
    }
};
