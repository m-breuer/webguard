<?php

use App\Enums\MonitoringStatus;
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
        Schema::create('monitoring_response_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignIdFor(Monitoring::class, 'monitoring_id')->constrained()->cascadeOnDelete();

            $table->enum('status', MonitoringStatus::values())->default(MonitoringStatus::UNKNOWN->value);
            $table->float('response_time')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_response_results');
    }
};
