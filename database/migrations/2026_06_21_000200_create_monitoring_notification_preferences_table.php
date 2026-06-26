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
        Schema::create('monitoring_notification_preferences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('monitoring_id')->constrained('monitorings')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('notification_on_failure')->default(true);
            $table->json('notification_channels')->nullable();
            $table->unsignedSmallInteger('ssl_expiry_warning_days')->default(7);
            $table->timestamps();

            $table->unique(['monitoring_id', 'user_id'], 'monitoring_notification_preferences_monitoring_user_unique');
            $table->index(['user_id', 'monitoring_id'], 'monitoring_notification_preferences_user_monitoring_idx');
        });

        DB::table('monitorings')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->select(['id', 'user_id', 'notification_on_failure', 'notification_channels', 'ssl_expiry_warning_days', 'created_at', 'updated_at'])
            ->chunk(500, function ($monitorings): void {
                $rows = [];

                foreach ($monitorings as $monitoring) {
                    $rows[] = [
                        'id' => (string) Illuminate\Support\Str::ulid(),
                        'monitoring_id' => $monitoring->id,
                        'user_id' => $monitoring->user_id,
                        'notification_on_failure' => (bool) $monitoring->notification_on_failure,
                        'notification_channels' => $monitoring->notification_channels,
                        'ssl_expiry_warning_days' => (int) ($monitoring->ssl_expiry_warning_days ?? 7),
                        'created_at' => $monitoring->created_at,
                        'updated_at' => $monitoring->updated_at,
                    ];
                }

                if ($rows !== []) {
                    DB::table('monitoring_notification_preferences')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_notification_preferences');
    }
};
