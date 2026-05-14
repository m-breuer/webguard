<?php

declare(strict_types=1);

use App\Models\Monitoring;
use App\Models\StatusPageComponent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('status_page_component_monitoring', function (Blueprint $table): void {
            $table->foreignIdFor(StatusPageComponent::class);
            $table->foreignIdFor(Monitoring::class);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->primary(['status_page_component_id', 'monitoring_id'], 'status_page_component_monitoring_pk');
            $table->index(['monitoring_id', 'status_page_component_id'], 'status_page_component_monitoring_monitoring_idx');
            $table->foreign('status_page_component_id', 'status_page_component_monitoring_component_fk')
                ->references('id')
                ->on('status_page_components')
                ->cascadeOnDelete();
            $table->foreign('monitoring_id', 'status_page_component_monitoring_monitoring_fk')
                ->references('id')
                ->on('monitorings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_page_component_monitoring');
    }
};
