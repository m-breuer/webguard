<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'instance_callback_idempotencies';

    private const UNIQUE_INDEX = 'instance_callback_idempotencies_key_unique';

    private const EXPIRY_INDEX = 'instance_callback_idempotencies_expires_at_index';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('instance_code', 32);
                $table->string('endpoint', 64);
                $table->string('idempotency_key', 100);
                $table->string('request_hash', 64);
                $table->unsignedSmallInteger('response_status');
                $table->json('response_body');
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->unique(
                    ['instance_code', 'endpoint', 'idempotency_key'],
                    self::UNIQUE_INDEX,
                );
                $table->index('expires_at', self::EXPIRY_INDEX);
            });

            return;
        }

        // MySQL can persist CREATE TABLE after a failed migration before Laravel
        // records the migration. Complete the indexes on the next startup.
        if (! Schema::hasIndex(self::TABLE, self::UNIQUE_INDEX, 'unique')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unique(
                    ['instance_code', 'endpoint', 'idempotency_key'],
                    self::UNIQUE_INDEX,
                );
            });
        }

        if (! Schema::hasIndex(self::TABLE, self::EXPIRY_INDEX, 'index')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->index('expires_at', self::EXPIRY_INDEX);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
