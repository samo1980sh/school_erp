<?php

declare(strict_types=1);

use App\Models\Employee;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'employees.view',
    'employees.create',
    'employees.update',
    'employees.export',
    'employees.import',
];

$tables = [
    'employees' => Schema::hasTable('employees'),
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

$duplicateEmployeeNumbers = $tables['employees']
    ? Employee::query()
        ->select(['employee_number', DB::raw('COUNT(*) as total')])
        ->groupBy('employee_number')
        ->having('total', '>', 1)
        ->orderBy('employee_number')
        ->get()
        ->map(fn ($row): array => ['employee_number' => $row->employee_number, 'total' => (int) $row->total])
        ->values()
        ->all()
    : [];

$duplicateNationalIds = $tables['employees']
    ? Employee::query()
        ->whereNotNull('national_id')
        ->where('national_id', '<>', '')
        ->select(['national_id', DB::raw('COUNT(*) as total')])
        ->groupBy('national_id')
        ->having('total', '>', 1)
        ->orderBy('national_id')
        ->get()
        ->map(fn ($row): array => ['national_id' => $row->national_id, 'total' => (int) $row->total])
        ->values()
        ->all()
    : [];

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
    'employees' => [
        'total' => $tables['employees'] ? Employee::query()->count() : 0,
        'active' => $tables['employees'] ? Employee::query()->where('is_active', true)->count() : 0,
        'statuses' => $tables['employees']
            ? Employee::query()
                ->select(['status', DB::raw('COUNT(*) as total')])
                ->groupBy('status')
                ->orderBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->toArray()
            : [],
        'duplicate_employee_numbers' => $duplicateEmployeeNumbers,
        'duplicate_national_ids' => $duplicateNationalIds,
    ],
    'excel' => [
        'maatwebsite_excel_installed' => class_exists(\Maatwebsite\Excel\Facades\Excel::class),
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
    || $duplicateEmployeeNumbers !== []
    || $duplicateNationalIds !== []
    || $report['employees']['total'] < 50
    || ! $report['excel']['maatwebsite_excel_installed'];

exit($hasErrors ? 1 : 0);
