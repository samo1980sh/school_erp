<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'academic_years.view',
    'academic_years.create',
    'academic_years.update',
    'academic_terms.view',
    'academic_terms.create',
    'academic_terms.update',
];

$tableExists = [
    'academic_years' => Schema::hasTable('academic_years'),
    'academic_terms' => Schema::hasTable('academic_terms'),
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
    ->map(fn($row): array => [
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
    ->map(fn($row): array => [
        'name' => $row->name,
        'guard_name' => $row->guard_name,
        'total' => (int) $row->total,
    ])
    ->values()
    ->all();

$academicYearCodeDuplicates = $tableExists['academic_years']
    ? AcademicYear::query()
    ->select(['code', DB::raw('COUNT(*) as total')])
    ->groupBy('code')
    ->having('total', '>', 1)
    ->orderBy('code')
    ->get()
    ->map(fn($row): array => [
        'code' => $row->code,
        'total' => (int) $row->total,
    ])
    ->values()
    ->all()
    : [];

$academicTermCodeDuplicates = $tableExists['academic_terms']
    ? AcademicTerm::query()
    ->select(['code', DB::raw('COUNT(*) as total')])
    ->groupBy('code')
    ->having('total', '>', 1)
    ->orderBy('code')
    ->get()
    ->map(fn($row): array => [
        'code' => $row->code,
        'total' => (int) $row->total,
    ])
    ->values()
    ->all()
    : [];

$orphanTerms = $tableExists['academic_terms'] && $tableExists['academic_years']
    ? AcademicTerm::query()
    ->leftJoin('academic_years', 'academic_terms.academic_year_id', '=', 'academic_years.id')
    ->whereNull('academic_years.id')
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

    'academic_years' => [
        'total' => $tableExists['academic_years'] ? AcademicYear::query()->count() : 0,
        'current' => $tableExists['academic_years'] ? AcademicYear::query()->where('is_current', true)->count() : 0,
        'statuses' => $tableExists['academic_years']
            ? AcademicYear::query()
            ->select(['status', DB::raw('COUNT(*) as total')])
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status')
            ->map(fn($value): int => (int) $value)
            ->toArray()
            : [],
        'duplicate_codes' => $academicYearCodeDuplicates,
    ],

    'academic_terms' => [
        'total' => $tableExists['academic_terms'] ? AcademicTerm::query()->count() : 0,
        'current' => $tableExists['academic_terms'] ? AcademicTerm::query()->where('is_current', true)->count() : 0,
        'statuses' => $tableExists['academic_terms']
            ? AcademicTerm::query()
            ->select(['status', DB::raw('COUNT(*) as total')])
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status')
            ->map(fn($value): int => (int) $value)
            ->toArray()
            : [],
        'duplicate_codes' => $academicTermCodeDuplicates,
        'orphan_terms' => (int) $orphanTerms,
    ],

    'sample' => [
        'current_year' => $tableExists['academic_years']
            ? AcademicYear::query()
            ->where('is_current', true)
            ->select(['id', 'name', 'code', 'status', 'starts_on', 'ends_on'])
            ->first()
            ?->toArray()
            : null,

        'first_terms' => $tableExists['academic_terms']
            ? AcademicTerm::query()
            ->with('academicYear:id,name')
            ->orderBy('academic_year_id')
            ->orderBy('sort_order')
            ->limit(6)
            ->get()
            ->map(fn(AcademicTerm $term): array => [
                'year' => $term->academicYear?->name,
                'name' => $term->name,
                'code' => $term->code,
                'status' => $term->status,
                'is_current' => (bool) $term->is_current,
            ])
            ->values()
            ->all()
            : [],
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
    || $academicYearCodeDuplicates !== []
    || $academicTermCodeDuplicates !== []
    || $orphanTerms > 0
    || $report['academic_years']['current'] !== 1
    || $report['academic_terms']['current'] !== 1;

exit($hasErrors ? 1 : 0);
