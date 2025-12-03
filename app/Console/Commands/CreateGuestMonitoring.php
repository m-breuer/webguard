<?php

namespace App\Console\Commands;

use App\Enums\MonitoringType;
use App\Enums\ServerInstance;
use App\Enums\UserRole;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Console\Command;

class CreateGuestMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:guest-monitoring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new monitoring for the guest user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $guestUser = User::query()->where('role', UserRole::GUEST)->first();

        if (! $guestUser) {
            $this->error('No guest user found. Please create a guest user first.');

            return Command::FAILURE;
        }

        $name = $this->ask('Enter the name for the monitoring');
        $target = $this->ask('Enter the target for the monitoring');

        $monitoring = Monitoring::query()->create([
            'user_id' => $guestUser->id,
            'type' => MonitoringType::HTTP,
            'name' => $name,
            'target' => $target,
            'timeout' => 5,
            'preferred_location' => ServerInstance::DE_1,
            'public_label_enabled' => true,
            'email_notification_on_failure' => false,
        ]);

        $this->info("Monitoring '{$monitoring->name}' created successfully for guest user.");

        return Command::SUCCESS;
    }
}
