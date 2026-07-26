<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'deleted_at', 'status', 'name'],
                'monitorings_user_status_name_idx'
            );
            $table->index(
                ['team_id', 'deleted_at', 'status', 'name'],
                'monitorings_team_status_name_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropIndex('monitorings_user_status_name_idx');
            $table->dropIndex('monitorings_team_status_name_idx');
        });
    }
};
