<?php

declare(strict_types=1);

use App\Enums\MaintenanceWindowRecurrence;
use App\Models\MaintenanceWindow;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('prefills the maintenance editor for one-off and recurring windows', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create(['monitoring_limit' => 10]);
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);
    $instanceCode = 'browser-maintenance-' . Str::lower(Str::random(8));
    ServerInstance::query()->create([
        'code' => $instanceCode,
        'api_key_hash' => 'browser-maintenance-instance-key',
        'is_active' => true,
    ]);
    $monitoring = Monitoring::factory()->for($user)->create([
        'name' => 'Maintenance browser monitor',
        'preferred_location' => $instanceCode,
        'maintenance_from' => '2026-07-03 10:00:00',
        'maintenance_until' => '2026-07-03 11:00:00',
    ]);
    $maintenanceWindow = MaintenanceWindow::query()->create([
        'monitoring_id' => $monitoring->id,
        'starts_at' => '2026-07-10 08:00:00',
        'duration_minutes' => 90,
        'recurrence' => MaintenanceWindowRecurrence::WEEKLY,
        'repeat_until' => '2026-12-31 22:59:59',
        'timezone' => 'Europe/Berlin',
        'enabled' => true,
    ]);

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/maintenance')
        ->waitForText('Maintenance browser monitor')
        ->assertCount('[data-maintenance-edit-one-off-window]', 1)
        ->assertCount('[data-maintenance-edit-recurring-window]', 1)
        ->click('[data-maintenance-edit-one-off-window]')
        ->assertScript(<<<JS
function () {
    const root = document.querySelector('[data-maintenance-editor-heading]')?.closest('[x-data]');
    const state = root ? window.Alpine[String.fromCharCode(36) + 'data'](root) : null;

    return state?.editing === true
        && state.mode === 'one_off'
        && state.scope === 'monitoring'
        && state.monitoringId === '{$monitoring->id}'
        && state.maintenanceFrom === '2026-07-03T08:00'
        && state.maintenanceUntil === '2026-07-03T09:00';
}
JS, true);

    $webpage->script("document.querySelector('[data-maintenance-cancel-edit]')?.click();");
    $webpage->script("document.querySelector('[data-maintenance-edit-recurring-window]')?.click();");

    $webpage
        ->assertScript(<<<JS
function () {
    const root = document.querySelector('[data-maintenance-editor-heading]')?.closest('[x-data]');
    const state = root ? window.Alpine[String.fromCharCode(36) + 'data'](root) : null;

    return state?.editing === true
        && state.mode === 'recurring'
        && state.scope === 'monitoring'
        && state.editingRecurringWindowId === '{$maintenanceWindow->id}'
        && state.monitoringId === '{$monitoring->id}'
        && state.recurringStartsAt === '2026-07-10T08:00'
        && state.recurrence === 'weekly'
        && state.recurringDurationMinutes === '90'
        && state.recurringRepeatUntil === '2026-12-31'
        && state.recurringTimezone === 'Europe/Berlin';
}
JS, true)
        ->assertNoJavaScriptErrors();
});
