<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\SchoolSection;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'classrooms.view',
    'classrooms.create',
    'classrooms.update',
    'sections.view',
    'sections.create',
    'sections.update',
];

$tableExists = [
    'classrooms' => Schema::hasTable('classrooms'),
    'sections' => Schema::hasTable('sections'),
    'academic_years' => Schema::hasTable('academic_years'),
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

$classroomCodeDuplicates = $tableExists['classrooms']
    ? Classroom::query()
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

$sectionCodeDuplicates = $tableExists['sections']
    ? SchoolSection::query()
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

$orphanSectionsMissingYear = $tableExists['sections'] && $tableExists['academic_years']
    ? SchoolSection::query()
        ->leftJoin('academic_years', 'sections.academic_year_id', '=', 'academic_years.id')
        ->whereNull('academic_years.id')
        ->count()
    : 0;

$orphanSectionsMissingGrade = $tableExists['sections'] && $tableExists['grades']
    ? SchoolSection::query()
        ->leftJoin('grades', 'sections.grade_id', '=', 'grades.id')
        ->whereNull('grades.id')
        ->count()
    : 0;

$orphanSectionsMissingClassroom = $tableExists['sections'] && $tableExists['classrooms']
    ? SchoolSection::query()
        ->leftJoin('classrooms', 'sections.classroom_id', '=', 'classrooms.id')
        ->whereNotNull('sections.classroom_id')
        ->whereNull('classrooms.id')
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

    'classrooms' => [
        'total' => $tableExists['classrooms'] ? Classroom::query()->count() : 0,
        'active' => $tableExists['classrooms'] ? Classroom::query()->where('is_active', true)->count() : 0,
        'types' => $tableExists['classrooms']
            ? Classroom::query()
                ->select(['type', DB::raw('COUNT(*) as total')])
                ->groupBy('type')
                ->orderBy('type')
                ->pluck('total', 'type')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_codes' => $classroomCodeDuplicates,
    ],

    'sections' => [
        'total' => $tableExists['sections'] ? SchoolSection::query()->count() : 0,
        'statuses' => $tableExists['sections']
            ? SchoolSection::query()
                ->select(['status', DB::raw('COUNT(*) as total')])
                ->groupBy('status')
                ->orderBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'gender_policies' => $tableExists['sections']
            ? SchoolSection::query()
                ->select(['gender_policy', DB::raw('COUNT(*) as total')])
                ->groupBy('gender_policy')
                ->orderBy('gender_policy')
                ->pluck('total', 'gender_policy')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_codes' => $sectionCodeDuplicates,
        'orphan_sections' => [
            'missing_year' => (int) $orphanSectionsMissingYear,
            'missing_grade' => (int) $orphanSectionsMissingGrade,
            'missing_classroom' => (int) $orphanSectionsMissingClassroom,
        ],
    ],

    'sample' => [
        'first_classrooms' => $tableExists['classrooms']
            ? Classroom::query()
                ->select(['id', 'name', 'code', 'type', 'capacity', 'is_active'])
                ->orderBy('sort_order')
                ->limit(5)
                ->get()
                ->values()
                ->all()
            : [],

        'first_sections' => $tableExists['sections']
            ? SchoolSection::query()
                ->with([
                    'academicYear:id,name',
                    'grade:id,name',
                    'classroom:id,name,code',
                ])
                ->orderBy('academic_year_id')
                ->orderBy('grade_id')
                ->orderBy('sort_order')
                ->limit(8)
                ->get()
                ->map(fn (SchoolSection $section): array => [
                    'year' => $section->academicYear?->name,
                    'grade' => $section->grade?->name,
                    'name' => $section->name,
                    'code' => $section->code,
                    'classroom' => $section->classroom?->name,
                    'status' => $section->status,
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
    || $classroomCodeDuplicates !== []
    || $sectionCodeDuplicates !== []
    || $orphanSectionsMissingYear > 0
    || $orphanSectionsMissingGrade > 0
    || $orphanSectionsMissingClassroom > 0
    || $report['classrooms']['total'] < 50
    || $report['sections']['total'] < 50;

exit($hasErrors ? 1 : 0);
