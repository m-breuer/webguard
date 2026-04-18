<?php

declare(strict_types=1);

namespace App\Console\Commands\Monitoring;

use App\Jobs\EvaluateHeartbeatMonitoringsJob;
use Illuminate\Console\Command;

class EvaluateHeartbeatMonitoringsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'monitoring:evaluate-heartbeats';

    /**
     * @var string
     */
    protected $description = 'Dispatches heartbeat evaluation to the dedicated heartbeat queue.';

    public function handle(): int
    {
        EvaluateHeartbeatMonitoringsJob::dispatch();

        $this->components->info('Heartbeat evaluation dispatched to the heartbeat queue.');

        return Command::SUCCESS;
    }
}
