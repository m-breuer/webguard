<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->string('incident_type')->nullable()->after('resolution_description');
            $table->string('severity')->nullable()->after('incident_type');
            $table->string('affected_service')->nullable()->after('severity');
            $table->string('customer_impact')->nullable()->after('affected_service');
            $table->string('contributing_category')->nullable()->after('customer_impact');
            $table->index(['incident_type', 'severity'], 'incidents_type_severity_idx');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropIndex('incidents_type_severity_idx');
            $table->dropColumn([
                'incident_type',
                'severity',
                'affected_service',
                'customer_impact',
                'contributing_category',
            ]);
        });
    }
};
