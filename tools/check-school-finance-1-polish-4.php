<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$requiredPermissions = [
    'fees.view',
    'fees.create',
    'fees.update',
    'fees.payments',
    'fees.reports',
    'fees.export',
    'fees.import',
];

$existingPermissions = Permission::query()
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

$duplicateFeeNumbers = Schema::hasTable('student_fees')
    ? DB::table('student_fees')
        ->select(['fee_number', DB::raw('COUNT(*) as total')])
        ->groupBy('fee_number')
        ->having('total', '>', 1)
        ->get()
        ->values()
        ->all()
    : [];

$duplicatePaymentNumbers = Schema::hasTable('student_payments')
    ? DB::table('student_payments')
        ->select(['payment_number', DB::raw('COUNT(*) as total')])
        ->groupBy('payment_number')
        ->having('total', '>', 1)
        ->get()
        ->values()
        ->all()
    : [];

$resourceFiles = [
    'student_fee_resource' => base_path('app/Filament/Resources/StudentFees/StudentFeeResource.php'),
    'student_payment_resource' => base_path('app/Filament/Resources/StudentPayments/StudentPaymentResource.php'),
];

$manageFiles = [
    'manage_student_fees' => base_path('app/Filament/Resources/StudentFees/Pages/ManageStudentFees.php'),
    'manage_student_payments' => base_path('app/Filament/Resources/StudentPayments/Pages/ManageStudentPayments.php'),
];

$resourceChecks = [];

foreach ($resourceFiles as $key => $path) {
    $content = is_file($path) ? (string) file_get_contents($path) : '';

    $resourceChecks[$key] = [
        'exists' => is_file($path),
        'finance_group' => str_contains($content, 'Finance & Fees') && str_contains($content, 'المالية والرسوم'),
        'navigation_label' => str_contains($content, 'getNavigationLabel'),
        'filters' => [
            'student_id' => str_contains($content, "SelectFilter::make('student_id')"),
            'academic_year_id' => str_contains($content, "SelectFilter::make('academic_year_id')"),
            'fee_type_id' => str_contains($content, "SelectFilter::make('fee_type_id')"),
            'status_or_payment_method' => str_contains($content, "SelectFilter::make('status')")
                || str_contains($content, "SelectFilter::make('payment_method')"),
        ],
    ];
}

$manageChecks = [];

foreach ($manageFiles as $key => $path) {
    $content = is_file($path) ? (string) file_get_contents($path) : '';

    $manageChecks[$key] = [
        'exists' => is_file($path),
        'balances_shortcut' => str_contains($content, 'openBalances')
            || str_contains($content, 'StudentFinancialBalanceResource'),
        'audit_subheading' => str_contains($content, 'getSubheading')
            && (
                str_contains($content, 'أرصدة الطلاب')
                || str_contains($content, 'Student Balances')
            ),
    ];
}

$report = [
    'permissions' => [
        'required' => $requiredPermissions,
        'missing' => array_values(array_diff($requiredPermissions, $existingPermissions)),
        'duplicates' => $permissionDuplicates,
        'non_web_guard_count' => Permission::query()->where('guard_name', '<>', 'web')->count(),
    ],
    'finance_records' => [
        'duplicate_fee_numbers' => $duplicateFeeNumbers,
        'duplicate_payment_numbers' => $duplicatePaymentNumbers,
    ],
    'ui_checks' => [
        'resources' => $resourceChecks,
        'manage_pages' => $manageChecks,
        'expected_note' => 'Finance group belongs to Resource files. Manage pages only need the Student Balances shortcut and audit subheading.',
    ],
];

echo json_encode(
    $report,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) . PHP_EOL;

$resourceHasErrors = in_array(
    false,
    array_map(
        fn (array $check): bool => $check['exists']
            && $check['finance_group']
            && $check['navigation_label']
            && ! in_array(false, $check['filters'], true),
        $resourceChecks
    ),
    true
);

$manageHasErrors = in_array(
    false,
    array_map(
        fn (array $check): bool => $check['exists']
            && $check['balances_shortcut']
            && $check['audit_subheading'],
        $manageChecks
    ),
    true
);

$hasErrors =
    $report['permissions']['missing'] !== []
    || $report['permissions']['duplicates'] !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $duplicateFeeNumbers !== []
    || $duplicatePaymentNumbers !== []
    || $resourceHasErrors
    || $manageHasErrors;

exit($hasErrors ? 1 : 0);