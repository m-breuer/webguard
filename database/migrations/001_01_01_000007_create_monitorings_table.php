<?php

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Models\User;
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
        Schema::create('monitorings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->enum('type', MonitoringType::values())->default(MonitoringType::HTTP);
            $table->string('target');
            $table->integer('port')->nullable();
            $table->string('keyword')->nullable();
            $table->enum('status', MonitoringLifecycleStatus::values())->default(MonitoringLifecycleStatus::ACTIVE);
            $table->integer('timeout')->default(5);
            $table->enum('http_method', App\Enums\HttpMethod::values())->nullable();
            $table->json('http_headers')->nullable();
            $table->text('http_body')->nullable();
            $table->string('auth_username')->nullable();
            $table->string('auth_password')->nullable();
            $table->boolean('public_label_enabled')->default(false);
            $table->enum('preferred_location', App\Enums\ServerInstance::values())
                ->default(App\Enums\ServerInstance::DE_1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitorings');
    }
};
