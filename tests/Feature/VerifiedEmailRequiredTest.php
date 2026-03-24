<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class VerifiedEmailRequiredTest extends TestCase
{
    public function test_unverified_user_is_redirected_to_verification_notice_for_protected_routes(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create();

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse->assertRedirect(route('verification.notice'));

        $monitoringsResponse = $this->actingAs($user)->get(route('monitorings.index'));
        $monitoringsResponse->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_access_protected_routes(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse->assertRedirect(route('monitorings.index'));

        $monitoringsResponse = $this->actingAs($user)->get(route('monitorings.index'));
        $monitoringsResponse->assertOk();
    }

    public function test_unverified_user_can_open_verification_notice_page(): void
    {
        Package::factory()->create();
        $user = User::factory()->unverified()->create();

        $testResponse = $this->actingAs($user)->get(route('verification.notice'));

        $testResponse->assertOk();
    }
}
