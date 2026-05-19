<?php

declare(strict_types=1);

use App\Models\EducationalStage;
use App\Models\Grade;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'educational_stages.view',
    'educational_stages.create',
    'educational_stages.update',
    'grades.view',
    'grades.create',
    'grades.update',
];

$tableExists = [
    'educational_stages' => Schema::hasTable('educational_stages'),
    'grades' => Schema::hasTable('grades'),
    'permissions' => Schema::hasTable('permissions'),
    'roles' => Schema::hasTable('roles'),
];

$existingRequiredPermissions = Permission::query()
    ->where('guard_name', 'web')
    ->whereIn('name', $requiredPermissions)
    ->pluck('name')
    ->all();

$missingPermissions = array_values(array_diff($requiredPermissions, $existingRequiredPermissions));

$permissionDuplicates = Permission::query()
    ->select(['name', 'guard_name', DB::raw('COUNT(*) as total')])
    ->groupBy('name', 'guard_name')
    ->having('total', '>', 1)
    ->orderBy('name')
    ->get()
    ->map(fn ($row): array => [
        'name' => $row->name,
        'guard_name' => $row->guard_name,
        'total' => (int) $row->total,
    ])
    ->values()
    ->all();

$roleDuplicates = Role::query()
    ->select(['name', 'guard_name', DB::raw('COUNT(*) as total')])
    ->groupBy('name', 'guard_name')
    ->having('total', '>', 1)
    ->orderBy('name')
    ->get()
    ->map(fn ($row): array => [
        'name' => $row->name,
        'guard_name' => $row->guard_name,
        'total' => (int) $row->total,
    ])
    ->values()
    ->all();

$stageCodeDuplicates = $tableExists['educational_stages']
    ? EducationalStage::query()
        ->select(['code', DB::raw('COUNT(*) as total')])
        ->groupBy('code')
        ->having('total', '>', 1)
        ->orderBy('code')
        ->get()
        ->map(fn ($row): array => [
            'code' => $row->code,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$gradeCodeDuplicates = $tableExists['grades']
    ? Grade::query()
        ->select(['code', DB::raw('COUNT(*) as total')])
        ->groupBy('code')
        ->having('total', '>', 1)
        ->orderBy('code')
        ->get()
        ->map(fn ($row): array => [
            'code' => $row->code,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$orphanGrades = $tableExists['grades'] && $tableExists['educational_stages']
    ? Grade::query()
        ->leftJoin('educational_stages', 'grades.educational_stage_id', '=', 'educational_stages.id')
        ->whereNull('educational_stages.id')
        ->count()
    : 0;

$report = [
    'tables' => $tableExists,

    'permissions' => [
        'required' => $requiredPermissions,
        'missing' => $missingPermissions,
        'duplicates' => $permissionDuplicates,
        'non_web_guard_count' => Permission::query()
            ->where('guard_name', '<>', 'web')
            ->count(),
    ],

    'roles' => [
        'duplicates' => $roleDuplicates,
        'non_web_guard_count' => Role::query()
            ->where('guard_name', '<>', 'web')
            ->count(),
    ],

    'educational_stages' => [
        'total' => $tableExists['educational_stages'] ? EducationalStage::query()->count() : 0,
        'active' => $tableExists['educational_stages'] ? EducationalStage::query()->where('is_active', true)->count() : 0,
        'duplicate_codes' => $stageCodeDuplicates,
        'sample' => $tableExists['educational_stages']
            ? EducationalStage::query()
                ->withCount('grades')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'code', 'sort_order', 'is_active'])
                ->map(fn (EducationalStage $stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'code' => $stage->code,
                    'sort_order' => $stage->sort_order,
                    'is_active' => (bool) $stage->is_active,
                    'grades_count' => $stage->grades_count,
                ])
                ->values()
                ->all()
            : [],
    ],

    'grades' => [
        'total' => $tableExists['grades'] ? Grade::query()->count() : 0,
        'active' => $tableExists['grades'] ? Grade::query()->where('is_active', true)->count() : 0,
        'duplicate_codes' => $gradeCodeDuplicates,
        'orphan_grades' => (int) $orphanGrades,
        'sample' => $tableExists['grades']
            ? Grade::query()
                ->with('educationalStage:id,name')
                ->orderBy('educational_stage_id')
                ->orderBy('sort_order')
                ->limit(10)
                ->get()
                ->map(fn (Grade $grade): array => [
                    'stage' => $grade->educationalStage?->name,
                    'name' => $grade->name,
                    'code' => $grade->code,
                    'grade_number' => $grade->grade_number,
                    'sort_order' => $grade->sort_order,
                    'is_active' => (bool) $grade->is_active,
                ])
                ->values()
                ->all()
            : [],
    ],

    'excel_support' => [
        'included' => false,
        'reason' => 'Educational stages and grades are controlled reference data with limited records. Excel import/export is not required in this package.',
    ],
];

echo json_encode(
    $report,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) . PHP_EOL;

$hasErrors =
    in_array(false, $tableExists, true)
    || $missingPermissions !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $stageCodeDuplicates !== []
    || $gradeCodeDuplicates !== []
    || $orphanGrades > 0
    || $report['educational_stages']['total'] < 4
    || $report['grades']['total'] < 17;

exit($hasErrors ? 1 : 0);
