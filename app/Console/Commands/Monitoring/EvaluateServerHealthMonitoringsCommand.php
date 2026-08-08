<?php

declare(strict_types=1);

namespace App\Console\Commands\Monitoring;

use App\Jobs\EvaluateServerHealthMonitoringsJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Dispatches server health stale-report evaluation to the dedicated heartbeat queue.')]
#[Signature('monitoring:evaluate-server-health')]
class EvaluateServerHealthMonitoringsCommand extends Command
{
    public function handle(): int
    {
        dispatch(new EvaluateServerHealthMonitoringsJob());

        $this->components->info('Server health evaluation dispatched to the dedicated heartbeat queue.');

        return Command::SUCCESS;
    }
}
