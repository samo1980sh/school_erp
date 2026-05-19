<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('father_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender', 20)->default('male');
            $table->date('birth_date')->nullable();
            $table->string('national_id')->nullable()->unique();
            $table->string('marital_status', 30)->nullable();

            $table->string('job_title');
            $table->string('department')->nullable();
            $table->string('employment_type', 50)->default('administrative');
            $table->date('hire_date')->nullable();
            $table->string('contract_type', 50)->nullable();
            $table->string('status', 30)->default('active');

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();

            $table->string('qualification')->nullable();
            $table->string('specialization')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['status', 'department']);
            $table->index(['employment_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
