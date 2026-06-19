<?php

declare(strict_types=1);

use App\Enums\StatusPageComponentSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('status_page_components', function (Blueprint $table): void {
            $table->string('source_type', 32)->default(StatusPageComponentSource::MANUAL->value)->after('position');
            $table->foreignUlid('monitoring_group_id')->nullable()->after('status_page_id');
            $table->index(['monitoring_group_id', 'source_type'], 'status_page_components_group_source_idx');
            $table->foreign('monitoring_group_id', 'status_page_components_group_fk')
                ->references('id')
                ->on('monitoring_groups')
                ->nullOnDelete();
        });

        Schema::table('monitoring_groups', function (Blueprint $table): void {
            $table->dropColumn('public_label_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_groups', function (Blueprint $table): void {
            $table->boolean('public_label_enabled')->default(false);
        });

        Schema::table('status_page_components', function (Blueprint $table): void {
            $table->dropForeign('status_page_components_group_fk');
            $table->dropIndex('status_page_components_group_source_idx');
            $table->dropColumn(['monitoring_group_id', 'source_type']);
        });
    }
};
