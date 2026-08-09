<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('incident_updates', function (Blueprint $table): void {
            $table->string('mobile_idempotency_key', 100)->nullable()->after('message');
            $table->unique(['incident_id', 'mobile_idempotency_key'], 'incident_updates_mobile_idempotency_unique');
        });

        Schema::table('incident_follow_ups', function (Blueprint $table): void {
            $table->string('mobile_idempotency_key', 100)->nullable()->after('external_url');
            $table->unique(['incident_id', 'mobile_idempotency_key'], 'incident_follow_ups_mobile_idempotency_unique');
        });

        Schema::table('incident_timeline_events', function (Blueprint $table): void {
            $table->string('mobile_idempotency_key', 100)->nullable()->after('source_type');
            $table->unique(['incident_id', 'mobile_idempotency_key'], 'incident_timeline_mobile_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('incident_updates', function (Blueprint $table): void {
            $table->dropUnique('incident_updates_mobile_idempotency_unique');
            $table->dropColumn('mobile_idempotency_key');
        });

        Schema::table('incident_follow_ups', function (Blueprint $table): void {
            $table->dropUnique('incident_follow_ups_mobile_idempotency_unique');
            $table->dropColumn('mobile_idempotency_key');
        });

        Schema::table('incident_timeline_events', function (Blueprint $table): void {
            $table->dropUnique('incident_timeline_mobile_idempotency_unique');
            $table->dropColumn('mobile_idempotency_key');
        });
    }
};
