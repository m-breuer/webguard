<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\OperationsOverviewCache;
use Illuminate\Database\Eloquent\Model;

final class OperationsOverviewCacheObserver
{
    public function created(Model $model): void
    {
        $this->flush();
    }

    public function updated(Model $model): void
    {
        $this->flush();
    }

    public function deleted(Model $model): void
    {
        $this->flush();
    }

    public function restored(Model $model): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        resolve(OperationsOverviewCache::class)->flush();
    }
}
