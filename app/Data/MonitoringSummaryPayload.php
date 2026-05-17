<?php

declare(strict_types=1);

namespace App\Data;

use JsonSerializable;

final readonly class MonitoringSummaryPayload implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $target,
        public string $type
    ) {}

    /**
     * @return array{name: string, target: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'target' => $this->target,
            'type' => $this->type,
        ];
    }

    /**
     * @return array{name: string, target: string, type: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
