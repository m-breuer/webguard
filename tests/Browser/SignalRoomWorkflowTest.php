<?php

declare(strict_types=1);

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('supports desktop service selection and keeps the Signal Room inside the viewport', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create(['monitoring_limit' => 10]);
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);
    $monitoring = Monitoring::factory()->for($user)->create([
        'name' => 'Payments API',
        'target' => 'https://payments.example.test/health',
    ]);
    MonitoringResponse::query()->create([
        'monitoring_id' => $monitoring->id,
        'status' => MonitoringStatus::UP,
        'response_time' => 128,
    ]);

    $page = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/dashboard')
        ->resize(1280, 800);

    $page->assertVisible('[data-signal-room]')
        ->assertVisible('[data-signal-service="' . $monitoring->id . '"]')
        ->assertVisible('aside [data-signal-detail]')
        ->assertScript(<<<'JS'
function () {
    const workspace = document.querySelector('[data-workspace-navigation]');
    const utilities = document.querySelector('[data-navigation-utilities]');
    if (!workspace || !utilities) {
        return false;
    }

    return utilities.getBoundingClientRect().top - workspace.getBoundingClientRect().bottom >= 24;
}
JS, true)
        ->assertScript(<<<'JS'
function () {
    const detail = document.querySelector('aside [data-signal-detail]');
    return detail !== null
        && detail.textContent.includes('Payments API')
        && detail.textContent.includes('128 ms');
}
JS, true)
        ->assertScript(<<<'JS'
function () {
    return document.documentElement.scrollWidth <= document.documentElement.clientWidth
        && document.body.scrollWidth <= document.body.clientWidth;
}
JS, true)
        ->assertNoJavaScriptErrors();
});

it('supports mobile filtering, service details and Escape to close the detail sheet', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create(['monitoring_limit' => 10]);
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);
    $healthy = Monitoring::factory()->for($user)->create(['name' => 'Healthy Payments API']);
    MonitoringResponse::query()->create([
        'monitoring_id' => $healthy->id,
        'status' => MonitoringStatus::UP,
    ]);
    $unknown = Monitoring::factory()->for($user)->create(['name' => 'Unknown Search Service']);

    $page = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/dashboard')
        ->resize(390, 640);

    $page->click('[data-signal-filter="attention"]')
        ->assertScript(<<<JS
function () {
    const service = document.querySelector('[data-signal-service="{$healthy->id}"]');
    return service !== null && getComputedStyle(service).display === 'none';
}
JS, true)
        ->click('[data-signal-filter="all"]')
        ->click('[data-signal-service="' . $healthy->id . '"]')
        ->assertVisible('[data-signal-mobile-detail]')
        ->assertScript(<<<'JS'
function () {
    const detail = document.querySelector('[data-signal-mobile-detail]');
    return detail !== null && detail.textContent.includes('Healthy Payments API');
}
JS, true)
        ->assertScript(<<<'JS'
function () {
    return document.documentElement.scrollWidth <= document.documentElement.clientWidth
        && document.body.scrollWidth <= document.body.clientWidth;
}
JS, true);
    $page->keys('input[type="search"]', 'Escape');
    $page->wait(0.2);

    $page->assertScript(<<<'JS'
function () {
    const sheet = document.querySelector('[data-signal-mobile-sheet]');
    return sheet !== null && getComputedStyle(sheet).display === 'none';
}
JS, true)
        ->type('input[type="search"]', 'Unknown Search')
        ->assertScript(<<<JS
function () {
    const unknown = document.querySelector('[data-signal-service="{$unknown->id}"]');
    const healthy = document.querySelector('[data-signal-service="{$healthy->id}"]');
    return unknown !== null
        && healthy !== null
        && getComputedStyle(unknown).display !== 'none'
        && getComputedStyle(healthy).display === 'none';
}
JS, true)
        ->assertNoJavaScriptErrors();
});
