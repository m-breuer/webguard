<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InternalUiTeamStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_a_team_and_becomes_its_admin(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.v1.internal.ui.teams.store'), ['name' => 'Platform', 'description' => 'Shared services'])
            ->assertCreated()->assertJsonPath('data.name', 'Platform')->assertJsonPath('data.role', 'admin');

        $this->assertDatabaseHas('teams', ['name' => 'Platform', 'created_by_user_id' => $user->id]);
        $this->assertDatabaseHas('team_memberships', ['user_id' => $user->id, 'role' => 'admin']);
    }
}
