<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('permissions', 'group_name')) {
                $table->string('group_name')->nullable()->after('guard_name');
            }

            if (! Schema::hasColumn('permissions', 'display_name')) {
                $table->string('display_name')->nullable()->after('group_name');
            }

            if (! Schema::hasColumn('permissions', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }

            if (! Schema::hasColumn('permissions', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('description');
            }
        });
    }

    public function down(): void
    {
        $columns = [];

        foreach (['group_name', 'display_name', 'description', 'sort_order'] as $column) {
            if (Schema::hasColumn('permissions', $column)) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            Schema::table('permissions', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
