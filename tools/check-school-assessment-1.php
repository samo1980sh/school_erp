<?php

declare(strict_types=1);

use App\Models\Exam;
use App\Models\StudentMark;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'exams.view',
    'exams.create',
    'exams.update',
    'exams.export',
    'exams.import',
    'marks.view',
    'marks.create',
    'marks.update',
    'marks.reports',
    'marks.export',
    'marks.import',
];

$tableExists = [
    'exams' => Schema::hasTable('exams'),
    'student_marks' => Schema::hasTable('student_marks'),
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

$duplicateExamCodes = $tableExists['exams']
    ? Exam::query()
        ->select(['code', DB::raw('COUNT(*) as total')])
        ->groupBy('code')
        ->having('total', '>', 1)
        ->orderBy('code')
        ->get()
        ->map(fn ($row): array => ['code' => $row->code, 'total' => (int) $row->total])
        ->values()
        ->all()
    : [];

$duplicateExamStudentMarks = $tableExists['student_marks']
    ? StudentMark::query()
        ->select(['exam_id', 'student_id', DB::raw('COUNT(*) as total')])
        ->groupBy('exam_id', 'student_id')
        ->having('total', '>', 1)
        ->orderBy('exam_id')
        ->get()
        ->map(fn ($row): array => [
            'exam_id' => (int) $row->exam_id,
            'student_id' => (int) $row->student_id,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$orphanMarks = [
    'missing_exam' => 0,
    'missing_student' => 0,
    'missing_enrollment' => 0,
    'missing_year' => 0,
    'missing_term' => 0,
    'missing_grade' => 0,
    'missing_section' => 0,
    'missing_subject' => 0,
];

if ($tableExists['student_marks']) {
    $orphanMarks = [
        'missing_exam' => StudentMark::query()->leftJoin('exams', 'student_marks.exam_id', '=', 'exams.id')->whereNull('exams.id')->count(),
        'missing_student' => StudentMark::query()->leftJoin('students', 'student_marks.student_id', '=', 'students.id')->whereNull('students.id')->count(),
        'missing_enrollment' => StudentMark::query()->whereNotNull('student_enrollment_id')->leftJoin('student_enrollments', 'student_marks.student_enrollment_id', '=', 'student_enrollments.id')->whereNull('student_enrollments.id')->count(),
        'missing_year' => StudentMark::query()->leftJoin('academic_years', 'student_marks.academic_year_id', '=', 'academic_years.id')->whereNull('academic_years.id')->count(),
        'missing_term' => StudentMark::query()->leftJoin('academic_terms', 'student_marks.academic_term_id', '=', 'academic_terms.id')->whereNull('academic_terms.id')->count(),
        'missing_grade' => StudentMark::query()->leftJoin('grades', 'student_marks.grade_id', '=', 'grades.id')->whereNull('grades.id')->count(),
        'missing_section' => StudentMark::query()->whereNotNull('section_id')->leftJoin('sections', 'student_marks.section_id', '=', 'sections.id')->whereNull('sections.id')->count(),
        'missing_subject' => StudentMark::query()->leftJoin('subjects', 'student_marks.subject_id', '=', 'subjects.id')->whereNull('subjects.id')->count(),
    ];
}

$report = [
    'tables' => $tableExists,
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
    'exams' => [
        'total' => $tableExists['exams'] ? Exam::query()->count() : 0,
        'minimum_expected' => 1,
        'statuses' => $tableExists['exams']
            ? Exam::query()->select(['status', DB::raw('COUNT(*) as total')])->groupBy('status')->orderBy('status')->pluck('total', 'status')->map(fn ($value): int => (int) $value)->toArray()
            : [],
        'duplicate_codes' => $duplicateExamCodes,
    ],
    'student_marks' => [
        'total' => $tableExists['student_marks'] ? StudentMark::query()->count() : 0,
        'minimum_expected' => 50,
        'statuses' => $tableExists['student_marks']
            ? StudentMark::query()->select(['status', DB::raw('COUNT(*) as total')])->groupBy('status')->orderBy('status')->pluck('total', 'status')->map(fn ($value): int => (int) $value)->toArray()
            : [],
        'duplicate_exam_student' => $duplicateExamStudentMarks,
        'orphan_marks' => array_map('intval', $orphanMarks),
    ],
    'excel' => [
        'maatwebsite_excel_installed' => class_exists(\Maatwebsite\Excel\Facades\Excel::class),
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$hasErrors =
    in_array(false, $tableExists, true)
    || $missingPermissions !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $duplicateExamCodes !== []
    || $duplicateExamStudentMarks !== []
    || array_sum(array_map('intval', $orphanMarks)) > 0
    || ! $report['excel']['maatwebsite_excel_installed']
    || $report['exams']['total'] < 1
    || $report['student_marks']['total'] < 50;

exit($hasErrors ? 1 : 0);
