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
        Schema::create('monitoring_notification_states', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('monitoring_notification_id');
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('monitoring_notification_id', 'mnotif_states_notification_fk')
                ->references('id')
                ->on('monitoring_notifications')
                ->cascadeOnDelete();
            $table->unique(['monitoring_notification_id', 'user_id'], 'monitoring_notification_states_notification_user_unique');
            $table->index(['user_id', 'read_at'], 'monitoring_notification_states_user_read_idx');
        });

        DB::table('monitoring_notifications')
            ->join('monitorings', 'monitoring_notifications.monitoring_id', '=', 'monitorings.id')
            ->whereNotNull('monitorings.user_id')
            ->orderBy('monitoring_notifications.id')
            ->select([
                'monitoring_notifications.id',
                'monitoring_notifications.read',
                'monitoring_notifications.sent',
                'monitoring_notifications.created_at',
                'monitoring_notifications.updated_at',
                'monitorings.user_id',
            ])
            ->chunk(500, function ($notifications): void {
                $rows = [];

                foreach ($notifications as $notification) {
                    $rows[] = [
                        'id' => (string) Illuminate\Support\Str::ulid(),
                        'monitoring_notification_id' => $notification->id,
                        'user_id' => $notification->user_id,
                        'read_at' => $notification->read ? $notification->updated_at : null,
                        'sent_at' => $notification->sent ? $notification->updated_at : null,
                        'created_at' => $notification->created_at,
                        'updated_at' => $notification->updated_at,
                    ];
                }

                if ($rows !== []) {
                    DB::table('monitoring_notification_states')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_notification_states');
    }
};
