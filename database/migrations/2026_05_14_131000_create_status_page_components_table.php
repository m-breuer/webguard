<?php

declare(strict_types=1);

use App\Models\StatusPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('status_page_components', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignIdFor(StatusPage::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['status_page_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_page_components');
    }
};
