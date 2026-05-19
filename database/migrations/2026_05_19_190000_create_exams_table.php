<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained('academic_terms')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('exam_type')->default('monthly');
            $table->date('exam_date')->nullable();
            $table->decimal('max_mark', 8, 2)->default(100);
            $table->decimal('passing_mark', 8, 2)->default(50);
            $table->decimal('weight_percent', 5, 2)->default(100);
            $table->string('status')->default('planned');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['academic_year_id', 'academic_term_id']);
            $table->index(['grade_id', 'subject_id']);
            $table->index(['status', 'exam_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
