<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MonitoringGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringGroup>
 */
class MonitoringGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'public_label_enabled' => false,
        ];
    }
}
