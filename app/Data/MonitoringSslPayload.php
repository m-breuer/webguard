<?php

declare(strict_types=1);

namespace App\Data;

use JsonSerializable;

final readonly class MonitoringSslPayload implements JsonSerializable
{
    public function __construct(
        public ?bool $valid,
        public ?string $expiration,
        public ?string $issuer,
        public ?string $issueDate
    ) {}

    /**
     * @return array{valid: bool|null, expiration: string|null, issuer: string|null, issue_date: string|null}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'expiration' => $this->expiration,
            'issuer' => $this->issuer,
            'issue_date' => $this->issueDate,
        ];
    }

    /**
     * @return array{valid: bool|null, expiration: string|null, issuer: string|null, issue_date: string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
