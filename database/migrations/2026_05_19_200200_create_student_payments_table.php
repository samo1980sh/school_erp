<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('student_fee_id')->constrained('student_fees')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('paid_on');
            $table->string('payment_method', 30)->default('cash');
            $table->string('reference_number')->nullable();
            $table->foreignId('received_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_fee_id', 'paid_on']);
            $table->index(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};
