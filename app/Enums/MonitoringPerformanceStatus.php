<?php

declare(strict_types=1);

namespace App\Enums;

enum MonitoringPerformanceStatus: string
{
    case NORMAL = 'normal';
    case DEGRADED = 'degraded';
}
