<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Monitoring;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class MonitoringIndexReadModel
{
    /**
     * @param  LengthAwarePaginator<int, Monitoring>  $monitorings
     * @param  Collection<int, string>  $summaryMonitoringIds
     */
    public function __construct(
        public LengthAwarePaginator $monitorings,
        public Collection $summaryMonitoringIds,
        public int $total,
    ) {}
}
