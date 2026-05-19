<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table): void {
            $table->id();

            $table->string('school_name');
            $table->string('legal_name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('school_code')->nullable()->unique();
            $table->string('license_number')->nullable();

            $table->string('principal_name')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();

            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();

            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();

            $table->string('default_locale', 10)->default('ar');
            $table->string('timezone')->default('Asia/Damascus');
            $table->string('currency_code', 10)->default('SYP');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
