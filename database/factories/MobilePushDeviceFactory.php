<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MobilePushDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MobilePushDevice>
 */
class MobilePushDeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<MobilePushDevice>
     */
    protected $model = MobilePushDevice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = 'fcm_' . Str::random(120);

        return [
            'user_id' => User::factory(),
            'platform' => fake()->randomElement(['ios', 'android']),
            'push_provider' => 'fcm',
            'push_token' => $token,
            'token_hash' => hash('sha256', $token),
            'device_name' => fake()->word(),
            'app_version' => '1.0.0',
            'locale' => 'en',
            'timezone' => 'Europe/Berlin',
            'enabled' => true,
            'notifications_authorized_at' => now(),
            'last_registered_at' => now(),
            'last_seen_at' => now(),
        ];
    }
}
