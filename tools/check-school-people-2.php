<?php

declare(strict_types=1);

use App\Models\Guardian;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'guardians.view',
    'guardians.create',
    'guardians.update',
    'guardians.export',
    'guardians.import',
];

$tableExists = [
    'guardians' => Schema::hasTable('guardians'),
    'guardian_student' => Schema::hasTable('guardian_student'),
    'students' => Schema::hasTable('students'),
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

$guardianNumberDuplicates = $tableExists['guardians']
    ? Guardian::query()
        ->select(['guardian_number', DB::raw('COUNT(*) as total')])
        ->groupBy('guardian_number')
        ->having('total', '>', 1)
        ->orderBy('guardian_number')
        ->get()
        ->map(fn ($row): array => ['guardian_number' => $row->guardian_number, 'total' => (int) $row->total])
        ->values()
        ->all()
    : [];

$nationalIdDuplicates = $tableExists['guardians']
    ? Guardian::query()
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

$pivotDuplicates = $tableExists['guardian_student']
    ? DB::table('guardian_student')
        ->select(['guardian_id', 'student_id', DB::raw('COUNT(*) as total')])
        ->groupBy('guardian_id', 'student_id')
        ->having('total', '>', 1)
        ->orderBy('guardian_id')
        ->get()
        ->map(fn ($row): array => [
            'guardian_id' => (int) $row->guardian_id,
            'student_id' => (int) $row->student_id,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$orphanGuardianLinks = [
    'missing_guardian' => 0,
    'missing_student' => 0,
];

if ($tableExists['guardian_student'] && $tableExists['guardians']) {
    $orphanGuardianLinks['missing_guardian'] = DB::table('guardian_student')
        ->leftJoin('guardians', 'guardian_student.guardian_id', '=', 'guardians.id')
        ->whereNull('guardians.id')
        ->count();
}

if ($tableExists['guardian_student'] && $tableExists['students']) {
    $orphanGuardianLinks['missing_student'] = DB::table('guardian_student')
        ->leftJoin('students', 'guardian_student.student_id', '=', 'students.id')
        ->whereNull('students.id')
        ->count();
}

$report = [
    'tables' => $tableExists,

    'excel' => [
        'maatwebsite_excel_installed' => class_exists('Maatwebsite\\Excel\\Facades\\Excel'),
        'guardians_export_class' => class_exists('App\\Exports\\GuardiansExport'),
        'guardians_template_export_class' => class_exists('App\\Exports\\GuardiansTemplateExport'),
        'guardians_import_class' => class_exists('App\\Imports\\GuardiansImport'),
    ],

    'permissions' => [
        'required' => $requiredPermissions,
        'missing' => $missingPermissions,
        'duplicates' => $permissionDuplicates,
        'non_web_guard_count' => Permission::query()->where('guard_name', '<>', 'web')->count(),
        'english_module_translations' => [
            'students.import' => Lang::has('rbac_module_permissions.permissions.students.import.display_name'),
            'guardians.export' => Lang::has('rbac_module_permissions.permissions.guardians.export.display_name'),
            'guardians.import' => Lang::has('rbac_module_permissions.permissions.guardians.import.display_name'),
        ],
    ],

    'roles' => [
        'duplicates' => $roleDuplicates,
        'non_web_guard_count' => Role::query()->where('guard_name', '<>', 'web')->count(),
    ],

    'guardians' => [
        'total' => $tableExists['guardians'] ? Guardian::query()->count() : 0,
        'active' => $tableExists['guardians'] ? Guardian::query()->where('status', 'active')->count() : 0,
        'statuses' => $tableExists['guardians']
            ? Guardian::query()
                ->select(['status', DB::raw('COUNT(*) as total')])
                ->groupBy('status')
                ->orderBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_guardian_numbers' => $guardianNumberDuplicates,
        'duplicate_national_ids' => $nationalIdDuplicates,
    ],

    'guardian_student' => [
        'total_links' => $tableExists['guardian_student'] ? DB::table('guardian_student')->count() : 0,
        'duplicate_links' => $pivotDuplicates,
        'orphan_links' => $orphanGuardianLinks,
    ],

    'sample' => [
        'first_guardians' => $tableExists['guardians']
            ? Guardian::query()
                ->with('students:id,student_number,full_name')
                ->orderBy('guardian_number')
                ->limit(5)
                ->get()
                ->map(fn (Guardian $guardian): array => [
                    'guardian_number' => $guardian->guardian_number,
                    'full_name' => $guardian->display_name,
                    'relation_type' => $guardian->relation_type,
                    'status' => $guardian->status,
                    'students' => $guardian->students->pluck('student_number')->values()->all(),
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
    || in_array(false, $report['excel'], true)
    || in_array(false, $report['permissions']['english_module_translations'], true)
    || $missingPermissions !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $guardianNumberDuplicates !== []
    || $nationalIdDuplicates !== []
    || $pivotDuplicates !== []
    || array_sum($orphanGuardianLinks) > 0
    || $report['guardians']['total'] < 50
    || $report['guardian_student']['total_links'] < 50;

exit($hasErrors ? 1 : 0);
