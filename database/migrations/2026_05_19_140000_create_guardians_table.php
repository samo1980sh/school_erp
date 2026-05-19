<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table): void {
            $table->id();
            $table->string('guardian_number')->unique();
            $table->string('first_name');
            $table->string('father_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->index();
            $table->string('gender', 20)->default('male')->index();
            $table->string('relation_type', 30)->default('father')->index();
            $table->string('national_id')->nullable()->unique();
            $table->string('occupation')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('workplace')->nullable();
            $table->boolean('is_emergency_contact')->default(true)->index();
            $table->boolean('has_custody')->default(true)->index();
            $table->boolean('is_financial_responsible')->default(true)->index();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
