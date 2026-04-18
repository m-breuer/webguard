<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitoring>
 */
class MonitoringFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            MonitoringType::HTTP,
            MonitoringType::PING,
            MonitoringType::KEYWORD,
            MonitoringType::PORT,
        ]);

        $data = [
            'name' => fake()->name(),
            'type' => $type,
            'target' => match ($type) {
                MonitoringType::HTTP, MonitoringType::KEYWORD => fake()->url(),
                MonitoringType::PING => fake()->ipv4(),
                MonitoringType::PORT => fake()->ipv4(), // Or fake()->domainName() if ports can be checked on domain names
            },
            'preferred_location' => 'de-1',
            'status' => MonitoringLifecycleStatus::ACTIVE,
        ];

        if ($type === MonitoringType::PORT) {
            $data['port'] = fake()->numberBetween(1, 65535);
        }

        if ($type === MonitoringType::KEYWORD) {
            $data['keyword'] = fake()->word();
        }

        return $data;
    }

    public function heartbeat(): static
    {
        return $this->state(function (): array {
            return [
                'type' => MonitoringType::HEARTBEAT,
                'target' => 'https://webguard.test/heartbeat/example-token',
                'heartbeat_token' => 'example-token',
                'heartbeat_interval_minutes' => 60,
                'heartbeat_grace_minutes' => 10,
                'heartbeat_last_ping_at' => null,
            ];
        });
    }
}
