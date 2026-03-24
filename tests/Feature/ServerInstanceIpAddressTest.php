<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerInstanceIpAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_server_instance_with_valid_ipv4_address(): void
    {
        $admin = $this->createAdminUser();

        $testResponse = $this->actingAs($admin)->post(route('admin.server-instances.store'), [
            'code' => 'us-1',
            'ip_address' => '192.168.10.20',
            'api_key' => '1234567890abcdef',
            'is_active' => '1',
        ]);

        $testResponse->assertRedirect(route('admin.server-instances.index'));

        $this->assertDatabaseHas('server_instances', [
            'code' => 'us-1',
            'ip_address' => '192.168.10.20',
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_create_server_instance_with_invalid_ipv4_address(): void
    {
        $admin = $this->createAdminUser();

        $testResponse = $this->from(route('admin.server-instances.create'))
            ->actingAs($admin)
            ->post(route('admin.server-instances.store'), [
                'code' => 'us-2',
                'ip_address' => '2001:db8::1',
                'api_key' => '1234567890abcdef',
                'is_active' => '1',
            ]);

        $testResponse->assertRedirect(route('admin.server-instances.create'));
        $testResponse->assertSessionHasErrors(['ip_address']);

        $this->assertDatabaseMissing('server_instances', [
            'code' => 'us-2',
        ]);
    }

    public function test_admin_can_update_server_instance_ipv4_address(): void
    {
        $admin = $this->createAdminUser();

        $instance = ServerInstance::query()->create([
            'code' => 'eu-1',
            'ip_address' => '10.0.0.1',
            'api_key_hash' => '1234567890abcdef',
            'is_active' => true,
        ]);

        $testResponse = $this->actingAs($admin)->put(route('admin.server-instances.update', $instance), [
            'code' => 'eu-1',
            'ip_address' => '10.0.0.2',
            'api_key' => '',
            'is_active' => '1',
        ]);

        $testResponse->assertRedirect(route('admin.server-instances.index'));

        $this->assertDatabaseHas('server_instances', [
            'id' => $instance->id,
            'code' => 'eu-1',
            'ip_address' => '10.0.0.2',
            'is_active' => true,
        ]);
    }

    private function createAdminUser(): User
    {
        Package::factory()->create();

        return User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
    }
}
