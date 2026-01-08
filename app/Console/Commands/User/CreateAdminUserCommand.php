<?php

declare(strict_types=1);

namespace App\Console\Commands\User;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user with a default password.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        if (! $email) {
            $email = $this->ask('Enter the email for the admin user');
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('A user with this email already exists.');

            return Command::FAILURE;
        }

        $model = User::query()->create([
            'name' => 'Admin User',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
            'terms_accepted_at' => now(),
            'package_id' => Package::query()->latest()->first()->id,
        ]);

        $this->info("Admin user '{$model->email}' created successfully with password 'password'.");

        return Command::SUCCESS;
    }
}
