<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();

            $table->string('student_number')->unique();
            $table->string('first_name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->index();

            $table->string('gender', 20)->default('male')->index();
            $table->date('birth_date')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('national_id')->nullable()->unique();

            $table->date('enrollment_date')->nullable();
            $table->foreignId('current_academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('current_grade_id')->nullable()->constrained('grades')->nullOnDelete();
            $table->foreignId('current_section_id')->nullable()->constrained('sections')->nullOnDelete();

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->text('medical_notes')->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 30)->default('active')->index();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->index(['current_academic_year_id', 'current_grade_id', 'current_section_id'], 'students_current_placement_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
