<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->string('dns_record_type', 16)->nullable()->after('keyword');
            $table->json('dns_expected_values')->nullable()->after('dns_record_type');
        });
    }

    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table): void {
            $table->dropColumn(['dns_record_type', 'dns_expected_values']);
        });
    }
};
