<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('incident_follow_ups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignUlid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->date('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('external_url')->nullable();
            $table->timestamps();

            $table->index(['incident_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_follow_ups');
    }
};
