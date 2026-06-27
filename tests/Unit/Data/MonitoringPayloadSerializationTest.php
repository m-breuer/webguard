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
        $monitoringIncidentPayload = new MonitoringIncidentPayload(
            downAt: '2026-06-27T08:00:00+00:00',
            upAt: null,
        );

        $this->assertSame([
            'down_at' => '2026-06-27T08:00:00+00:00',
            'up_at' => null,
        ], $monitoringIncidentPayload->toArray());
        $this->assertSame($monitoringIncidentPayload->toArray(), $monitoringIncidentPayload->jsonSerialize());
    }

    public function test_uptime_calendar_payload_serializes_nested_months_and_days(): void
    {
        $monitoringUptimeCalendarDayPayload = new MonitoringUptimeCalendarDayPayload('2026-06-27', 99.95);
        $monitoringUptimeCalendarMonthPayload = new MonitoringUptimeCalendarMonthPayload(new Collection([$monitoringUptimeCalendarDayPayload]), 99.95);
        $monitoringUptimeCalendarPayload = new MonitoringUptimeCalendarPayload(new Collection([
            '2026-06' => $monitoringUptimeCalendarMonthPayload,
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
        ], $monitoringUptimeCalendarDayPayload->toArray());
        $this->assertSame($monitoringUptimeCalendarDayPayload->toArray(), $monitoringUptimeCalendarDayPayload->jsonSerialize());
        $this->assertSame($expected['2026-06'], $monitoringUptimeCalendarMonthPayload->jsonSerialize());
        $this->assertSame($expected, $monitoringUptimeCalendarPayload->toArray());
        $this->assertSame($expected, $monitoringUptimeCalendarPayload->jsonSerialize());
    }
}
