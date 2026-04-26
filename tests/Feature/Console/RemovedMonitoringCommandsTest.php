<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RemovedMonitoringCommandsTest extends TestCase
{
    public function test_removed_monitoring_commands_are_not_registered(): void
    {
        $commands = array_keys(Artisan::all());

        $this->assertNotContains('monitoring:create-guest', $commands);
        $this->assertNotContains('monitoring:delete', $commands);
    }
}
