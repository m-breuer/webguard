<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('status_pages', function (Blueprint $table): void {
            $table->string('slug')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('status_pages')
            ->whereNull('slug')
            ->update(['slug' => DB::raw('id')]);

        Schema::table('status_pages', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
        });
    }
};
