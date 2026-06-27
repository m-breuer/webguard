<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Exception;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    public function test_user_observer_assigns_cheapest_selectable_package(): void
    {
        $expensivePackage = Package::factory()->create([
            'monitoring_limit' => 10,
            'price' => 10,
            'is_selectable' => true,
        ]);
        $cheapestPackage = Package::factory()->create([
            'monitoring_limit' => 5,
            'price' => 0,
            'is_selectable' => true,
        ]);

        $user = User::factory()->create(['package_id' => null]);

        $this->assertSame($cheapestPackage->id, $user->package_id);
        $this->assertNotSame($expensivePackage->id, $user->package_id);
    }

    public function test_user_observer_falls_back_to_first_package_when_none_are_selectable(): void
    {
        $fallbackPackage = Package::factory()->create([
            'price' => 99,
            'is_selectable' => false,
        ]);

        $user = User::factory()->create(['package_id' => null]);

        $this->assertSame($fallbackPackage->id, $user->package_id);
    }

    public function test_user_observer_throws_when_no_package_exists(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No packages available to assign to new user.');

        User::factory()->create(['package_id' => null]);
    }
}
