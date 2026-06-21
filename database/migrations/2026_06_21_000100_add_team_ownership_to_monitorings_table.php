<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('monitorings', function (Blueprint $table): void {
            $table->foreignUlid('team_id')->nullable()->after('user_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('created_by_user_id')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
            $table->foreignUlid('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['team_id', 'created_at'], 'monitorings_team_created_at_idx');
            $table->index(['created_by_user_id', 'created_at'], 'monitorings_created_by_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropForeign(['team_id']);
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex('monitorings_team_created_at_idx');
            $table->dropIndex('monitorings_created_by_created_at_idx');
            $table->dropColumn(['team_id', 'created_by_user_id']);
        });

        Schema::table('monitorings', function (Blueprint $table): void {
            $table->foreignUlid('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
