<?php

declare(strict_types=1);

use App\Models\Student;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'students.view',
    'students.create',
    'students.update',
    'students.export',
    'students.import',
];

$tableExists = [
    'students' => Schema::hasTable('students'),
    'academic_years' => Schema::hasTable('academic_years'),
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

$studentNumberDuplicates = $tableExists['students']
    ? Student::query()
        ->select(['student_number', DB::raw('COUNT(*) as total')])
        ->groupBy('student_number')
        ->having('total', '>', 1)
        ->orderBy('student_number')
        ->get()
        ->map(fn ($row): array => ['student_number' => $row->student_number, 'total' => (int) $row->total])
        ->values()
        ->all()
    : [];

$nationalIdDuplicates = $tableExists['students']
    ? Student::query()
        ->select(['national_id', DB::raw('COUNT(*) as total')])
        ->whereNotNull('national_id')
        ->where('national_id', '<>', '')
        ->groupBy('national_id')
        ->having('total', '>', 1)
        ->orderBy('national_id')
        ->get()
        ->map(fn ($row): array => ['national_id' => $row->national_id, 'total' => (int) $row->total])
        ->values()
        ->all()
    : [];

$orphanStudents = ['missing_year' => 0, 'missing_grade' => 0, 'missing_section' => 0];

if ($tableExists['students']) {
    $orphanStudents['missing_year'] = Student::query()
        ->leftJoin('academic_years', 'students.current_academic_year_id', '=', 'academic_years.id')
        ->whereNotNull('students.current_academic_year_id')
        ->whereNull('academic_years.id')
        ->count();

    $orphanStudents['missing_grade'] = Student::query()
        ->leftJoin('grades', 'students.current_grade_id', '=', 'grades.id')
        ->whereNotNull('students.current_grade_id')
        ->whereNull('grades.id')
        ->count();

    $orphanStudents['missing_section'] = Student::query()
        ->leftJoin('sections', 'students.current_section_id', '=', 'sections.id')
        ->whereNotNull('students.current_section_id')
        ->whereNull('sections.id')
        ->count();
}

$report = [
    'tables' => $tableExists,
    'excel' => [
        'maatwebsite_excel_installed' => class_exists('Maatwebsite\\Excel\\Facades\\Excel'),
        'students_export_class' => class_exists('App\\Exports\\StudentsExport'),
        'students_template_export_class' => class_exists('App\\Exports\\StudentsTemplateExport'),
        'students_import_class' => class_exists('App\\Imports\\StudentsImport'),
    ],
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
    'students' => [
        'total' => $tableExists['students'] ? Student::query()->count() : 0,
        'active' => $tableExists['students'] ? Student::query()->where('status', 'active')->count() : 0,
        'statuses' => $tableExists['students']
            ? Student::query()
                ->select(['status', DB::raw('COUNT(*) as total')])
                ->groupBy('status')
                ->orderBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_student_numbers' => $studentNumberDuplicates,
        'duplicate_national_ids' => $nationalIdDuplicates,
        'orphan_students' => $orphanStudents,
    ],
    'sample' => [
        'first_students' => $tableExists['students']
            ? Student::query()
                ->with(['academicYear:id,name', 'grade:id,name', 'section:id,name'])
                ->orderBy('student_number')
                ->limit(5)
                ->get()
                ->map(fn (Student $student): array => [
                    'student_number' => $student->student_number,
                    'full_name' => $student->display_name,
                    'gender' => $student->gender,
                    'status' => $student->status,
                    'year' => $student->academicYear?->name,
                    'grade' => $student->grade?->name,
                    'section' => $student->section?->name,
                ])
                ->values()
                ->all()
            : [],
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$hasErrors =
    in_array(false, $tableExists, true)
    || in_array(false, $report['excel'], true)
    || $missingPermissions !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $studentNumberDuplicates !== []
    || $nationalIdDuplicates !== []
    || array_sum($orphanStudents) > 0
    || $report['students']['total'] < 50;

exit($hasErrors ? 1 : 0);
