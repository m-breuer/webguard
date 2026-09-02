<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ApiKeyAbility;
use App\Jobs\DeleteUser;
use App\Models\Package;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class InternalUiProfileSecurityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_member_can_manage_only_their_own_scoped_api_keys(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherUser->createToken(ApiKeyService::storedName('Other key'), [ApiKeyAbility::ANALYTICS_READ->value]);

        $this->actingAs($user)->getJson(route('app.profile.api-keys.index'))
            ->assertOk()
            ->assertExactJson(['data' => []]);

        $testResponse = $this->actingAs($user)->postJson(route('app.profile.api-keys.store'), [
            'name' => 'Analytics key',
            'abilities' => [ApiKeyAbility::ANALYTICS_READ->value],
        ]);

        $testResponse->assertCreated()
            ->assertJsonPath('data.api_key.name', 'Analytics key')
            ->assertJsonPath('data.api_key.abilities.0', ApiKeyAbility::ANALYTICS_READ->value)
            ->assertJsonPath('data.api_key.revoked_at', null);

        $token = $user->tokens()->where('name', ApiKeyService::storedName('Analytics key'))->sole();
        $plainTextToken = (string) $testResponse->json('data.token');

        $this->assertStringContainsString('|', $plainTextToken);
        $this->assertNotSame($plainTextToken, $token->token);

        $this->actingAs($user)->deleteJson(route('app.profile.api-keys.destroy', $token))
            ->assertOk()
            ->assertJsonPath('data.id', $token->id)
            ->assertJsonPath('data.revoked_at', fn (?string $value): bool => $value !== null);

        $this->actingAs($user)->deleteJson(route('app.profile.api-keys.destroy', $token))
            ->assertOk();

        $this->actingAs($user)->getJson(route('app.profile.api-keys.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'Other key']);

        $this->actingAs($user)->deleteJson(route('app.profile.api-keys.destroy', $otherUser->tokens()->sole()))
            ->assertNotFound();
    }

    public function test_member_can_schedule_account_deletion_after_password_confirmation(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson(route('app.profile.destroy'), [
            'password' => 'incorrect-password',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->actingAs($user)->deleteJson(route('app.profile.destroy'), [
            'password' => 'password',
        ])->assertOk()->assertJsonPath('data.deletion_scheduled', true);

        $this->assertGuest();
        Queue::assertPushed(DeleteUser::class, fn (DeleteUser $deleteUser): bool => $deleteUser->user->is($user));
        $this->assertStringEndsWith('@webguard.invalid', (string) $user->fresh()?->email);
    }
}
