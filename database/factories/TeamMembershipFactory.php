<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\TeamMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMembership>
 */
class TeamMembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'role' => TeamRole::MEMBER,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'role' => TeamRole::ADMIN,
        ]);
    }
}
