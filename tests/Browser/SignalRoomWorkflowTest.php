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

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/dashboard')
        ->resize(1280, 800);

    $webpage->assertVisible('[data-signal-room]')
        ->assertVisible('[data-signal-service="' . $monitoring->id . '"]')
        ->assertVisible('aside [data-signal-detail]')
        ->assertScript(<<<'JS'
function () {
    const primaryNavigation = document.querySelector('[data-primary-navigation]');
    if (!primaryNavigation) {
        return false;
    }

    return primaryNavigation.querySelectorAll('[data-primary-destination]').length === 1
        && primaryNavigation.querySelector('[data-secondary-navigation]') !== null
        && primaryNavigation.querySelector('[data-notifications-navigation]') !== null;
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
        ->click('aside [data-signal-tab="checks"]')
        ->assertScript(<<<'JS'
function () {
    const detail = document.querySelector('aside [data-signal-detail]');
    const tab = detail?.querySelector('[data-signal-tab="checks"]');
    const panel = detail?.querySelector('[data-signal-tab-panel="checks"]');

    return tab?.getAttribute('aria-selected') === 'true'
        && panel !== null
        && getComputedStyle(panel).display !== 'none'
        && panel.textContent.includes('128 ms');
}
JS, true)
        ->click('aside [data-signal-tab="incidents"]')
        ->assertScript(<<<'JS'
function () {
    const detail = document.querySelector('aside [data-signal-detail]');
    const tab = detail?.querySelector('[data-signal-tab="incidents"]');
    const panel = detail?.querySelector('[data-signal-tab-panel="incidents"]');

    return tab?.getAttribute('aria-selected') === 'true'
        && panel !== null
        && getComputedStyle(panel).display !== 'none';
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

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/dashboard')
        ->resize(390, 640);

    $webpage->click('[data-signal-filter="attention"]')
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
    $webpage->keys('input[type="search"]', 'Escape');
    $webpage->wait(0.2);

    $webpage->assertScript(<<<'JS'
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

it('keeps the desktop language menu within the navigation rail', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create(['monitoring_limit' => 10]);
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/dashboard')
        ->resize(1280, 800)
        ->click('#language-switch-desktop')
        ->assertScript(<<<'JS'
function () {
    const trigger = document.querySelector('#language-switch-desktop');
    const menu = trigger?.closest('[x-data]')?.querySelector('.absolute');
    const rail = document.querySelector('nav');

    if (!trigger || !menu || !rail || getComputedStyle(menu).display === 'none') {
        return false;
    }

    const menuRect = menu.getBoundingClientRect();
    const triggerRect = trigger.getBoundingClientRect();
    const railRect = rail.getBoundingClientRect();

    return menuRect.left >= railRect.left
        && menuRect.right <= railRect.right
        && menuRect.bottom <= triggerRect.top;
}
JS, true)
        ->click('#language-switch-desktop')
        ->click('#profile-menu-desktop')
        ->assertScript(<<<'JS'
function () {
    const trigger = document.querySelector('#profile-menu-desktop');
    const menu = trigger?.closest('[x-data]')?.querySelector('.absolute');
    const rail = document.querySelector('nav');

    if (!trigger || !menu || !rail || getComputedStyle(menu).display === 'none') {
        return false;
    }

    const menuRect = menu.getBoundingClientRect();
    const triggerRect = trigger.getBoundingClientRect();
    const railRect = rail.getBoundingClientRect();

    return menuRect.left >= railRect.left
        && menuRect.right <= railRect.right
        && menuRect.bottom <= triggerRect.top;
}
JS, true)
        ->assertNoJavaScriptErrors();
});
