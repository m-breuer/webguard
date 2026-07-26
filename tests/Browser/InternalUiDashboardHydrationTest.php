<?php

declare(strict_types=1);

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('hydrates the dashboard from its JSON projection and exposes a recoverable error state', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $package = Package::factory()->create(['monitoring_limit' => 10]);
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);
    $monitoring = Monitoring::factory()->for($user)->create(['name' => 'JSON hydrated API']);

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/dashboard')
        ->waitForText('JSON hydrated API');

    $webpage->assertScript(<<<'JS'
function () {
    const root = document.querySelector('#dashboard-page-content');
    const loadedProjection = performance.getEntriesByType('resource').some((entry) => entry.name.includes('/api/v1/internal/ui/dashboard'));

    return root !== null
        && root.querySelector('[data-dashboard-content]') !== null
        && root.querySelector('[data-dashboard-loading]') === null
        && loadedProjection
        && !root.hasAttribute('aria-busy');
}
JS, true)
        ->script(<<<'JS'
const root = document.querySelector('#dashboard-page-content');
root.dataset.apiEndpoint = '/api/v1/internal/ui/not-found';
void window.Alpine.$data(root).load(root);
JS)
        ->wait(0.5)
        ->assertVisible('[data-dashboard-error]')
        ->assertScript(<<<'JS'
function () {
    const root = document.querySelector('#dashboard-page-content');
    const error = root?.querySelector('[data-dashboard-error]');

    return error !== null
        && getComputedStyle(error).display !== 'none'
        && !root.hasAttribute('aria-busy');
}
JS, true)
        ->assertNoJavaScriptErrors();
});
