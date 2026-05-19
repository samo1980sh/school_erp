<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('gender_policy')->default('mixed')->index();
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'grade_id', 'name'], 'sections_year_grade_name_unique');
            $table->index(['academic_year_id', 'grade_id']);
            $table->index(['classroom_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
