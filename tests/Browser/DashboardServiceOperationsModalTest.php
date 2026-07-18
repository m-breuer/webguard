<?php

declare(strict_types=1);

use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('opens the dashboard monitoring create actions in a responsive modal', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create(['monitoring_limit' => 10]);
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);

    $page = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]');

    foreach ([[1280, 800], [390, 640]] as $widthHeight) {
        [$width, $height] = $widthHeight;
        $page->navigate('/dashboard')->resize($width, $height);

        $trigger = 'a[data-form-modal-name="monitoring-form-modal"][href$="/monitorings/create"]';
        $modal = '[data-form-modal="monitoring-form-modal"]';

        $page->assertCount($trigger, 1)
            ->click($trigger)
            ->wait(1)
            ->waitForText(__('monitoring.form.sections.basic'))
            ->assertVisible($modal)
            ->assertScript(<<<'JS'
function () {
    const modal = document.querySelector('[data-form-modal="monitoring-form-modal"]');

    return modal?.contains(document.activeElement)
        && document.body.classList.contains('overflow-y-hidden')
        && document.documentElement.scrollWidth <= document.documentElement.clientWidth
        && document.body.scrollWidth <= document.body.clientWidth;
}
JS, true)
            ->assertNoJavaScriptErrors();

        if ($width === 390) {
            $page->assertScript(<<<'JS'
function () {
    const scrollRegion = document.querySelector('[data-form-modal="monitoring-form-modal"] .min-h-0.overflow-y-auto');

    return scrollRegion !== null
        && scrollRegion.scrollWidth <= scrollRegion.clientWidth;
}
JS, true);
        }

        $page->script("document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));");
        $page->assertMissing($modal);
    }
});

it('opens service operations status page and monitoring group modals responsively', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create(['monitoring_limit' => 10]);
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);
    $group = MonitoringGroup::factory()->for($user)->create([
        'name' => 'Browser service group',
    ]);
    StatusPage::query()->create([
        'user_id' => $user->id,
        'name' => 'Browser service status page',
        'slug' => 'browser-' . Str::lower(Str::random(10)),
        'is_public' => true,
    ]);

    $page = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]');

    foreach ([[1280, 800], [390, 640]] as $widthHeight) {
        [$width, $height] = $widthHeight;
        $page->navigate('/incidents/analytics')->resize($width, $height);

        $statusTrigger = 'a[data-form-modal-name="status-page-form-modal"][href$="/status-pages/create"]';
        $statusModal = '[data-form-modal="status-page-form-modal"]';
        $groupTrigger = 'a[data-form-modal-name="monitoring-group-form-modal"][href$="/monitoring-groups/' . $group->getRouteKey() . '/edit"]';
        $groupModal = '[data-form-modal="monitoring-group-form-modal"]';

        $page->assertCount($statusTrigger, 1)
            ->click($statusTrigger)
            ->wait(1)
            ->waitForText(__('status_page.form.name'))
            ->assertVisible($statusModal)
            ->assertScript(<<<'JS'
function () {
    const modal = document.querySelector('[data-form-modal="status-page-form-modal"]');

    return modal?.contains(document.activeElement)
        && document.documentElement.scrollWidth <= document.documentElement.clientWidth
        && document.body.scrollWidth <= document.body.clientWidth;
}
JS, true);
        $page->script("document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));");
        $page->assertMissing($statusModal)
            ->click($groupTrigger)
            ->wait(1)
            ->waitForText(__('monitoring_group.form.name'))
            ->assertVisible($groupModal)
            ->assertScript(<<<'JS'
function () {
    const modal = document.querySelector('[data-form-modal="monitoring-group-form-modal"]');

    return modal?.contains(document.activeElement)
        && document.documentElement.scrollWidth <= document.documentElement.clientWidth
        && document.body.scrollWidth <= document.body.clientWidth;
}
JS, true)
            ->assertNoJavaScriptErrors();

        $page->script("document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));");
        $page->assertMissing($groupModal);
    }
});
