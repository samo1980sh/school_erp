<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('relationship_type', 30)->default('guardian')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->boolean('can_pick_up')->default(true);
            $table->boolean('is_financial_responsible')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['guardian_id', 'student_id'], 'guardian_student_unique');
            $table->index(['student_id', 'relationship_type'], 'guardian_student_student_relation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
    }
};
