<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\AssertsApiContracts;
use Tests\TestCase;

final class InternalUiPasswordApiTest extends TestCase
{
    use AssertsApiContracts;
    use RefreshDatabase;

    public function test_authenticated_member_can_update_their_password_once(): void
    {
        Package::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('current-password')]);

        $testResponse = $this->actingAs($user)->putJson(route('app.profile.password.update'), [
            'current_password' => 'current-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $testResponse->assertOk()->assertJsonPath('data.updated', true);
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertInternalUiTelemetry($testResponse, 4, 131072);
    }

    public function test_password_contract_rejects_guests_and_invalid_current_passwords(): void
    {
        $this->putJson(route('app.profile.password.update'), [])->assertUnauthorized();

        Package::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('current-password')]);

        $this->actingAs($user)->putJson(route('app.profile.password.update'), [
            'current_password' => 'incorrect-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }
}
