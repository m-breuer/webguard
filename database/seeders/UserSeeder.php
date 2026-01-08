<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'breuer.marcel@outlook.com')->first();

        if ($user) {
            $user->delete();
        }

        $user = User::factory()->create([
            'name' => 'Marcel Breuer',
            'password' => 'password',
            'email' => 'breuer.marcel@outlook.com',
            'role' => UserRole::ADMIN->value,
        ]);

        User::factory()->create([
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'password' => 'password',
            'role' => UserRole::GUEST->value,
        ]);
    }
}
