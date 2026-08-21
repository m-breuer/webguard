<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\MonitoringSslResult;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class PublicLabelPageTest extends TestCase
{
    public function test_public_label_page_uses_status_ssl_uptime_calendar_and_recent_incidents_layout(): void
    {
        Date::setTestNow('2026-05-03 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Primary API',
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
            'status' => MonitoringLifecycleStatus::ACTIVE,
            'public_label_enabled' => true,
            'created_at' => Date::now()->subDays(100),
        ]);

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 120,
            'created_at' => Date::now()->subMinutes(5),
            'updated_at' => Date::now()->subMinutes(5),
        ]);

        MonitoringSslResult::query()->create([
            'monitoring_id' => $monitoring->id,
            'is_valid' => true,
            'issuer' => 'Example CA',
            'issued_at' => Date::now()->subDays(30),
            'expires_at' => Date::now()->addDays(60),
        ]);

        $oldestVisibleIncident = null;
        $hiddenIncident = null;
        foreach (range(1, 11) as $hoursAgo) {
            $downAt = Date::now()->subHours($hoursAgo);
            $incident = Incident::query()->create([
                'monitoring_id' => $monitoring->id,
                'down_at' => $downAt,
                'up_at' => $downAt->copy()->addMinutes(10),
            ]);

            if ($hoursAgo === 10) {
                $oldestVisibleIncident = $incident;
            }

            if ($hoursAgo === 11) {
                $hiddenIncident = $incident;
            }
        }

        $testResponse = $this->get(route('public-status-pages.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText('Primary API');
        $testResponse->assertSeeInOrder([
            __('monitoring.public_label.current_status'),
            __('monitoring.detail.ssl.heading'),
            trans_choice('monitoring.public_label.range_days', 7, ['days' => 7]),
            trans_choice('monitoring.public_label.range_days', 30, ['days' => 30]),
            trans_choice('monitoring.public_label.range_days', 90, ['days' => 90]),
            __('monitoring.detail.calendar.heading'),
            __('monitoring.public_label.recent_incidents'),
        ]);
        $testResponse->assertSeeHtml('class="mx-auto max-w-5xl space-y-6"');
        $testResponse->assertSeeHtml('class="grid grid-cols-1 gap-4 md:grid-cols-3"');
        $testResponse->assertSeeHtml('id="public-current-status"');
        $testResponse->assertSeeHtml('id="public-monitoring-component-' . $monitoring->id . '"');
        $testResponse->assertSeeHtml('id="public-uptime-card-7"');
        $testResponse->assertSeeHtml('id="public-uptime-card-30"');
        $testResponse->assertSeeHtml('id="public-uptime-card-90"');
        $testResponse->assertSeeHtml('id="public-incidents"');
        $testResponse->assertDontSeeHtml('id="incidents-range"');
        $testResponse->assertSeeText(Date::parse($oldestVisibleIncident->down_at)->locale(app()->getLocale())->isoFormat('L LT'));
        $testResponse->assertDontSeeText(Date::parse($hiddenIncident->down_at)->locale(app()->getLocale())->isoFormat('L LT'));
    }

    public function test_legacy_public_label_url_redirects_to_the_canonical_status_url(): void
    {
        Package::factory()->create();
        $monitoring = Monitoring::factory()->for(User::factory())->create([
            'public_label_enabled' => true,
        ]);

        $this->get(route('legacy-public-label', $monitoring))
            ->assertStatus(301)
            ->assertRedirect(route('public-status-pages.show', $monitoring));

        $this->post(route('legacy-public-label.subscribers.store', $monitoring))
            ->assertStatus(307)
            ->assertRedirect(route('public-status-pages.subscribers.store', $monitoring));
    }
}
