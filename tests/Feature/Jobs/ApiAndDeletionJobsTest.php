<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\DeleteUser;
use App\Jobs\LogApiUsage;
use App\Models\ApiLog;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ApiAndDeletionJobsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::factory()->create();
    }

    public function test_delete_user_job_deletes_user_and_owned_monitorings(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->for($user)->create();

        (new DeleteUser($user))->handle();

        $this->assertDatabaseMissing('monitorings', ['id' => $monitoring->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_log_api_usage_job_creates_api_log(): void
    {
        $user = User::factory()->create();

        (new LogApiUsage($user->id, 'api.v1.monitorings.index'))->handle();

        $this->assertDatabaseHas('api_logs', [
            'user_id' => $user->id,
            'route' => 'api.v1.monitorings.index',
        ]);
    }

    public function test_log_api_usage_job_logs_storage_failures(): void
    {
        Log::spy();

        (new LogApiUsage('missing-user', 'api.v1.monitorings.index'))->handle();

        $this->assertSame(0, ApiLog::query()->count());
        Log::shouldHaveReceived('error')->once();
    }
}
