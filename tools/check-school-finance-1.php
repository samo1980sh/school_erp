<?php

declare(strict_types=1);

use App\Models\FeeType;
use App\Models\StudentFee;
use App\Models\StudentPayment;
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
    'fees.create',
    'fees.update',
    'fees.payments',
    'fees.reports',
    'fees.export',
    'fees.import',
];

$tables = [
    'fee_types' => Schema::hasTable('fee_types'),
    'student_fees' => Schema::hasTable('student_fees'),
    'student_payments' => Schema::hasTable('student_payments'),
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
    ->map(fn ($row): array => ['name' => $row->name, 'guard_name' => $row->guard_name, 'total' => (int) $row->total])
    ->values()
    ->all();

$roleDuplicates = Role::query()
    ->select(['name', 'guard_name', DB::raw('COUNT(*) as total')])
    ->groupBy('name', 'guard_name')
    ->having('total', '>', 1)
    ->orderBy('name')
    ->get()
    ->map(fn ($row): array => ['name' => $row->name, 'guard_name' => $row->guard_name, 'total' => (int) $row->total])
    ->values()
    ->all();

$duplicateFeeCodes = $tables['fee_types'] ? FeeType::query()->select(['code', DB::raw('COUNT(*) as total')])->groupBy('code')->having('total', '>', 1)->get()->values()->all() : [];
$duplicateFeeNumbers = $tables['student_fees'] ? StudentFee::query()->select(['fee_number', DB::raw('COUNT(*) as total')])->groupBy('fee_number')->having('total', '>', 1)->get()->values()->all() : [];
$duplicatePaymentNumbers = $tables['student_payments'] ? StudentPayment::query()->select(['payment_number', DB::raw('COUNT(*) as total')])->groupBy('payment_number')->having('total', '>', 1)->get()->values()->all() : [];
$duplicateStudentFeeAssignments = $tables['student_fees'] ? StudentFee::query()->select(['fee_type_id', 'student_id', 'academic_year_id', DB::raw('COUNT(*) as total')])->groupBy('fee_type_id', 'student_id', 'academic_year_id')->having('total', '>', 1)->get()->values()->all() : [];

$orphanFees = [
    'missing_fee_type' => $tables['student_fees'] ? StudentFee::query()->leftJoin('fee_types', 'student_fees.fee_type_id', '=', 'fee_types.id')->whereNull('fee_types.id')->count() : 0,
    'missing_student' => $tables['student_fees'] ? StudentFee::query()->leftJoin('students', 'student_fees.student_id', '=', 'students.id')->whereNull('students.id')->count() : 0,
    'missing_year' => $tables['student_fees'] ? StudentFee::query()->leftJoin('academic_years', 'student_fees.academic_year_id', '=', 'academic_years.id')->whereNull('academic_years.id')->count() : 0,
];

$orphanPayments = [
    'missing_fee' => $tables['student_payments'] ? StudentPayment::query()->leftJoin('student_fees', 'student_payments.student_fee_id', '=', 'student_fees.id')->whereNull('student_fees.id')->count() : 0,
    'missing_student' => $tables['student_payments'] ? StudentPayment::query()->leftJoin('students', 'student_payments.student_id', '=', 'students.id')->whereNull('students.id')->count() : 0,
    'missing_year' => $tables['student_payments'] ? StudentPayment::query()->leftJoin('academic_years', 'student_payments.academic_year_id', '=', 'academic_years.id')->whereNull('academic_years.id')->count() : 0,
];

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
    'finance' => [
        'fee_types' => ['total' => $tables['fee_types'] ? FeeType::query()->count() : 0, 'duplicate_codes' => $duplicateFeeCodes],
        'student_fees' => ['total' => $tables['student_fees'] ? StudentFee::query()->count() : 0, 'duplicate_fee_numbers' => $duplicateFeeNumbers, 'duplicate_student_fee_assignments' => $duplicateStudentFeeAssignments, 'orphan_fees' => $orphanFees],
        'student_payments' => ['total' => $tables['student_payments'] ? StudentPayment::query()->count() : 0, 'duplicate_payment_numbers' => $duplicatePaymentNumbers, 'orphan_payments' => $orphanPayments],
    ],
    'excel' => [
        'maatwebsite_excel_installed' => class_exists(\Maatwebsite\Excel\Facades\Excel::class),
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$hasErrors = in_array(false, $tables, true)
    || $missingPermissions !== []
    || $permissionDuplicates !== []
    || $roleDuplicates !== []
    || $report['permissions']['non_web_guard_count'] > 0
    || $report['roles']['non_web_guard_count'] > 0
    || $duplicateFeeCodes !== []
    || $duplicateFeeNumbers !== []
    || $duplicatePaymentNumbers !== []
    || $duplicateStudentFeeAssignments !== []
    || array_sum($orphanFees) > 0
    || array_sum($orphanPayments) > 0
    || ($report['finance']['fee_types']['total'] < 6)
    || ($report['finance']['student_fees']['total'] < 50)
    || ($report['finance']['student_payments']['total'] < 20)
    || (! $report['excel']['maatwebsite_excel_installed']);

exit($hasErrors ? 1 : 0);
