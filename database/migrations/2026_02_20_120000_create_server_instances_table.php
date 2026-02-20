<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_instances', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('api_key_hash');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $defaultApiKey = env('WEBGUARD_INSTANCE_API_KEY') ?: Str::random(48);

        DB::table('server_instances')->insert([
            'id' => (string) Str::ulid(),
            'code' => 'de-1',
            'api_key_hash' => Hash::make($defaultApiKey),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_instances');
    }
};
