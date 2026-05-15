<?php

declare(strict_types=1);

use App\Models\Incident;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('incident_updates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignIdFor(Incident::class)->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('message');
            $table->timestamps();

            $table->index(['incident_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_updates');
    }
};
