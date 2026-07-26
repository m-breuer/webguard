<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\OperationsOverviewCache;

final class OperationsOverviewCacheObserver
{
    public function created(): void
    {
        $this->flush();
    }

    public function updated(): void
    {
        $this->flush();
    }

    public function deleted(): void
    {
        $this->flush();
    }

    public function restored(): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        resolve(OperationsOverviewCache::class)->flush();
    }
}
