<?php

declare(strict_types=1);

use App\Enums\HttpMethod;
use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Enums\NotificationType;
use App\Enums\ServerInstance;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::REGULAR->value)->change();
        });

        Schema::table('monitorings', function (Blueprint $table) {
            $table->string('type')->default(MonitoringType::HTTP->value)->change();
            $table->string('status')->default(MonitoringLifecycleStatus::ACTIVE->value)->change();
            $table->string('http_method')->nullable()->change();
            $table->string('preferred_location')->default(ServerInstance::DE_1->value)->change();
        });

        Schema::table('monitoring_response_results', function (Blueprint $table) {
            $table->string('status')->default(MonitoringStatus::UNKNOWN->value)->change();
        });

        Schema::table('monitoring_notifications', function (Blueprint $table) {
            $table->string('type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', UserRole::values())->default(UserRole::REGULAR->value)->change();
        });

        Schema::table('monitorings', function (Blueprint $table) {
            $table->enum('type', MonitoringType::values())->default(MonitoringType::HTTP->value)->change();
            $table->enum('status', MonitoringLifecycleStatus::values())->default(MonitoringLifecycleStatus::ACTIVE->value)->change();
            $table->enum('http_method', HttpMethod::values())->nullable()->change();
            $table->enum('preferred_location', ServerInstance::values())->default(ServerInstance::DE_1->value)->change();
        });

        Schema::table('monitoring_response_results', function (Blueprint $table) {
            $table->enum('status', MonitoringStatus::values())->default(MonitoringStatus::UNKNOWN->value)->change();
        });

        Schema::table('monitoring_notifications', function (Blueprint $table) {
            $table->enum('type', NotificationType::values())->change();
        });
    }
};
