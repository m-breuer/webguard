<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ApiKeyAbility;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_authenticated_user_can_create_and_list_a_named_scoped_key_without_exposing_its_secret(): void
    {
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->postJson(route('v1.api-keys.store'), [
            'name' => 'Server agent',
            'abilities' => [ApiKeyAbility::SERVER_HEALTH_WRITE->value],
        ]);

        $testResponse->assertCreated()
            ->assertJsonPath('data.key.name', 'Server agent')
            ->assertJsonPath('data.key.abilities.0', ApiKeyAbility::SERVER_HEALTH_WRITE->value)
            ->assertJsonPath('data.key.revoked', false);

        $plainTextToken = (string) $testResponse->json('data.token');
        $personalAccessToken = PersonalAccessToken::query()->sole();

        $this->assertStringContainsString('|', $plainTextToken);
        $this->assertNotSame($plainTextToken, $personalAccessToken->token);
        $this->assertSame(ApiKeyService::storedName('Server agent'), $personalAccessToken->name);

        $this->actingAs($user)->getJson(route('v1.api-keys.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $personalAccessToken->id)
            ->assertJsonPath('data.0.name', 'Server agent')
            ->assertJsonPath('data.0.token_prefix', $personalAccessToken->id . '|')
            ->assertJsonMissing(['token' => $plainTextToken])
            ->assertJsonMissing(['token' => $personalAccessToken->token]);
    }

    public function test_key_names_are_unique_per_user_and_require_allowed_abilities(): void
    {
        $user = User::factory()->create();
        $user->createToken(ApiKeyService::storedName('Analytics'), [ApiKeyAbility::ANALYTICS_READ->value]);

        $this->actingAs($user)->postJson(route('v1.api-keys.store'), [
            'name' => 'Analytics',
            'abilities' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'abilities']);

        $this->actingAs($user)->postJson(route('v1.api-keys.store'), [
            'name' => 'Other key',
            'abilities' => ['external:write'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['abilities.0']);
    }

    public function test_key_metadata_is_scoped_to_its_owner_and_revocation_is_idempotent(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $owner->createToken(ApiKeyService::storedName('Analytics'), [ApiKeyAbility::ANALYTICS_READ->value])->accessToken;

        $this->actingAs($otherUser)->getJson(route('v1.api-keys.show', $token))
            ->assertNotFound();

        $this->actingAs($owner)->deleteJson(route('v1.api-keys.destroy', $token))
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->actingAs($owner)->deleteJson(route('v1.api-keys.destroy', $token))
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->assertNotNull($token->fresh()?->revoked_at);
    }

    public function test_analytics_key_can_only_read_analytics_routes_and_tracks_its_last_use(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        $plainTextToken = $user->createToken(
            ApiKeyService::storedName('Analytics'),
            [ApiKeyAbility::ANALYTICS_READ->value]
        )->plainTextToken;

        $this->withToken($plainTextToken)
            ->getJson('/api/v1/monitorings/' . $monitoring->id . '/status')
            ->assertOk();

        $this->withToken($plainTextToken)
            ->deleteJson('/api/v1/monitorings/' . $monitoring->id)
            ->assertForbidden();

        $personalAccessToken = PersonalAccessToken::query()->where('name', ApiKeyService::storedName('Analytics'))->sole();
        $this->assertNotNull($personalAccessToken->last_used_at);
    }

    public function test_server_health_key_can_submit_only_its_owners_server_health_monitoring(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $monitoring = Monitoring::factory()->for($owner)->create([
            'type' => MonitoringType::SERVER_HEALTH,
        ]);
        $otherMonitoring = Monitoring::factory()->for($otherUser)->create([
            'type' => MonitoringType::SERVER_HEALTH,
        ]);
        $plainTextToken = $owner->createToken(
            ApiKeyService::storedName('Server agent'),
            [ApiKeyAbility::SERVER_HEALTH_WRITE->value]
        )->plainTextToken;

        $this->withToken($plainTextToken)
            ->postJson(route('v1.server-health.bearer.store', $monitoring), ['cpu_usage_percent' => 42.5])
            ->assertOk();

        $this->withToken($plainTextToken)
            ->postJson(route('v1.server-health.bearer.store', $otherMonitoring), ['cpu_usage_percent' => 42.5])
            ->assertNotFound();

        $this->withToken($plainTextToken)
            ->getJson('/api/v1/monitorings/' . $monitoring->id . '/status')
            ->assertForbidden();
    }

    public function test_revoked_key_can_no_longer_authenticate_but_existing_legacy_token_stays_compatible(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();
        $newToken = $user->createToken(
            ApiKeyService::storedName('Analytics'),
            [ApiKeyAbility::ANALYTICS_READ->value]
        );
        $legacyToken = $user->createToken(ApiKeyService::LEGACY_TOKEN_NAME)->plainTextToken;

        resolve(ApiKeyService::class)->revoke($newToken->accessToken);

        $this->withToken($newToken->plainTextToken)
            ->getJson('/api/v1/monitorings/' . $monitoring->id . '/status')
            ->assertUnauthorized();

        $this->withToken($legacyToken)
            ->getJson('/api/v1/monitorings/' . $monitoring->id . '/status')
            ->assertOk();
    }
}
