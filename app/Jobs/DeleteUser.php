<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class DeleteUser
 *
 * This job is responsible for deleting a user and all their associated data.
 * It dispatches other jobs to handle the deletion of monitorings owned by the user.
 */
class DeleteUser implements ShouldQueue
{
    use \Illuminate\Foundation\Queue\Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->monitorings()->delete();

        $this->user->delete();
    }
}
