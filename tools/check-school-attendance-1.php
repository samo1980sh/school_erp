<?php

declare(strict_types=1);

use App\Models\StudentAttendance;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'attendance.view',
    'attendance.create',
    'attendance.update',
    'attendance.reports',
    'attendance.export',
    'attendance.import',
];

$tables = [
    'student_attendances' => Schema::hasTable('student_attendances'),
    'student_enrollments' => Schema::hasTable('student_enrollments'),
    'students' => Schema::hasTable('students'),
    'academic_years' => Schema::hasTable('academic_years'),
    'academic_terms' => Schema::hasTable('academic_terms'),
    'grades' => Schema::hasTable('grades'),
    'sections' => Schema::hasTable('sections'),
    'permissions' => Schema::hasTable('permissions'),
    'roles' => Schema::hasTable('roles'),
];

$existingRequiredPermissions = Permission::query()
    ->where('guard_name', 'web')
    ->whereIn('name', $requiredPermissions)
    ->pluck('name')
    ->all();

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

$duplicateStudentDate = $tables['student_attendances']
    ? StudentAttendance::query()
        ->select(['student_id', 'attendance_date', DB::raw('COUNT(*) as total')])
        ->groupBy('student_id', 'attendance_date')
        ->having('total', '>', 1)
        ->get()
        ->map(fn ($row): array => [
            'student_id' => (int) $row->student_id,
            'attendance_date' => (string) $row->attendance_date,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$orphanAttendance = [
    'missing_student' => 0,
    'missing_enrollment' => 0,
    'missing_year' => 0,
    'missing_term' => 0,
    'missing_grade' => 0,
    'missing_section' => 0,
];

if ($tables['student_attendances']) {
    $orphanAttendance['missing_student'] = StudentAttendance::query()
        ->leftJoin('students', 'student_attendances.student_id', '=', 'students.id')
        ->whereNull('students.id')
        ->count();

    $orphanAttendance['missing_enrollment'] = StudentAttendance::query()
        ->whereNotNull('student_enrollment_id')
        ->leftJoin('student_enrollments', 'student_attendances.student_enrollment_id', '=', 'student_enrollments.id')
        ->whereNull('student_enrollments.id')
        ->count();

    $orphanAttendance['missing_year'] = StudentAttendance::query()
        ->leftJoin('academic_years', 'student_attendances.academic_year_id', '=', 'academic_years.id')
        ->whereNull('academic_years.id')
        ->count();

    $orphanAttendance['missing_term'] = StudentAttendance::query()
        ->whereNotNull('academic_term_id')
        ->leftJoin('academic_terms', 'student_attendances.academic_term_id', '=', 'academic_terms.id')
        ->whereNull('academic_terms.id')
        ->count();

    $orphanAttendance['missing_grade'] = StudentAttendance::query()
        ->leftJoin('grades', 'student_attendances.grade_id', '=', 'grades.id')
        ->whereNull('grades.id')
        ->count();

    $orphanAttendance['missing_section'] = StudentAttendance::query()
        ->leftJoin('sections', 'student_attendances.section_id', '=', 'sections.id')
        ->whereNull('sections.id')
        ->count();
}

$report = [
    'tables' => $tables,
    'permissions' => [
        'required' => $requiredPermissions,
        'missing' => array_values(array_diff($requiredPermissions, $existingRequiredPermissions)),
        'duplicates' => $permissionDuplicates,
        'non_web_guard_count' => Permission::query()->where('guard_name', '<>', 'web')->count(),
    ],
    'roles' => [
        'duplicates' => $roleDuplicates,
        'non_web_guard_count' => Role::query()->where('guard_name', '<>', 'web')->count(),
    ],
    'attendance' => [
        'total' => $tables['student_attendances'] ? StudentAttendance::query()->count() : 0,
        'statuses' => $tables['student_attendances']
            ? StudentAttendance::query()
                ->select(['status', DB::raw('COUNT(*) as total')])
                ->groupBy('status')
                ->orderBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_student_date' => $duplicateStudentDate,
        'orphan_attendance' => $orphanAttendance,
    ],
    'excel' => [
        'maatwebsite_excel_installed' => class_exists(\Maatwebsite\Excel\Facades\Excel::class),
    ],
    'sample' => [
        'first_records' => $tables['student_attendances']
            ? StudentAttendance::query()
                ->with(['student:id,student_number,first_name,last_name', 'academicYear:id,name', 'grade:id,name', 'section:id,name'])
                ->orderByDesc('attendance_date')
                ->limit(5)
                ->get()
                ->map(fn (StudentAttendance $attendance): array => [
                    'student_number' => $attendance->student?->student_number,
                    'student_name' => trim((string) (($attendance->student?->first_name ?? '') . ' ' . ($attendance->student?->last_name ?? ''))),
                    'date' => $attendance->attendance_date?->format('Y-m-d'),
                    'status' => $attendance->status,
                    'year' => $attendance->academicYear?->name,
                    'grade' => $attendance->grade?->name,
                    'section' => $attendance->section?->name,
                ])
                ->values()
                ->all()
            : [],
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$hasErrors =
    in_array(false, $tables, true)
    || $report['permissions']['missing'] !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $duplicateStudentDate !== []
    || array_sum($orphanAttendance) > 0
    || $report['attendance']['total'] < 50
    || ! $report['excel']['maatwebsite_excel_installed'];

exit($hasErrors ? 1 : 0);
