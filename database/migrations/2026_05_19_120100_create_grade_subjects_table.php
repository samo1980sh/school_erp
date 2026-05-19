<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grade_subjects')) {
            return;
        }

        Schema::create('grade_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->unsignedTinyInteger('weekly_periods')->nullable();
            $table->decimal('coefficient', 5, 2)->default(1.00);
            $table->boolean('is_core')->default(true)->index();
            $table->boolean('is_exam_subject')->default(true)->index();
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'grade_id', 'subject_id'], 'grade_subjects_year_grade_subject_unique');
            $table->index(['academic_year_id', 'grade_id']);
            $table->index(['subject_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_subjects');
    }
};
