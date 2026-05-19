<?php

declare(strict_types=1);

use App\Models\Teacher;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'teachers.view',
    'teachers.create',
    'teachers.update',
    'teachers.export',
    'teachers.import',
];

$tables = [
    'teachers' => Schema::hasTable('teachers'),
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

$duplicateTeacherNumbers = $tables['teachers']
    ? Teacher::query()
        ->select(['teacher_number', DB::raw('COUNT(*) as total')])
        ->groupBy('teacher_number')
        ->having('total', '>', 1)
        ->orderBy('teacher_number')
        ->get()
        ->map(fn ($row): array => [
            'teacher_number' => $row->teacher_number,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$duplicateNationalIds = $tables['teachers']
    ? Teacher::query()
        ->whereNotNull('national_id')
        ->where('national_id', '<>', '')
        ->select(['national_id', DB::raw('COUNT(*) as total')])
        ->groupBy('national_id')
        ->having('total', '>', 1)
        ->orderBy('national_id')
        ->get()
        ->map(fn ($row): array => [
            'national_id' => $row->national_id,
            'total' => (int) $row->total,
        ])
        ->values()
        ->all()
    : [];

$report = [
    'tables' => $tables,

    'permissions' => [
        'required' => $requiredPermissions,
        'missing' => array_values(array_diff($requiredPermissions, $existingRequiredPermissions)),
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

    'teachers' => [
        'total' => $tables['teachers'] ? Teacher::query()->count() : 0,
        'statuses' => $tables['teachers']
            ? Teacher::query()
                ->select(['status', DB::raw('COUNT(*) as total')])
                ->groupBy('status')
                ->orderBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'employment_types' => $tables['teachers']
            ? Teacher::query()
                ->select(['employment_type', DB::raw('COUNT(*) as total')])
                ->groupBy('employment_type')
                ->orderBy('employment_type')
                ->pluck('total', 'employment_type')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_teacher_numbers' => $duplicateTeacherNumbers,
        'duplicate_national_ids' => $duplicateNationalIds,
    ],

    'excel' => [
        'maatwebsite_excel_installed' => class_exists(\Maatwebsite\Excel\Facades\Excel::class),
    ],

    'sample' => [
        'first_teachers' => $tables['teachers']
            ? Teacher::query()
                ->select(['teacher_number', 'full_name', 'specialization', 'mobile', 'status'])
                ->orderBy('teacher_number')
                ->limit(5)
                ->get()
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
    in_array(false, $tables, true)
    || $report['permissions']['missing'] !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $duplicateTeacherNumbers !== []
    || $duplicateNationalIds !== []
    || $report['teachers']['total'] < 50
    || $report['excel']['maatwebsite_excel_installed'] !== true;

exit($hasErrors ? 1 : 0);
