<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringStatus;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitoringCardDataApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_data_endpoint_batches_monitoring_status_and_heatmap_queries(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();

        $monitorings = Monitoring::factory()->count(3)->for($user)->create();

        foreach ($monitorings as $index => $monitoring) {
            foreach (range(0, 2) as $hourOffset) {
                $checkedAt = Date::now()->subHours($hourOffset)->subMinutes($index + 1);

                MonitoringResponse::query()->create([
                    'monitoring_id' => $monitoring->id,
                    'status' => $index === 1 ? MonitoringStatus::DOWN : MonitoringStatus::UP,
                    'http_status_code' => $index === 1 ? 503 : 200,
                    'response_time' => 120.0 + $index,
                    'created_at' => $checkedAt,
                    'updated_at' => $checkedAt,
                ]);
            }
        }

        Incident::query()->create([
            'monitoring_id' => $monitorings[1]->id,
            'down_at' => Date::now()->subHours(2),
            'up_at' => null,
            'created_at' => Date::now()->subHours(2),
            'updated_at' => Date::now()->subHours(2),
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach ($monitorings as $monitoring) {
            $this->actingAs($user)->getJson('/api/monitorings/' . $monitoring->id . '/status')->assertOk();
            $this->actingAs($user)->getJson('/api/monitorings/' . $monitoring->id . '/heatmap')->assertOk();
        }

        $legacySelectCount = $this->selectQueryCount();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $testResponse = $this->actingAs($user)->getJson('/api/monitorings/card-data?' . http_build_query([
            'ids' => $monitorings->pluck('id')->all(),
        ]));

        $testResponse->assertOk();
        $testResponse->assertJsonPath('data.' . $monitorings[0]->id . '.status', MonitoringStatus::UP->value);
        $testResponse->assertJsonPath('data.' . $monitorings[1]->id . '.status', MonitoringStatus::DOWN->value);
        $testResponse->assertJsonCount(24, 'data.' . $monitorings[2]->id . '.heatmap');

        $selectCount = $this->selectQueryCount();

        $this->assertGreaterThan($selectCount, $legacySelectCount);
        $this->assertLessThanOrEqual(4, $selectCount, (string) collect(DB::getQueryLog())->pluck('query')->implode(PHP_EOL));
    }

    public function test_card_data_endpoint_returns_only_requested_monitorings_for_the_authenticated_user(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedMonitoring = Monitoring::factory()->for($user)->create();
        $foreignMonitoring = Monitoring::factory()->for($otherUser)->create();

        $testResponse = $this->actingAs($user)->getJson('/api/monitorings/card-data?' . http_build_query([
            'ids' => [$ownedMonitoring->id, $foreignMonitoring->id],
        ]));

        $testResponse->assertOk();
        $testResponse->assertJsonPath('data.' . $ownedMonitoring->id . '.heatmap.0.uptime', 0);
        $testResponse->assertJsonMissingPath('data.' . $foreignMonitoring->id);
    }

    private function selectQueryCount(): int
    {
        return collect(DB::getQueryLog())
            ->filter(static fn (array $entry): bool => str_starts_with(mb_strtolower($entry['query']), 'select'))
            ->count();
    }
}
