<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateGuestMonitoringCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_first_active_server_instance_from_database(): void
    {
        Package::factory()->create();

        $guestUser = User::factory()->create([
            'role' => UserRole::GUEST->value,
        ]);

        ServerInstance::query()->where('code', 'de-1')->update(['is_active' => false]);

        ServerInstance::query()->create([
            'code' => 'eu-2',
            'ip_address' => '198.51.100.20',
            'api_key_hash' => 'api-key-eu-2',
            'is_active' => true,
        ]);

        ServerInstance::query()->create([
            'code' => 'aa-1',
            'ip_address' => '198.51.100.21',
            'api_key_hash' => 'api-key-aa-1',
            'is_active' => true,
        ]);

        $this->artisan('monitoring:create-guest')
            ->expectsQuestion('Enter the name for the monitoring', 'Guest HTTP Check')
            ->expectsQuestion('Enter the target for the monitoring', 'https://example.com')
            ->expectsOutput("Monitoring 'Guest HTTP Check' created successfully for guest user.")
            ->assertSuccessful();

        $this->assertDatabaseHas('monitorings', [
            'user_id' => $guestUser->id,
            'name' => 'Guest HTTP Check',
            'target' => 'https://example.com',
            'preferred_location' => 'aa-1',
        ]);
    }

    public function test_it_fails_when_no_active_server_instance_exists(): void
    {
        Package::factory()->create();

        User::factory()->create([
            'role' => UserRole::GUEST->value,
        ]);

        ServerInstance::query()->update(['is_active' => false]);

        $this->artisan('monitoring:create-guest')
            ->expectsOutput('No active server instance found. Please configure one first.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('monitorings', 0);
    }
}
