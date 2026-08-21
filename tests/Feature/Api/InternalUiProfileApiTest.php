<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssertsApiContracts;
use Tests\TestCase;

final class InternalUiProfileApiTest extends TestCase
{
    use AssertsApiContracts;
    use RefreshDatabase;

    public function test_authenticated_member_can_update_their_account_without_exposing_notification_configuration(): void
    {
        Package::factory()->create();
        $user = User::factory()->create(['notification_channels' => ['telegram' => ['bot_token' => 'secret']]]);

        $response = $this->actingAs($user)->patchJson(route('api.v1.internal.ui.profile.update'), [
            'name' => 'Updated member',
            'email' => 'updated@example.test',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated member')
            ->assertJsonPath('data.email', 'updated@example.test')
            ->assertJsonPath('data.is_verified', false)
            ->assertJsonMissing(['bot_token' => 'secret']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated member', 'email' => 'updated@example.test', 'email_verified_at' => null]);
        $this->assertInternalUiTelemetry($response, 4, 131072);
    }

    public function test_profile_contract_requires_authentication_and_valid_data(): void
    {
        $this->patchJson(route('api.v1.internal.ui.profile.update'), [])->assertUnauthorized();

        Package::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('api.v1.internal.ui.profile.update'), ['name' => '', 'email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }
}
