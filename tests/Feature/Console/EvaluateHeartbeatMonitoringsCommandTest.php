<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Jobs\EvaluateHeartbeatMonitoringsJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EvaluateHeartbeatMonitoringsCommandTest extends TestCase
{
    public function test_it_dispatches_heartbeat_evaluation_to_the_dedicated_redis_queue(): void
    {
        Queue::fake();

        $this->artisan('monitoring:evaluate-heartbeats')->assertSuccessful();

        $this->assertSame('redis', config('queue.connections.redis.driver'));
        $this->assertSame('heartbeat', config('monitoring.heartbeat_queue'));

        Queue::assertPushed(EvaluateHeartbeatMonitoringsJob::class, function (EvaluateHeartbeatMonitoringsJob $evaluateHeartbeatMonitoringsJob): bool {
            return $evaluateHeartbeatMonitoringsJob->connection === 'redis'
                && $evaluateHeartbeatMonitoringsJob->queue === 'heartbeat';
        });
    }
}
