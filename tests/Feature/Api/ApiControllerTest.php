<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_correct_interval_in_the_status_endpoint(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $testResponse = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertOk();
        $testResponse->assertJson(['interval' => 300]);
    }

    public function test_returns_status_metadata_and_translation_keys_in_status_endpoint(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN,
            'http_status_code' => 503,
            'response_time' => 220.0,
        ]);

        $testResponse = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/status');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('status_code', 503);
        $testResponse->assertJsonPath('status_identifier', 'status.server_error');
        $testResponse->assertJsonPath('status_key', 'notifications.status.server_error');
        $testResponse->assertJsonPath('monitoring.name', $monitoring->name);
        $testResponse->assertJsonPath('monitoring.target', $monitoring->target);
    }

    public function test_results_endpoint_exposes_http_status_code_for_historical_entries(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $liveCheckedAt = now()->subMinutes(3);
        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 204,
            'response_time' => 123.4,
            'created_at' => $liveCheckedAt,
            'updated_at' => $liveCheckedAt,
        ]);

        $archivedCheckedAt = now()->subDay();
        DB::table('monitoring_response_archived')->insert([
            'id' => (string) Str::ulid(),
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
            'response_time' => 222.0,
            'created_at' => $archivedCheckedAt,
            'updated_at' => $archivedCheckedAt,
        ]);

        $testResponse = $this->actingAs($user)->getJson('/api/monitorings/' . $monitoring->id . '/checks?limit=10');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('meta.count', 2);
        $testResponse->assertJsonPath('data.0.http_status_code', 204);
        $testResponse->assertJsonPath('data.0.status_identifier', 'status.success');
        $testResponse->assertJsonPath('data.1.http_status_code', 503);
        $testResponse->assertJsonPath('data.1.status_identifier', 'status.server_error');
    }

    public function test_results_endpoint_skips_archived_history_query_when_live_rows_fill_the_page(): void
    {
        Date::setTestNow('2026-04-06 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        foreach (range(1, 12) as $minuteOffset) {
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

        DB::table('monitoring_response_archived')->insert([
            'id' => (string) Str::ulid(),
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
            'response_time' => 222.0,
            'created_at' => Date::now()->subDays(10),
            'updated_at' => Date::now()->subDays(10),
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $testResponse = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/checks?limit=10');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('meta.count', 10);
        $testResponse->assertJsonCount(10, 'data');
        $testResponse->assertJsonMissingPath('data.10');
        $testResponse->assertJsonPath('data.0.source', 'live');

        $historyQueries = $this->historyQueries();

        $this->assertCount(1, $historyQueries);
        $this->assertStringContainsString('monitoring_response_results', $historyQueries[0]);
        $this->assertStringNotContainsString('monitoring_response_archived', $historyQueries[0]);
    }

    public function test_results_endpoint_uses_only_live_history_for_recent_day_filters(): void
    {
        Date::setTestNow('2026-04-06 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        $recentCheck = Date::now()->subDay();
        MonitoringResponse::query()->forceCreate([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 204,
            'response_time' => 150.0,
            'created_at' => $recentCheck,
            'updated_at' => $recentCheck,
        ]);

        DB::table('monitoring_response_archived')->insert([
            'id' => (string) Str::ulid(),
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::DOWN->value,
            'http_status_code' => 503,
            'response_time' => 250.0,
            'created_at' => Date::now()->subDays(14),
            'updated_at' => Date::now()->subDays(14),
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $testResponse = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/checks?days=2&limit=10');

        $testResponse->assertOk();
        $testResponse->assertJsonPath('meta.count', 1);
        $testResponse->assertJsonPath('data.0.source', 'live');

        $historyQueries = $this->historyQueries();

        $this->assertCount(1, $historyQueries);
        $this->assertStringContainsString('monitoring_response_results', $historyQueries[0]);
        $this->assertStringNotContainsString('monitoring_response_archived', $historyQueries[0]);
    }

    public function test_results_endpoint_supports_offset_pagination_for_recent_checks(): void
    {
        Date::setTestNow('2026-04-06 12:00:00');

        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        foreach (range(1, 8) as $minuteOffset) {
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

        $testResponse = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/checks?days=1&limit=5');

        $testResponse->assertOk();
        $testResponse->assertJsonCount(5, 'data');
        $testResponse->assertJsonPath('meta.count', 5);
        $testResponse->assertJsonPath('meta.offset', 0);
        $testResponse->assertJsonPath('meta.has_more', true);
        $testResponse->assertJsonPath('meta.next_offset', 5);
        $this->assertSame(101.0, (float) $testResponse->json('data.0.response_time'));

        $secondPageResponse = $this->actingAs($user)->getJson('/api/v1/monitorings/' . $monitoring->id . '/checks?days=1&limit=5&offset=5');

        $secondPageResponse->assertOk();
        $secondPageResponse->assertJsonCount(3, 'data');
        $secondPageResponse->assertJsonPath('meta.count', 3);
        $secondPageResponse->assertJsonPath('meta.offset', 5);
        $secondPageResponse->assertJsonPath('meta.has_more', false);
        $secondPageResponse->assertJsonPath('meta.next_offset', null);
        $this->assertSame(106.0, (float) $secondPageResponse->json('data.0.response_time'));
    }

    public function test_history_response_tables_have_indexes_for_timeline_pagination(): void
    {
        $this->assertTableHasIndexColumns(
            'monitoring_response_results',
            ['monitoring_id', 'created_at', 'id']
        );

        $this->assertTableHasIndexColumns(
            'monitoring_response_archived',
            ['monitoring_id', 'created_at', 'id']
        );
    }

    /**
     * @return list<string>
     */
    private function historyQueries(): array
    {
        return collect(DB::getQueryLog())
            ->pluck('query')
            ->map(static fn (string $query): string => mb_strtolower($query))
            ->filter(static fn (string $query): bool => str_contains($query, 'monitoring_response_results')
                || str_contains($query, 'monitoring_response_archived'))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertTableHasIndexColumns(string $table, array $columns): void
    {
        $indexColumns = collect(Schema::getIndexes($table))
            ->map(static fn (array $index): array => $index['columns'])
            ->values();

        $this->assertTrue(
            $indexColumns->contains($columns),
            sprintf(
                'Expected %s to have an index on (%s). Existing index columns: %s',
                $table,
                implode(', ', $columns),
                $indexColumns->map(static fn (array $indexedColumns): string => '(' . implode(', ', $indexedColumns) . ')')->implode(', ')
            )
        );
    }
}
