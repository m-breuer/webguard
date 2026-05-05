<?php

declare(strict_types=1);

namespace App\Console\Commands\Infrastructure;

use App\Services\InfrastructureHealthService;
use Illuminate\Console\Command;

class RecordInfrastructureHeartbeatCommand extends Command
{
    protected $signature = 'infrastructure:heartbeat';

    protected $description = 'Records a scheduler heartbeat for infrastructure health diagnostics.';

    public function handle(InfrastructureHealthService $infrastructureHealthService): int
    {
        $timestamp = $infrastructureHealthService->recordSchedulerHeartbeat();

        $this->components->info('Infrastructure heartbeat recorded at ' . $timestamp . '.');

        return Command::SUCCESS;
    }
}
