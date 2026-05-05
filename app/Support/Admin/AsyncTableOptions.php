<?php

declare(strict_types=1);

namespace App\Support\Admin;

final readonly class AsyncTableOptions
{
    public function __construct(
        public string $sort,
        public string $direction,
        public int $perPage,
    ) {}
}
