<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\MonitoringResponse;
use App\Models\MonitoringResponseArchived;
use App\Support\MonitoringResponseHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class MonitoringResponseHistoryTest extends TestCase
{
    public function test_query_for_end_date_uses_archived_rows_before_recent_window(): void
    {
        Date::setTestNow(Carbon::parse('2026-06-27 12:00:00'));

        $this->assertSame(
            MonitoringResponseArchived::class,
            MonitoringResponseHistory::queryForEndDate(Carbon::parse('2026-06-19 23:59:59'))->getModel()::class
        );
        $this->assertSame(
            MonitoringResponse::class,
            MonitoringResponseHistory::queryForEndDate(Carbon::parse('2026-06-20 00:00:00'))->getModel()::class
        );
    }

    public function test_grouping_and_sqlite_period_expression_are_stable(): void
    {
        $this->assertSame('%Y-%m-%d %H', MonitoringResponseHistory::groupingForDays(1));
        $this->assertSame('%Y-%m-%d', MonitoringResponseHistory::groupingForDays(30));
        $this->assertSame('%Y-%m', MonitoringResponseHistory::groupingForDays(31));
        $this->assertSame("strftime('%Y-%m-%d', checked_at)", MonitoringResponseHistory::periodExpression('checked_at', '%Y-%m-%d'));
    }
}
