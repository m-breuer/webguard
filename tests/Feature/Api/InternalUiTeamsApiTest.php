<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Package;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssertsApiContracts;
use Tests\TestCase;

final class InternalUiTeamsApiTest extends TestCase
{
    use AssertsApiContracts;
    use RefreshDatabase;

    public function test_member_only_receives_teams_visible_to_them(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $visibleTeam = Team::factory()->create(['name' => 'Visible team']);
        TeamMembership::factory()->for($visibleTeam)->for($user)->admin()->create();
        $hiddenTeam = Team::factory()->create(['name' => 'Hidden team']);
        TeamMembership::factory()->for($hiddenTeam)->for(User::factory())->create();

        $testResponse = $this->actingAs($user)->getJson(route('app.teams.index'));

        $testResponse
            ->assertOk()
            ->assertJsonPath('data.teams.0.id', $visibleTeam->id)
            ->assertJsonPath('data.teams.0.role', 'admin')
            ->assertJsonMissing(['id' => $hiddenTeam->id]);

        $this->assertInternalUiTelemetry($testResponse, 4, 131072);
    }
}
