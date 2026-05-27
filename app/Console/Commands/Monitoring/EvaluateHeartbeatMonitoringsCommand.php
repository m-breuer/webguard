<?php

declare(strict_types=1);

namespace App\Console\Commands\Monitoring;

use App\Jobs\EvaluateHeartbeatMonitoringsJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Dispatches heartbeat evaluation to the dedicated heartbeat queue.')]
#[Signature('monitoring:evaluate-heartbeats')]
class EvaluateHeartbeatMonitoringsCommand extends Command
{
    public function handle(): int
    {
        dispatch(new EvaluateHeartbeatMonitoringsJob());

        $this->components->info('Heartbeat evaluation dispatched to the heartbeat queue.');

        return Command::SUCCESS;
    }
}
