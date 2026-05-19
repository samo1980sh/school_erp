<?php

declare(strict_types=1);

use App\Models\StudentEnrollment;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'enrollments.view',
    'enrollments.create',
    'enrollments.update',
    'enrollments.export',
    'enrollments.import',
];

$tables = [
    'student_enrollments' => Schema::hasTable('student_enrollments'),
    'students' => Schema::hasTable('students'),
    'academic_years' => Schema::hasTable('academic_years'),
    'academic_terms' => Schema::hasTable('academic_terms'),
    'grades' => Schema::hasTable('grades'),
    'sections' => Schema::hasTable('sections'),
    'permissions' => Schema::hasTable('permissions'),
    'roles' => Schema::hasTable('roles'),
];

$existingPermissions = Permission::query()
    ->where('guard_name', 'web')
    ->whereIn('name', $requiredPermissions)
    ->pluck('name')
    ->all();

$missingPermissions = array_values(array_diff($requiredPermissions, $existingPermissions));

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

$duplicateEnrollmentNumbers = $tables['student_enrollments']
    ? StudentEnrollment::query()
        ->select(['enrollment_number', DB::raw('COUNT(*) as total')])
        ->groupBy('enrollment_number')
        ->having('total', '>', 1)
        ->orderBy('enrollment_number')
        ->get()
        ->map(fn ($row): array => [
            'enrollment_number' => $row->enrollment_number,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$duplicateStudentYear = $tables['student_enrollments']
    ? StudentEnrollment::query()
        ->select(['student_id', 'academic_year_id', DB::raw('COUNT(*) as total')])
        ->groupBy('student_id', 'academic_year_id')
        ->having('total', '>', 1)
        ->get()
        ->map(fn ($row): array => [
            'student_id' => (int) $row->student_id,
            'academic_year_id' => (int) $row->academic_year_id,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$orphanEnrollments = [
    'missing_student' => 0,
    'missing_year' => 0,
    'missing_term' => 0,
    'missing_grade' => 0,
    'missing_section' => 0,
];

if ($tables['student_enrollments']) {
    $orphanEnrollments['missing_student'] = StudentEnrollment::query()
        ->leftJoin('students', 'student_enrollments.student_id', '=', 'students.id')
        ->whereNull('students.id')
        ->count();

    $orphanEnrollments['missing_year'] = StudentEnrollment::query()
        ->leftJoin('academic_years', 'student_enrollments.academic_year_id', '=', 'academic_years.id')
        ->whereNull('academic_years.id')
        ->count();

    $orphanEnrollments['missing_term'] = StudentEnrollment::query()
        ->whereNotNull('student_enrollments.academic_term_id')
        ->leftJoin('academic_terms', 'student_enrollments.academic_term_id', '=', 'academic_terms.id')
        ->whereNull('academic_terms.id')
        ->count();

    $orphanEnrollments['missing_grade'] = StudentEnrollment::query()
        ->leftJoin('grades', 'student_enrollments.grade_id', '=', 'grades.id')
        ->whereNull('grades.id')
        ->count();

    $orphanEnrollments['missing_section'] = StudentEnrollment::query()
        ->leftJoin('sections', 'student_enrollments.section_id', '=', 'sections.id')
        ->whereNull('sections.id')
        ->count();
}

$report = [
    'tables' => $tables,
    'permissions' => [
        'required' => $requiredPermissions,
        'missing' => $missingPermissions,
        'duplicates' => $permissionDuplicates,
        'non_web_guard_count' => Permission::query()->where('guard_name', '<>', 'web')->count(),
    ],
    'roles' => [
        'duplicates' => $roleDuplicates,
        'non_web_guard_count' => Role::query()->where('guard_name', '<>', 'web')->count(),
    ],
    'student_enrollments' => [
        'total' => $tables['student_enrollments'] ? StudentEnrollment::query()->count() : 0,
        'current' => $tables['student_enrollments'] ? StudentEnrollment::query()->where('is_current', true)->count() : 0,
        'statuses' => $tables['student_enrollments']
            ? StudentEnrollment::query()
                ->select(['status', DB::raw('COUNT(*) as total')])
                ->groupBy('status')
                ->orderBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_enrollment_numbers' => $duplicateEnrollmentNumbers,
        'duplicate_student_year' => $duplicateStudentYear,
        'orphan_enrollments' => $orphanEnrollments,
    ],
    'excel' => [
        'maatwebsite_excel_installed' => class_exists(\Maatwebsite\Excel\Facades\Excel::class),
    ],
    'sample' => [
        'first_enrollments' => $tables['student_enrollments']
            ? StudentEnrollment::query()
                ->with(['student', 'academicYear:id,name', 'grade:id,name', 'section:id,name'])
                ->limit(6)
                ->get()
                ->map(fn (StudentEnrollment $enrollment): array => [
                    'enrollment_number' => $enrollment->enrollment_number,
                    'student' => StudentEnrollment::studentDisplayName($enrollment->student),
                    'year' => $enrollment->academicYear?->name,
                    'grade' => $enrollment->grade?->name,
                    'section' => $enrollment->section?->name,
                    'status' => $enrollment->status,
                    'is_current' => (bool) $enrollment->is_current,
                ])
                ->values()
                ->all()
            : [],
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$hasErrors =
    in_array(false, $tables, true)
    || $missingPermissions !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $duplicateEnrollmentNumbers !== []
    || $duplicateStudentYear !== []
    || array_sum($orphanEnrollments) > 0
    || ! $report['excel']['maatwebsite_excel_installed'];

exit($hasErrors ? 1 : 0);
