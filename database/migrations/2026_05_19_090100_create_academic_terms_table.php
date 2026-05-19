<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('name');
            $table->string('code')->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 30)->default('planned')->index();
            $table->boolean('is_current')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);
            $table->index(['academic_year_id', 'status', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};
