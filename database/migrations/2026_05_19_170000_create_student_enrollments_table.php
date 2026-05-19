<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('academic_term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->restrictOnDelete();
            $table->foreignId('section_id')->constrained('sections')->restrictOnDelete();

            $table->string('enrollment_number')->unique();
            $table->date('enrollment_date');
            $table->string('enrollment_type')->default('new');
            $table->string('status')->default('enrolled');
            $table->boolean('is_current')->default(true);
            $table->string('previous_school')->nullable();
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id'], 'student_enrollments_student_year_unique');
            $table->index(['academic_year_id', 'grade_id', 'section_id'], 'student_enrollments_academic_scope_index');
            $table->index(['status', 'is_current'], 'student_enrollments_status_current_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
