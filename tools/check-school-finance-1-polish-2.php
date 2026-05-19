<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$resourcePath = base_path('app/Filament/Resources/StudentFinancialBalances/StudentFinancialBalanceResource.php');
$viewPath = resource_path('views/filament/finance/student-balance-details.blade.php');

$resourceContent = file_exists($resourcePath) ? (string) file_get_contents($resourcePath) : '';
$viewContent = file_exists($viewPath) ? (string) file_get_contents($viewPath) : '';

$requiredPermissions = [
    'fees.view',
    'fees.reports',
    'fees.export',
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

$viewExists = DB::selectOne("select count(*) as total from information_schema.tables where table_schema = database() and table_name = 'student_financial_balances'")->total > 0;

$balancesCount = $viewExists ? DB::table('student_financial_balances')->count() : 0;

$report = [
    'files' => [
        'resource_exists' => file_exists($resourcePath),
        'blade_exists' => file_exists($viewPath),
        'has_view_details_action' => str_contains($resourceContent, "Action::make('viewDetails')"),
        'uses_modal_content_view' => str_contains($resourceContent, "filament.finance.student-balance-details"),
        'view_has_fee_details_section' => str_contains($viewContent, 'تفاصيل الرسوم') || str_contains($viewContent, 'Fee details'),
        'view_has_payment_receipts_section' => str_contains($viewContent, 'إيصالات الدفع') || str_contains($viewContent, 'Payment receipts'),
    ],
    'tables' => [
        'student_fees' => Schema::hasTable('student_fees'),
        'student_payments' => Schema::hasTable('student_payments'),
        'student_financial_balances' => $viewExists,
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
    'student_balances' => [
        'total' => $balancesCount,
        'with_remaining' => $viewExists ? DB::table('student_financial_balances')->where('total_remaining', '>', 0)->count() : 0,
        'paid' => $viewExists ? DB::table('student_financial_balances')->where('total_remaining', '<=', 0)->count() : 0,
        'overdue' => $viewExists ? DB::table('student_financial_balances')->where('overdue_fees_count', '>', 0)->count() : 0,
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$hasErrors =
    in_array(false, $report['files'], true)
    || in_array(false, $report['tables'], true)
    || $missingPermissions !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $balancesCount <= 0;

exit($hasErrors ? 1 : 0);
