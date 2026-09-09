<?php

declare(strict_types=1);

namespace App\Console\Commands\Instances;

use App\Models\InstanceCallbackIdempotency;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Delete expired instance callback idempotency records.')]
#[Signature('instances:prune-callback-idempotency')]
class PruneCallbackIdempotencyCommand extends Command
{
    public function handle(): int
    {
        $deletedCount = 0;

        InstanceCallbackIdempotency::query()
            ->where('expires_at', '<', now())
            ->chunkById(250, function ($records) use (&$deletedCount): void {
                $deletedCount += $records->count();
                InstanceCallbackIdempotency::query()->whereIn('id', $records->pluck('id'))->delete();
            });

        $this->info("Deleted {$deletedCount} expired instance callback idempotency records.");

        return Command::SUCCESS;
    }
}
