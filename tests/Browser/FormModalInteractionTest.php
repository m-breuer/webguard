<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('keeps the user monitoring modal usable on desktop and mobile', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create(['monitoring_limit' => 10]);
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);
    $instanceCode = 'browser-' . Str::lower(Str::random(8));
    ServerInstance::query()->create([
        'code' => $instanceCode,
        'api_key_hash' => 'browser-modal-instance-key',
        'is_active' => true,
    ]);
    Monitoring::factory()->for($user)->create([
        'name' => 'Existing browser monitor',
        'preferred_location' => $instanceCode,
    ]);

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/monitorings');

    foreach ([[1280, 800], [390, 640]] as $iteration => [$width, $height]) {
        if ($iteration > 0) {
            $webpage->navigate('/monitorings');
        }

        $webpage->resize($width, $height);

        $trigger = 'header [data-form-modal-name="monitoring-form-modal"]';
        $modal = '[data-form-modal="monitoring-form-modal"]';
        $submit = $modal . ' form button[type="submit"]';
        $nameField = $modal . ' [x-ref="content"] form input[name="name"]';
        $targetField = $modal . ' [x-ref="content"] form input[name="target"]';
        $typeField = $modal . ' [x-ref="content"] form select[name="type"]';

        $webpage->assertCount($trigger, 1)
            ->click($trigger);
        expect($webpage->script(<<<'JS'
async function () {
    const loader = document.querySelector('[x-data="formModalLoader()"]');
    const startedAt = Date.now();

    while (loader && window.Alpine.$data(loader)?.loading && Date.now() - startedAt < 10000) {
        await new Promise((resolve) => setTimeout(resolve, 50));
    }

    return loader !== null && window.Alpine.$data(loader)?.loading === false;
}
JS))->toBeTrue();
        $webpage->waitForText(__('monitoring.form.sections.basic'))
            ->assertVisible($modal)
            ->assertScript(<<<'JS'
function () {
    const form = document.querySelector('[data-form-modal="monitoring-form-modal"] [x-ref="content"] [data-monitoring-type-form]');
    const state = form ? window.Alpine.$data(form) : null;

    return state?.timeoutValue === 5;
}
JS, true)
            ->assertScript(<<<'JS'
function () {
    const modal = document.querySelector('[data-form-modal="monitoring-form-modal"]');

    return modal?.contains(document.activeElement)
        && document.body.classList.contains('overflow-y-hidden')
        && document.documentElement.scrollWidth <= document.documentElement.clientWidth
        && document.body.scrollWidth <= document.body.clientWidth;
}
JS, true);

        $webpage->assertScript(<<<'JS'
function () {
    const modal = document.querySelector('[data-form-modal="monitoring-form-modal"]');
    const trigger = document.querySelector('header [data-form-modal-name="monitoring-form-modal"]');
    const focusableSelector = 'a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), details, [tabindex]:not([tabindex="-1"])';
    const elements = [...modal.querySelectorAll(focusableSelector)].filter((element) => element.getClientRects().length > 0);
    const first = elements[0];
    const last = elements.at(-1);

    if (! modal || ! trigger || ! first || ! last) {
        return false;
    }

    last.focus();
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true }));
    const wrapsForward = document.activeElement === first;

    first.focus();
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', shiftKey: true, bubbles: true, cancelable: true }));
    const wrapsBackward = document.activeElement === last;

    trigger.focus();

    return wrapsForward
        && wrapsBackward
        && trigger.closest('[inert]') !== null
        && modal.contains(document.activeElement);
}
JS, true);

        $webpage->select($typeField, 'port')
            ->assertScript(<<<'JS'
function () {
    const form = document.querySelector('[data-form-modal="monitoring-form-modal"] [x-ref="content"] form');
    const type = form?.querySelector('select[name="type"]');

    const port = form?.querySelector('input[name="port"]');

    return type?.value === 'port'
        && port instanceof HTMLInputElement
        && getComputedStyle(port).display !== 'none';
}
JS, true);

        if ($width === 390) {
            $webpage->assertScript(<<<'JS'
function () {
    const scrollRegion = document.querySelector('[data-form-modal="monitoring-form-modal"] .min-h-0.overflow-y-auto');

    return scrollRegion !== null
        && scrollRegion.scrollHeight > scrollRegion.clientHeight
        && scrollRegion.scrollWidth <= scrollRegion.clientWidth;
}
JS, true);
        }

        $webpage->clear($nameField)
            ->press($submit)
            ->assertVisible($modal);
        $webpage->script("document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));");
        $webpage->assertMissing($modal)
            ->assertScript(<<<'JS'
function () {
    return document.activeElement === document.querySelector('header [data-form-modal-name="monitoring-form-modal"]');
}
JS, true);
        $webpage->navigate('/monitorings')
            ->click($trigger)
            ->waitForText(__('monitoring.form.sections.basic'))
            ->assertVisible($modal)
            ->assertCount($nameField, 1)
            ->fill($nameField, 'Browser modal ' . Str::lower(Str::random(8)))
            ->fill($targetField, 'https://example.com');
        $webpage->assertScript(<<<'JS'
function () {
    const modal = document.querySelector('[data-form-modal="monitoring-form-modal"]');
    const form = modal?.querySelector('[x-ref="content"] form');
    const name = form?.querySelector('input[name="name"]')?.value;
    const type = form?.querySelector('select[name="type"]')?.value;
    const target = form?.querySelector('input[name="target"]')?.value;
    return Boolean(name && type === 'http' && target === 'https://example.com' && form?.checkValidity());
}
JS, true);
        $webpage->press($submit)
            ->waitForText(__('monitoring.messages.created'))
            ->assertNoJavaScriptErrors();
    }
});

it('submits monitoring edits from a dynamically loaded modal on the first attempt', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create(['monitoring_limit' => 10]);
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);
    $instanceCode = 'browser-edit-' . Str::lower(Str::random(8));
    ServerInstance::query()->create([
        'code' => $instanceCode,
        'api_key_hash' => 'browser-edit-modal-instance-key',
        'is_active' => true,
    ]);
    $monitoring = Monitoring::factory()->for($user)->create([
        'name' => 'Editable browser monitor',
        'preferred_location' => $instanceCode,
    ]);

    $modal = '[data-form-modal="monitoring-form-modal"]';
    $trigger = 'a[data-form-modal-name="monitoring-form-modal"][href$="/monitorings/' . $monitoring->getRouteKey() . '/edit"]';
    $nameField = $modal . ' [x-ref="content"] form input[name="name"]';
    $submit = $modal . ' form button[type="submit"]';

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/monitorings/' . $monitoring->getRouteKey())
        ->assertCount($trigger, 1)
        ->click($trigger)
        ->waitForText(__('monitoring.form.sections.basic'))
        ->assertVisible($modal)
        ->assertScript(<<<'JS'
function () {
    const form = document.querySelector('[data-form-modal="monitoring-form-modal"] [x-ref="content"] [data-monitoring-type-form]');
    const state = form ? window.Alpine.$data(form) : null;

    return state?.timeoutValue === 5;
}
JS, true)
        ->clear($nameField)
        ->fill($nameField, 'Updated browser monitor')
        ->click($submit)
        ->waitForText(__('monitoring.messages.updated'))
        ->assertSee('Updated browser monitor')
        ->assertScript(<<<JS
function () {
    return window.location.pathname === '/monitorings/{$monitoring->getRouteKey()}';
}
JS, true)
        ->assertNoJavaScriptErrors();
});

it('opens the admin package edit form in a focused responsive modal', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create([
        'monitoring_limit' => 25,
        'is_selectable' => false,
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
        'password' => Hash::make('password'),
    ]);

    $webpage = visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/admin/packages');

    foreach ([[1280, 800], [390, 640]] as $iteration => [$width, $height]) {
        if ($iteration > 0) {
            $webpage->navigate('/admin/packages');
        }

        $webpage->resize($width, $height);

        $editTrigger = 'a[data-form-modal-name="admin-package-form-modal"][href$="/packages/' . $package->getRouteKey() . '/edit"]';
        $modal = '[data-form-modal="admin-package-form-modal"]';

        $webpage->assertCount($editTrigger, 1)
            ->click($editTrigger)
            ->waitForText(__('admin.packages.fields.monitoring_limit'))
            ->assertVisible($modal)
            ->assertScript(<<<'JS'
function () {
    const modal = document.querySelector('[data-form-modal="admin-package-form-modal"]');

    return modal?.contains(document.activeElement)
        && document.body.classList.contains('overflow-y-hidden')
        && document.documentElement.scrollWidth <= document.documentElement.clientWidth
        && document.body.scrollWidth <= document.body.clientWidth;
}
JS, true);
        $webpage->script("document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));");
        $webpage->assertMissing($modal)
            ->assertNoJavaScriptErrors();
    }
});

it('keeps the status page edit form flush with the modal surface', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $user = User::factory()->create([
        'package_id' => Package::factory()->create()->id,
        'password' => Hash::make('password'),
    ]);
    $statusPage = StatusPage::query()->create([
        'user_id' => $user->id,
        'name' => 'Flat modal status page',
        'is_public' => true,
    ]);

    $modal = '[data-form-modal="status-page-form-modal"]';
    $trigger = 'a[data-form-modal-name="status-page-form-modal"][href$="/status-pages/' . $statusPage->getRouteKey() . '/edit"]';

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/status-pages/' . $statusPage->getRouteKey())
        ->assertCount($trigger, 1)
        ->click($trigger)
        ->waitForText(__('status_page.form.components'))
        ->assertVisible($modal)
        ->assertScript(<<<'JS'
function () {
    const formSurface = document.querySelector('[data-status-page-modal-form]');

    return formSurface !== null
        && ! formSurface.classList.contains('bg-white')
        && ! formSurface.classList.contains('shadow-md')
        && ! formSurface.classList.contains('rounded-lg')
        && ! formSurface.classList.contains('p-6');
}
JS, true)
        ->assertNoJavaScriptErrors();
});
