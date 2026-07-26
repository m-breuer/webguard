<?php

declare(strict_types=1);

namespace Tests\Unit\Data;

use App\Data\MonitoringServiceReadModel;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonitoringServiceReadModelTest extends TestCase
{
    public function test_it_exposes_locale_neutral_service_values_with_stable_timestamps(): void
    {
        $checkedAt = Carbon::parse('2026-07-26 12:00:00', 'UTC');
        $monitoring = new Monitoring();
        $monitoring->forceFill([
            'id' => 'monitoring-123',
            'name' => 'Checkout API',
            'target' => 'https://checkout.example.test',
            'type' => MonitoringType::HTTP,
        ]);
        $response = new MonitoringResponse();
        $response->forceFill([
            'status' => MonitoringStatus::UP,
            'response_time' => 123.4,
            'created_at' => $checkedAt,
        ]);
        $monitoring->setRelation('latestResponseResult', $response);
        $monitoring->setRelation('latestIncident', null);
        $monitoring->setRelation('groups', new EloquentCollection());

        $service = MonitoringServiceReadModel::fromMonitoring($monitoring, MonitoringStatus::UP->value);

        $this->assertSame([
            'id' => 'monitoring-123',
            'name' => 'Checkout API',
            'target' => 'https://checkout.example.test',
            'type' => 'http',
            'group_name' => null,
            'status' => 'up',
            'open_incident' => false,
            'last_checked_at' => $response->created_at->toIso8601String(),
            'response_time_ms' => 123.4,
        ], $service->toArray());
    }
}
