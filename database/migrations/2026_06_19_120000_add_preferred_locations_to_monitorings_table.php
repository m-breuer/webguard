<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->json('preferred_locations')->nullable()->after('preferred_location');
        });

        DB::table('monitorings')
            ->whereNotNull('preferred_location')
            ->orderBy('id')
            ->eachById(function (object $monitoring): void {
                DB::table('monitorings')
                    ->where('id', $monitoring->id)
                    ->update([
                        'preferred_locations' => json_encode([(string) $monitoring->preferred_location], JSON_THROW_ON_ERROR),
                    ]);
            }, 100, 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropColumn('preferred_locations');
        });
    }
};
