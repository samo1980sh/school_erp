<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grades')) {
            return;
        }

        Schema::create('grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('educational_stage_id')
                ->constrained('educational_stages')
                ->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedTinyInteger('grade_number')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['educational_stage_id', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
