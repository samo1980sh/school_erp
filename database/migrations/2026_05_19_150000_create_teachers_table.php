<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('teacher_number')->unique();
            $table->string('full_name');
            $table->string('gender', 20)->default('male');
            $table->string('national_id')->nullable()->unique();
            $table->date('birth_date')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();

            $table->string('qualification')->nullable();
            $table->string('specialization')->nullable();
            $table->string('job_title')->nullable();
            $table->string('employment_type', 30)->default('full_time');
            $table->date('hire_date')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'employment_type']);
            $table->index('specialization');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
