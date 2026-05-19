<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fees', function (Blueprint $table): void {
            $table->id();
            $table->string('fee_number')->unique();
            $table->foreignId('fee_type_id')->constrained('fee_types')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->nullable()->constrained('student_enrollments')->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->date('due_on')->nullable();
            $table->string('status', 30)->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fee_type_id', 'student_id', 'academic_year_id'], 'student_fee_unique_assignment');
            $table->index(['academic_year_id', 'grade_id', 'section_id']);
            $table->index(['status', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fees');
    }
};
