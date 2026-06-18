<?php

declare(strict_types=1);

use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_groups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('public_label_enabled')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'name'], 'monitoring_groups_user_name_unique');
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('monitoring_group_monitoring', function (Blueprint $table): void {
            $table->foreignUlid('monitoring_group_id');
            $table->foreignIdFor(Monitoring::class);
            $table->timestamps();

            $table->primary(['monitoring_group_id', 'monitoring_id'], 'monitoring_group_monitoring_pk');
            $table->index(['monitoring_id', 'monitoring_group_id'], 'monitoring_group_monitoring_monitoring_idx');
            $table->foreign('monitoring_group_id', 'monitoring_group_monitoring_group_fk')
                ->references('id')
                ->on('monitoring_groups')
                ->cascadeOnDelete();
            $table->foreign('monitoring_id', 'monitoring_group_monitoring_monitoring_fk')
                ->references('id')
                ->on('monitorings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_group_monitoring');
        Schema::dropIfExists('monitoring_groups');
    }
};
