<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            if (! Schema::hasColumn('sections', 'is_active')) {
                $table->boolean('is_active')
                    ->default(true)
                    ->after('capacity');
            }
        });

        DB::table('sections')->update([
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            if (Schema::hasColumn('sections', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
