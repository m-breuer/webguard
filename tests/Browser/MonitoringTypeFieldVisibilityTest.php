<?php

declare(strict_types=1);

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('shows only the monitoring type-specific fields on initial form load', function (): void {
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
        'api_key_hash' => 'browser-type-fields-instance-key',
        'is_active' => true,
    ]);
    $serverHealthMonitoring = Monitoring::factory()->for($user)->create([
        'type' => MonitoringType::SERVER_HEALTH,
        'preferred_location' => $instanceCode,
    ]);
    $heartbeatMonitoring = Monitoring::factory()->for($user)->create([
        'type' => MonitoringType::HEARTBEAT,
        'preferred_location' => $instanceCode,
    ]);
    $dnsMonitoring = Monitoring::factory()->for($user)->create([
        'type' => MonitoringType::DNS_RECORD,
        'preferred_location' => $instanceCode,
    ]);
    $pingMonitoring = Monitoring::factory()->for($user)->create([
        'type' => MonitoringType::PING,
        'preferred_location' => $instanceCode,
    ]);

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]');

    $assertFields = static function (bool $heartbeatVisible, bool $serverHealthVisible, bool $dnsVisible, bool $checkConfigurationVisible): string {
        $heartbeatHidden = $heartbeatVisible ? 'false' : 'true';
        $serverHealthHidden = $serverHealthVisible ? 'false' : 'true';
        $dnsHidden = $dnsVisible ? 'false' : 'true';
        $checkConfigurationHidden = $checkConfigurationVisible ? 'false' : 'true';

        return <<<JS
function () {
    const form = document.querySelector('[data-monitoring-type-form]');
    const heartbeatFields = form?.querySelector('[data-monitoring-type-fields="heartbeat"]:not(.border-dashed)');
    const heartbeatNotice = form?.querySelector('[data-monitoring-type-fields="heartbeat"].border-dashed');
    const serverHealthNotice = form?.querySelector('[data-monitoring-type-fields="server_health"].border-dashed');
    const serverHealthFields = form?.querySelector('[data-monitoring-type-fields="server_health"]:not(.border-dashed)');
    const dnsFields = form?.querySelector('[data-monitoring-type-fields="dns_record"]');
    const checkConfiguration = form?.querySelector('[data-monitoring-check-configuration]');

    return heartbeatFields instanceof HTMLElement
        && serverHealthFields instanceof HTMLElement
        && dnsFields instanceof HTMLElement
        && checkConfiguration instanceof HTMLDetailsElement
        && heartbeatFields.hidden === {$heartbeatHidden}
        && (heartbeatNotice === null || heartbeatNotice.hidden === {$heartbeatHidden})
        && (serverHealthNotice === null || serverHealthNotice.hidden === {$serverHealthHidden})
        && serverHealthFields.hidden === {$serverHealthHidden}
        && dnsFields.hidden === {$dnsHidden}
        && checkConfiguration.hidden === {$checkConfigurationHidden};
}
JS;
    };

    $webpage->navigate('/monitorings/create')
        ->waitForText(__('monitoring.form.sections.basic'))
        ->assertScript($assertFields(false, false, false, true), true)
        ->select('select[name="type"]', MonitoringType::HEARTBEAT->value)
        ->assertScript($assertFields(true, false, false, true), true)
        ->select('select[name="type"]', MonitoringType::SERVER_HEALTH->value)
        ->assertScript($assertFields(false, true, false, true), true)
        ->click('[data-monitoring-check-configuration] > summary')
        ->assertScript(<<<'JS'
function () {
    return document.querySelector('[data-monitoring-check-configuration]')?.open === true;
}
JS, true)
        ->select('select[name="type"]', MonitoringType::DNS_RECORD->value)
        ->assertScript($assertFields(false, false, true, true), true)
        ->select('select[name="type"]', MonitoringType::PING->value)
        ->assertScript($assertFields(false, false, false, false), true)
        ->navigate('/monitorings/' . $heartbeatMonitoring->getRouteKey() . '/edit')
        ->waitForText(__('monitoring.edit.title', ['monitoring' => $heartbeatMonitoring->name]))
        ->assertScript($assertFields(true, false, false, true), true)
        ->navigate('/monitorings/' . $serverHealthMonitoring->getRouteKey() . '/edit')
        ->waitForText(__('monitoring.edit.title', ['monitoring' => $serverHealthMonitoring->name]))
        ->assertScript($assertFields(false, true, false, true), true)
        ->navigate('/monitorings/' . $dnsMonitoring->getRouteKey() . '/edit')
        ->waitForText(__('monitoring.edit.title', ['monitoring' => $dnsMonitoring->name]))
        ->assertScript($assertFields(false, false, true, true), true)
        ->navigate('/monitorings/' . $pingMonitoring->getRouteKey() . '/edit')
        ->waitForText(__('monitoring.edit.title', ['monitoring' => $pingMonitoring->name]))
        ->assertScript($assertFields(false, false, false, false), true)
        ->assertNoJavaScriptErrors();
});
