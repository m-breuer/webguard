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
            $table->unsignedSmallInteger('failure_confirmation_threshold')
                ->default(2)
                ->change();
        });

        DB::table('monitorings')->update([
            'failure_confirmation_threshold' => 2,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('failure_confirmation_threshold')
                ->default(1)
                ->change();
        });
    }
};
