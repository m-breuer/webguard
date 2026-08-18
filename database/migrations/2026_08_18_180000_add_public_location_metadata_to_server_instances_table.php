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
        Schema::table('server_instances', function (Blueprint $table): void {
            $table->string('display_name', 100)->nullable()->after('code');
            $table->string('country_code', 2)->nullable()->after('display_name');
            $table->string('region', 100)->nullable()->after('country_code');
        });

        DB::table('server_instances')
            ->where('code', 'de-1')
            ->update([
                'display_name' => 'Germany',
                'country_code' => 'DE',
                'region' => 'Europe',
            ]);
    }

    public function down(): void
    {
        Schema::table('server_instances', function (Blueprint $table): void {
            $table->dropColumn(['display_name', 'country_code', 'region']);
        });
    }
};
