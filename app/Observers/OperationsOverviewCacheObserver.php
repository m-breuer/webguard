<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Monitoring;
use App\Services\MonitoringStatsCache;
use App\Services\OperationsOverviewCache;
use Illuminate\Database\Eloquent\Model;

final class OperationsOverviewCacheObserver
{
    public function created(Model $model): void
    {
        $this->flush($model);
    }

    public function updated(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    public function restored(Model $model): void
    {
        $this->flush($model);
    }

    private function flush(Model $model): void
    {
        resolve(OperationsOverviewCache::class)->flush();

        if ($model instanceof Monitoring) {
            resolve(MonitoringStatsCache::class)->flush($model);
        }
    }
}
