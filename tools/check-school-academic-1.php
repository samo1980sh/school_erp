<?php

declare(strict_types=1);

use App\Models\GradeSubject;
use App\Models\Subject;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'subjects.view',
    'subjects.create',
    'subjects.update',
];

$tableExists = [
    'subjects' => Schema::hasTable('subjects'),
    'grade_subjects' => Schema::hasTable('grade_subjects'),
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

$subjectCodeDuplicates = $tableExists['subjects']
    ? Subject::query()
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

$gradeSubjectDuplicates = $tableExists['grade_subjects']
    ? GradeSubject::query()
        ->select(['academic_year_id', 'grade_id', 'subject_id', DB::raw('COUNT(*) as total')])
        ->groupBy('academic_year_id', 'grade_id', 'subject_id')
        ->having('total', '>', 1)
        ->orderBy('academic_year_id')
        ->orderBy('grade_id')
        ->get()
        ->map(fn ($row): array => [
            'academic_year_id' => (int) $row->academic_year_id,
            'grade_id' => (int) $row->grade_id,
            'subject_id' => (int) $row->subject_id,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$orphanGradeSubjectsMissingYear = $tableExists['grade_subjects'] && $tableExists['academic_years']
    ? GradeSubject::query()
        ->leftJoin('academic_years', 'grade_subjects.academic_year_id', '=', 'academic_years.id')
        ->whereNull('academic_years.id')
        ->count()
    : 0;

$orphanGradeSubjectsMissingGrade = $tableExists['grade_subjects'] && $tableExists['grades']
    ? GradeSubject::query()
        ->leftJoin('grades', 'grade_subjects.grade_id', '=', 'grades.id')
        ->whereNull('grades.id')
        ->count()
    : 0;

$orphanGradeSubjectsMissingSubject = $tableExists['grade_subjects'] && $tableExists['subjects']
    ? GradeSubject::query()
        ->leftJoin('subjects', 'grade_subjects.subject_id', '=', 'subjects.id')
        ->whereNull('subjects.id')
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

    'subjects' => [
        'total' => $tableExists['subjects'] ? Subject::query()->count() : 0,
        'active' => $tableExists['subjects'] ? Subject::query()->where('is_active', true)->count() : 0,
        'categories' => $tableExists['subjects']
            ? Subject::query()
                ->select(['category', DB::raw('COUNT(*) as total')])
                ->groupBy('category')
                ->orderBy('category')
                ->pluck('total', 'category')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_codes' => $subjectCodeDuplicates,
    ],

    'grade_subjects' => [
        'total' => $tableExists['grade_subjects'] ? GradeSubject::query()->count() : 0,
        'statuses' => $tableExists['grade_subjects']
            ? GradeSubject::query()
                ->select(['status', DB::raw('COUNT(*) as total')])
                ->groupBy('status')
                ->orderBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_assignments' => $gradeSubjectDuplicates,
        'orphan_grade_subjects' => [
            'missing_year' => (int) $orphanGradeSubjectsMissingYear,
            'missing_grade' => (int) $orphanGradeSubjectsMissingGrade,
            'missing_subject' => (int) $orphanGradeSubjectsMissingSubject,
        ],
    ],

    'sample' => [
        'first_subjects' => $tableExists['subjects']
            ? Subject::query()
                ->select(['id', 'name', 'code', 'category', 'default_weekly_periods', 'is_active'])
                ->orderBy('sort_order')
                ->limit(8)
                ->get()
                ->values()
                ->all()
            : [],

        'first_grade_subjects' => $tableExists['grade_subjects']
            ? GradeSubject::query()
                ->with([
                    'academicYear:id,name',
                    'grade:id,name',
                    'subject:id,name,code',
                ])
                ->orderBy('academic_year_id')
                ->orderBy('grade_id')
                ->orderBy('sort_order')
                ->limit(10)
                ->get()
                ->map(fn (GradeSubject $plan): array => [
                    'year' => $plan->academicYear?->name,
                    'grade' => $plan->grade?->name,
                    'subject' => $plan->subject?->name,
                    'subject_code' => $plan->subject?->code,
                    'weekly_periods' => $plan->weekly_periods,
                    'coefficient' => (string) $plan->coefficient,
                    'status' => $plan->status,
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
    || $subjectCodeDuplicates !== []
    || $gradeSubjectDuplicates !== []
    || $orphanGradeSubjectsMissingYear > 0
    || $orphanGradeSubjectsMissingGrade > 0
    || $orphanGradeSubjectsMissingSubject > 0
    || $report['subjects']['total'] < 15
    || $report['grade_subjects']['total'] < 50;

exit($hasErrors ? 1 : 0);
