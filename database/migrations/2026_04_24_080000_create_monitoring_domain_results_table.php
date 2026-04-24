<?php

declare(strict_types=1);

use App\Models\Monitoring;
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
        Schema::create('monitoring_domain_results', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignIdFor(Monitoring::class, 'monitoring_id')->constrained()->cascadeOnDelete();

            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_valid')->default(false);
            $table->string('registrar')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique('monitoring_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_domain_results');
    }
};
