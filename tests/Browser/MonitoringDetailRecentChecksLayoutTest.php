<?php

declare(strict_types=1);

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('keeps monitoring detail content readable before the desktop side rail breakpoint', function (): void {
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
        'target' => 'https://example.com/health',
    ]);
    MonitoringResponse::query()->create([
        'monitoring_id' => $monitoring->id,
        'status' => MonitoringStatus::UP,
        'http_status_code' => 200,
        'response_time' => 14,
    ]);

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/monitorings/' . $monitoring->id)
        ->resize(1068, 1368)
        ->waitForText(__('monitoring.detail.checks.sources.live'));

    $webpage->assertScript(<<<'JS'
function () {
    const layout = document.querySelector('[data-monitoring-detail-layout]');
    const primary = layout?.children[0];
    const rail = layout?.children[1];
    const cards = document.querySelector('[data-monitoring-primary-cards]');
    const row = document.querySelector('[data-recent-check-row]');
    const result = row?.querySelector('[data-recent-check-result]');
    const status = row?.querySelector('[data-recent-check-status]');

    if (!layout || !primary || !rail || !cards || !row || !result || !status) {
        return false;
    }

    const primaryRect = primary.getBoundingClientRect();
    const railRect = rail.getBoundingClientRect();
    const cardRects = [...cards.children].slice(0, 2).map((card) => card.getBoundingClientRect());

    if (railRect.top < primaryRect.bottom - 1 || cardRects.length < 2 || cardRects[0].width < 300) {
        return false;
    }

    const resultRect = result.getBoundingClientRect();
    const statusRect = status.getBoundingClientRect();

    const statusFitsAfterResult = resultRect.bottom <= statusRect.top + 1;
    const statusFitsBesideResult = resultRect.right <= statusRect.left + 1;

    return (statusFitsAfterResult || statusFitsBesideResult)
        && row.getBoundingClientRect().right >= statusRect.right;
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
