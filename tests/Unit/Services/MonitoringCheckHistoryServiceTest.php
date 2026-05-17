<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use App\Services\MonitoringCheckHistoryService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonitoringCheckHistoryServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_history_formats_live_and_archived_rows_with_status_metadata(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $liveCheckedAt = Date::now()->subMinutes(5);
        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => 123.4,
            'server_health_metrics' => [
                'cpu_usage_percent' => 42.5,
                'ram_usage_percent' => 68.2,
            ],
            'created_at' => $liveCheckedAt,
            'updated_at' => $liveCheckedAt,
        ]);

        $archivedCheckedAt = Date::now()->subDays(10);
        $this->insertArchivedResponse($monitoring, $archivedCheckedAt);

        $history = resolve(MonitoringCheckHistoryService::class)->getHistory(
            $monitoring,
            null,
            Date::now()->endOfDay(),
            10,
            0
        );

        $this->assertFalse($history['has_more']);
        $this->assertNull($history['next_offset']);
        $this->assertCount(2, $history['data']);
        $this->assertSame('live', $history['data'][0]['source']);
        $this->assertSame('status.success', $history['data'][0]['status_identifier']);
        $this->assertSame(42.5, $history['data'][0]['server_health_metrics']['cpu_usage_percent']);
        $this->assertSame('archived', $history['data'][1]['source']);
        $this->assertSame('status.server_error', $history['data'][1]['status_identifier']);
    }

    public function test_history_returns_next_offset_when_a_page_has_more_rows(): void
    {
        Date::setTestNow('2026-04-12 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        foreach (range(1, 3) as $minuteOffset) {
            $checkedAt = Date::now()->subMinutes($minuteOffset);

            MonitoringResponse::query()->forceCreate([
                'monitoring_id' => $monitoring->id,
                'status' => MonitoringStatus::UP,
                'http_status_code' => 200,
                'response_time' => 100 + $minuteOffset,
                'created_at' => $checkedAt,
                'updated_at' => $checkedAt,
            ]);
        }

        $history = resolve(MonitoringCheckHistoryService::class)->getHistory(
            $monitoring,
            Date::now()->subDay()->startOfDay(),
            Date::now()->endOfDay(),
            2,
            0
        );

        $this->assertTrue($history['has_more']);
        $this->assertSame(2, $history['next_offset']);
        $this->assertCount(2, $history['data']);
    }

    private function insertArchivedResponse(Monitoring $monitoring, mixed $checkedAt): void
    {
        $monitoring->archivedResponseResults()->getModel()->newQuery()->insert([
            'id' => (string) Str::ulid(),
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
            'response_time' => 222.0,
            'server_health_metrics' => null,
            'created_at' => $checkedAt,
            'updated_at' => $checkedAt,
        ]);
    }
}
