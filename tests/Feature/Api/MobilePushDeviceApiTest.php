<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\NotificationChannel;
use App\Models\MobilePushDevice;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobilePushDeviceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_mobile_push_device(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $testResponse = $this->actingAs($user)->postJson('/api/v1/mobile-push-devices', [
            'platform' => 'ios',
            'push_token' => 'fcm-token-123',
            'device_name' => 'Marcel iPhone',
            'app_version' => '1.0.0',
            'locale' => 'de-DE',
            'timezone' => 'Europe/Berlin',
        ]);

        $testResponse
            ->assertCreated()
            ->assertJsonPath('data.platform', 'ios')
            ->assertJsonPath('data.device_name', 'Marcel iPhone')
            ->assertJsonMissingPath('data.push_token');

        $this->assertDatabaseHas('mobile_push_devices', [
            'user_id' => $user->id,
            'platform' => 'ios',
            'token_hash' => hash('sha256', 'fcm-token-123'),
            'enabled' => true,
        ]);

        $user->refresh();
        $this->assertTrue((bool) data_get($user->notification_channels, NotificationChannel::MOBILE_PUSH->value . '.enabled'));
    }

    public function test_registering_same_token_updates_existing_mobile_push_device(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/mobile-push-devices', [
            'platform' => 'android',
            'push_token' => 'same-fcm-token',
            'device_name' => 'Old name',
        ])->assertCreated();

        $testResponse = $this->actingAs($user)->postJson('/api/v1/mobile-push-devices', [
            'platform' => 'android',
            'push_token' => 'same-fcm-token',
            'device_name' => 'New name',
            'enabled' => true,
        ]);

        $testResponse
            ->assertOk()
            ->assertJsonPath('data.device_name', 'New name');

        $this->assertDatabaseCount('mobile_push_devices', 1);
    }

    public function test_user_can_list_update_and_revoke_own_mobile_push_devices(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $device = MobilePushDevice::factory()->for($user)->create([
            'device_name' => 'Pixel',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/mobile-push-devices')
            ->assertOk()
            ->assertJsonPath('data.0.id', $device->id)
            ->assertJsonMissingPath('data.0.push_token');

        $this->actingAs($user)
            ->patchJson('/api/v1/mobile-push-devices/' . $device->id, [
                'device_name' => 'Pixel 9',
                'enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.device_name', 'Pixel 9')
            ->assertJsonPath('data.enabled', false);

        $this->actingAs($user)
            ->deleteJson('/api/v1/mobile-push-devices/' . $device->id)
            ->assertNoContent();

        $device->refresh();
        $this->assertFalse($device->enabled);
        $this->assertNotNull($device->revoked_at);
    }

    public function test_user_cannot_update_or_revoke_another_users_mobile_push_device(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $device = MobilePushDevice::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/mobile-push-devices/' . $device->id, [
                'enabled' => false,
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->deleteJson('/api/v1/mobile-push-devices/' . $device->id)
            ->assertNotFound();
    }
}
