<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->enum('role', UserRole::values())->default(UserRole::REGULAR);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->foreignUlid('package_id')->nullable()->constrained('packages')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $blueprint): void {
            $blueprint->string('email')->primary();
            $blueprint->string('token');
            $blueprint->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $blueprint): void {
            $blueprint->string('id')->primary();
            $blueprint->foreignUlid('user_id')->nullable()->index();
            $blueprint->string('ip_address', 45)->nullable();
            $blueprint->text('user_agent')->nullable();
            $blueprint->longText('payload');
            $blueprint->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
