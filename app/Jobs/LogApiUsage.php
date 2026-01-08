<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ApiLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job to asynchronously log API usage for a given user.
 *
 * This job stores a new entry in the api_logs table, which can be used
 * for monitoring, analytics, or billing purposes. It is designed to be
 * dispatched from middleware or controller logic that detects API activity.
 */
class LogApiUsage implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use SerializesModels;

    /**
     * The ID of the user whose API usage is being logged.
     */
    protected string $userId;

    /**
     * The API route that was accessed (optional).
     */
    protected ?string $route = null;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            ApiLog::query()->create([
                'user_id' => $this->userId,
                'route' => $this->route,
            ]);
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
