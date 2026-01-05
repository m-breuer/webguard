<?php

namespace Database\Factories;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Enums\ServerInstance;
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
        $type = fake()->randomElement(MonitoringType::cases());

        $data = [
            'name' => fake()->name(),
            'type' => $type,
            'target' => match ($type) {
                MonitoringType::HTTP, MonitoringType::KEYWORD => fake()->url(),
                MonitoringType::PING => fake()->ipv4(),
                MonitoringType::PORT => fake()->ipv4(), // Or fake()->domainName() if ports can be checked on domain names
            },
            'preferred_location' => fake()->randomElement(ServerInstance::cases()),
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
}
