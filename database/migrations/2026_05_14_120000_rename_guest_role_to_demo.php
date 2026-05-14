<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! DB::table('users')->where('email', 'demo@example.com')->exists()) {
            DB::table('users')
                ->where('email', 'guest@example.com')
                ->update([
                    'name' => 'Demo User',
                    'email' => 'demo@example.com',
                ]);
        }

        DB::table('users')
            ->where('role', 'guest')
            ->update([
                'name' => 'Demo User',
                'role' => UserRole::DEMO->value,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! DB::table('users')->where('email', 'guest@example.com')->exists()) {
            DB::table('users')
                ->where('email', 'demo@example.com')
                ->update([
                    'name' => 'Guest User',
                    'email' => 'guest@example.com',
                ]);
        }

        DB::table('users')
            ->where('role', UserRole::DEMO->value)
            ->update([
                'name' => 'Guest User',
                'role' => 'guest',
            ]);
    }
};
