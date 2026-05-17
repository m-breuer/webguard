<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class MonitoringResponseTimesPayload implements JsonSerializable
{
    /**
     * @param  Collection<int, MonitoringResponseTimePointPayload>  $data
     */
    public function __construct(
        public Collection $data,
        public MonitoringResponseTimeAggregatePayload $aggregated
    ) {}

    /**
     * @return array{
     *     data: Collection<int, array{date: string, avg: float|int|string|null, min: float|int|string|null, max: float|int|string|null}>,
     *     aggregated: array{avg: float|int|null, min: float|int|string|null, max: float|int|string|null}
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data->map(
                static fn (MonitoringResponseTimePointPayload $monitoringResponseTimePointPayload): array => $monitoringResponseTimePointPayload->toArray()
            ),
            'aggregated' => $this->aggregated->toArray(),
        ];
    }

    /**
     * @return array{
     *     data: Collection<int, array{date: string, avg: float|int|string|null, min: float|int|string|null, max: float|int|string|null}>,
     *     aggregated: array{avg: float|int|null, min: float|int|string|null, max: float|int|string|null}
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
