<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MonitoringType;
use App\Models\Monitoring;

final class MonitoringCheckIntervalService
{
    public function secondsFor(Monitoring $monitoring): int
    {
        return $this->secondsForType($monitoring->type);
    }

    public function secondsForType(MonitoringType|string|null $type): int
    {
        if (is_string($type)) {
            $type = MonitoringType::tryFrom($type);
        }

        return match ($type) {
            MonitoringType::HTTP, MonitoringType::KEYWORD => $this->websiteSeconds(),
            default => $this->defaultSeconds(),
        };
    }

    public function defaultSeconds(): int
    {
        return max(60, (int) config('monitoring.default_interval_minutes', 5) * 60);
    }

    private function websiteSeconds(): int
    {
        return max(60, (int) config('monitoring.website_interval_minutes', 15) * 60);
    }
}
