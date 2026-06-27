<?php

declare(strict_types=1);

namespace Tests\Unit\Data;

use App\Data\MonitoringIncidentPayload;
use App\Data\MonitoringUptimeCalendarDayPayload;
use App\Data\MonitoringUptimeCalendarMonthPayload;
use App\Data\MonitoringUptimeCalendarPayload;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MonitoringPayloadSerializationTest extends TestCase
{
    public function test_incident_payload_serializes_to_api_shape(): void
    {
        $payload = new MonitoringIncidentPayload(
            downAt: '2026-06-27T08:00:00+00:00',
            upAt: null,
        );

        $this->assertSame([
            'down_at' => '2026-06-27T08:00:00+00:00',
            'up_at' => null,
        ], $payload->toArray());
        $this->assertSame($payload->toArray(), $payload->jsonSerialize());
    }

    public function test_uptime_calendar_payload_serializes_nested_months_and_days(): void
    {
        $day = new MonitoringUptimeCalendarDayPayload('2026-06-27', 99.95);
        $month = new MonitoringUptimeCalendarMonthPayload(new Collection([$day]), 99.95);
        $payload = new MonitoringUptimeCalendarPayload(new Collection([
            '2026-06' => $month,
        ]));

        $expected = [
            '2026-06' => [
                'days' => [
                    [
                        'date' => '2026-06-27',
                        'uptime_percentage' => 99.95,
                    ],
                ],
                'monthly_average_uptime' => 99.95,
            ],
        ];

        $this->assertSame([
            'date' => '2026-06-27',
            'uptime_percentage' => 99.95,
        ], $day->toArray());
        $this->assertSame($day->toArray(), $day->jsonSerialize());
        $this->assertSame($expected['2026-06'], $month->jsonSerialize());
        $this->assertSame($expected, $payload->toArray());
        $this->assertSame($expected, $payload->jsonSerialize());
    }
}
