<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name')->nullable()->after('guard_name');
            }

            if (! Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }

            if (! Schema::hasColumn('roles', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            if (Schema::hasColumn('roles', 'sort_order')) {
                $table->dropColumn('sort_order');
            }

            if (Schema::hasColumn('roles', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('roles', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });
    }
};
