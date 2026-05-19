<?php

declare(strict_types=1);

use App\Models\StudentFinancialBalance;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'fees.view',
    'fees.reports',
    'fees.export',
];

$tables = [
    'student_fees' => Schema::hasTable('student_fees'),
    'student_payments' => Schema::hasTable('student_payments'),
    'student_financial_balances' => true,
];

try {
    DB::table('student_financial_balances')->limit(1)->get();
} catch (Throwable) {
    $tables['student_financial_balances'] = false;
}

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

$balances = $tables['student_financial_balances']
    ? StudentFinancialBalance::query()
    : null;

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
    'student_balances' => [
        'total' => $balances ? $balances->count() : 0,
        'with_remaining' => $balances ? (clone $balances)->where('total_remaining', '>', 0)->count() : 0,
        'paid' => $balances ? (clone $balances)->where('total_remaining', '<=', 0)->count() : 0,
        'overdue' => $balances ? (clone $balances)->where('total_remaining', '>', 0)->where('overdue_fees_count', '>', 0)->count() : 0,
    ],
    'sample' => $balances
        ? (clone $balances)
            ->select(['student_number', 'student_name', 'academic_year_name', 'fees_count', 'total_fees', 'total_paid', 'total_remaining', 'overdue_fees_count', 'last_payment_date'])
            ->orderByDesc('total_remaining')
            ->limit(5)
            ->get()
            ->values()
            ->all()
        : [],
];

$hasErrors =
    in_array(false, $tables, true)
    || $report['permissions']['missing'] !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $report['student_balances']['total'] <= 0;

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit($hasErrors ? 1 : 0);
