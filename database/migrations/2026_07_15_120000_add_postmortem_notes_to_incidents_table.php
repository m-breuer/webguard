<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->text('problem_description')->nullable()->after('affected_locations');
            $table->text('resolution_description')->nullable()->after('problem_description');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropColumn(['problem_description', 'resolution_description']);
        });
    }
};
