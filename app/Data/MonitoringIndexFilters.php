<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\MonitoringLifecycleStatus;

final readonly class MonitoringIndexFilters
{
    /**
     * @param  list<string>  $types
     * @param  list<string>  $healthStatuses
     */
    public function __construct(
        public ?string $search,
        public array $types,
        public array $healthStatuses,
        public ?MonitoringLifecycleStatus $lifecycleStatus,
        public ?string $groupId,
        public ?string $teamId,
        public ?string $ownership,
        public bool $onlyActiveMaintenance,
        public ?string $sort,
    ) {}

    public function hasActiveFilters(): bool
    {
        return $this->search !== null
            || $this->types !== []
            || $this->healthStatuses !== []
            || $this->lifecycleStatus !== null
            || $this->groupId !== null
            || $this->teamId !== null
            || $this->ownership !== null
            || $this->onlyActiveMaintenance;
    }
}
