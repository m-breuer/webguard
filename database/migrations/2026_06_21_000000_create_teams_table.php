<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->foreignUlid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['created_by_user_id', 'created_at'], 'teams_created_by_created_at_idx');
        });

        Schema::create('team_memberships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', TeamRole::values())->default(TeamRole::MEMBER->value);
            $table->timestamps();

            $table->unique(['team_id', 'user_id'], 'team_memberships_team_user_unique');
            $table->index(['user_id', 'team_id'], 'team_memberships_user_team_idx');
            $table->index(['team_id', 'role'], 'team_memberships_team_role_idx');
        });

        Schema::create('team_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('email');
            $table->enum('role', TeamRole::values())->default(TeamRole::MEMBER->value);
            $table->string('token_hash', 128)->unique();
            $table->foreignUlid('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'email', 'accepted_at'], 'team_invitations_team_email_accepted_unique');
            $table->index(['email', 'accepted_at'], 'team_invitations_email_accepted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('team_memberships');
        Schema::dropIfExists('teams');
    }
};
