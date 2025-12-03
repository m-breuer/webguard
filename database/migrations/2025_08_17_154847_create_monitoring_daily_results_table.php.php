<?php

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
        Schema::create('monitoring_daily_results', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('monitoring_id')->constrained('monitorings')->onDelete('cascade');
            $table->date('date')->unique();
            $table->integer('uptime_total');
            $table->integer('downtime_total');
            $table->float('uptime_percentage');
            $table->float('downtime_percentage');
            $table->integer('uptime_minutes');
            $table->integer('downtime_minutes');
            $table->float('avg_response_time')->nullable();
            $table->integer('min_response_time')->nullable();
            $table->integer('max_response_time')->nullable();
            $table->integer('incidents_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_daily_results');
    }
};
