<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('horizon_batches');
        Schema::dropIfExists('horizon_jobs');
        Schema::dropIfExists('horizon_metrics');
        Schema::dropIfExists('horizon_monitoring');
        Schema::dropIfExists('horizon_supervisors');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it is for a package that has been removed.
    }
};
