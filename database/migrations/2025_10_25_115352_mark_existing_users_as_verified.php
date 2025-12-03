<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::whereNull('email_verified_at')->update([
            'email_verified_at' => DB::raw('CURRENT_TIMESTAMP'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data migration to backfill data, so the down method is intentionally left empty.
        // Reverting this could unintentionally un-verify users who have legitimately verified their email
        // after this migration was run.
    }
};
