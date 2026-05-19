<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('due_on')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'grade_id']);
            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_types');
    }
};
