<?php

declare(strict_types=1);

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('displays monitoring cards', function () {
    $package = Package::factory()->create();
    $user = User::factory()->create();
    $user->package_id = $package->id;
    $user->save();

    Monitoring::factory()->count(3)->for($user)->create();

    $page = visit('/monitorings')->actingAs($user);
    $page->assertSeeAnythingIn('.monitoring-card');
});
