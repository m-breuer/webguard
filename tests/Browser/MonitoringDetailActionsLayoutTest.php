<?php

declare(strict_types=1);

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('keeps monitoring detail actions aligned and usable across responsive widths', function (): void {
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
        'name' => 'Browser API',
        'target' => 'https://example.test/health',
    ]);

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/monitorings/' . $monitoring->id)
        ->resize(390, 844)
        ->assertVisible('[data-monitoring-actions-trigger]');

    $webpage->assertScript(<<<'JS'
function () {
    const actions = document.querySelector('[data-monitoring-actions]');
    const trigger = document.querySelector('[data-monitoring-actions-trigger]');
    const header = document.querySelector('[data-monitoring-detail-header]');

    if (!actions || !trigger || !header) {
        return false;
    }

    const actionsRect = actions.getBoundingClientRect();
    const triggerRect = trigger.getBoundingClientRect();
    const headerRect = header.getBoundingClientRect();

    return Math.abs(triggerRect.right - actionsRect.right) <= 1
        && actionsRect.right <= headerRect.right + 1
        && document.documentElement.scrollWidth <= document.documentElement.clientWidth;
}
JS, true)
        ->click('[data-monitoring-actions-trigger]')
        ->assertScript(<<<'JS'
function () {
    const trigger = document.querySelector('[data-monitoring-actions-trigger]');
    const menu = document.querySelector('#monitoring-actions-menu');

    return trigger?.getAttribute('aria-expanded') === 'true'
        && menu !== null
        && getComputedStyle(menu).display !== 'none'
        && menu.textContent.includes('Edit')
        && menu.textContent.includes('Delete');
}
JS, true)
        ->resize(1280, 800)
        ->assertScript(<<<'JS'
function () {
    const actions = document.querySelector('[data-monitoring-actions]');
    const trigger = document.querySelector('[data-monitoring-actions-trigger]');
    const header = document.querySelector('[data-monitoring-detail-header]');

    if (!actions || !trigger || !header) {
        return false;
    }

    const actionsRect = actions.getBoundingClientRect();
    const triggerRect = trigger.getBoundingClientRect();
    const headerRect = header.getBoundingClientRect();

    return actionsRect.left >= headerRect.left - 1
        && actionsRect.right <= headerRect.right + 1
        && Math.abs(triggerRect.right - actionsRect.right) <= 1
        && document.documentElement.scrollWidth <= document.documentElement.clientWidth;
}
JS, true)
        ->assertNoJavaScriptErrors();
});
