<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\FeeType;
use App\Models\Grade;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\StudentPayment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FinanceFoundationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ensurePermissions();

        $year = AcademicYear::query()->where('is_current', true)->first()
            ?? AcademicYear::query()->orderByDesc('starts_on')->first();

        if (! $year) {
            return;
        }

        $grades = Grade::query()->orderBy('sort_order')->orderBy('name')->get();

        $feeDefinitions = [
            ['code' => 'TUITION', 'name' => 'القسط الدراسي', 'amount' => 1500000, 'sort_order' => 10],
            ['code' => 'REGISTRATION', 'name' => 'رسم التسجيل', 'amount' => 250000, 'sort_order' => 20],
            ['code' => 'BOOKS', 'name' => 'رسوم الكتب والقرطاسية', 'amount' => 180000, 'sort_order' => 30],
            ['code' => 'TRANSPORT', 'name' => 'رسوم النقل المدرسي', 'amount' => 300000, 'sort_order' => 40],
            ['code' => 'ACTIVITIES', 'name' => 'رسوم الأنشطة', 'amount' => 75000, 'sort_order' => 50],
            ['code' => 'UNIFORM', 'name' => 'رسوم الزي المدرسي', 'amount' => 120000, 'sort_order' => 60],
        ];

        foreach ($feeDefinitions as $definition) {
            FeeType::query()->updateOrCreate(
                ['code' => 'FY-' . $year->name . '-' . $definition['code']],
                [
                    'academic_year_id' => $year->id,
                    'grade_id' => null,
                    'sort_order' => $definition['sort_order'],
                    'name' => $definition['name'],
                    'amount' => $definition['amount'],
                    'due_on' => Carbon::parse($year->starts_on)->addDays(30),
                    'status' => 'active',
                    'notes' => 'بيانات تجريبية قابلة للتعديل حسب سياسة المدرسة المالية.',
                ]
            );
        }

        $gradeBaseFees = [
            800000, 850000, 900000, 950000, 1000000, 1100000, 1200000, 1300000, 1400000,
        ];

        foreach ($grades as $index => $grade) {
            FeeType::query()->updateOrCreate(
                ['code' => 'FY-' . $year->name . '-GRADE-' . str_pad((string) $grade->id, 3, '0', STR_PAD_LEFT)],
                [
                    'academic_year_id' => $year->id,
                    'grade_id' => $grade->id,
                    'sort_order' => 100 + ($index * 10),
                    'name' => 'قسط ' . $grade->name,
                    'amount' => $gradeBaseFees[$index % count($gradeBaseFees)],
                    'due_on' => Carbon::parse($year->starts_on)->addDays(45),
                    'status' => 'active',
                    'notes' => 'قسط تجريبي مرتبط بالصف الدراسي.',
                ]
            );
        }

        $feeTypes = FeeType::query()
            ->where('academic_year_id', $year->id)
            ->orderBy('sort_order')
            ->get();

        $enrollments = StudentEnrollment::query()
            ->with(['student', 'grade', 'section'])
            ->where('academic_year_id', $year->id)
            ->limit(80)
            ->get();

        $employees = Employee::query()->orderBy('id')->limit(10)->get();

        $counter = 1;

        foreach ($enrollments as $enrollmentIndex => $enrollment) {
            $student = $enrollment->student;

            if (! $student) {
                continue;
            }

            $studentFeeTypes = $feeTypes
                ->filter(fn (FeeType $feeType): bool => $feeType->grade_id === null || $feeType->grade_id === $enrollment->grade_id)
                ->take(4);

            foreach ($studentFeeTypes as $feeType) {
                $discount = $counter % 7 === 0 ? round(((float) $feeType->amount) * 0.10, 2) : 0;
                $netAmount = max(((float) $feeType->amount) - $discount, 0);

                $paymentScenario = $counter % 4;
                $paidAmount = match ($paymentScenario) {
                    0 => $netAmount,
                    1 => round($netAmount / 2, 2),
                    2 => 0,
                    default => round($netAmount * 0.75, 2),
                };

                $balance = round($netAmount - $paidAmount, 2);
                $status = match (true) {
                    $balance <= 0 => 'paid',
                    $paidAmount > 0 => 'partial',
                    default => 'unpaid',
                };

                $studentFee = StudentFee::query()->updateOrCreate(
                    [
                        'fee_type_id' => $feeType->id,
                        'student_id' => $student->id,
                        'academic_year_id' => $year->id,
                    ],
                    [
                        'fee_number' => 'SF-' . $year->name . '-' . str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
                        'student_enrollment_id' => $enrollment->id,
                        'grade_id' => $enrollment->grade_id,
                        'section_id' => $enrollment->section_id,
                        'amount' => $feeType->amount,
                        'discount_amount' => $discount,
                        'paid_amount' => $paidAmount,
                        'balance_amount' => $balance,
                        'due_on' => $feeType->due_on,
                        'status' => $status,
                        'notes' => 'رسم تجريبي منشأ من Seeder المالية.',
                    ]
                );

                if ($paidAmount > 0) {
                    StudentPayment::query()->updateOrCreate(
                        ['payment_number' => 'PAY-' . $year->name . '-' . str_pad((string) $counter, 5, '0', STR_PAD_LEFT)],
                        [
                            'student_fee_id' => $studentFee->id,
                            'student_id' => $student->id,
                            'academic_year_id' => $year->id,
                            'amount' => $paidAmount,
                            'paid_on' => Carbon::parse($year->starts_on)->addDays(60 + ($enrollmentIndex % 30)),
                            'payment_method' => ['cash', 'bank_transfer', 'card'][$counter % 3],
                            'reference_number' => 'REF-' . str_pad((string) $counter, 6, '0', STR_PAD_LEFT),
                            'received_by_employee_id' => $employees->isNotEmpty() ? $employees[$counter % $employees->count()]->id : null,
                            'notes' => 'دفعة تجريبية مرتبطة برسم طالب.',
                        ]
                    );
                }

                $counter++;
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensurePermissions(): void
    {
        $permissions = [
            ['name' => 'fees.export', 'display_name' => 'تصدير الرسوم', 'description' => 'يسمح بتصدير بيانات الرسوم والمدفوعات إلى Excel.', 'sort_order' => 711],
            ['name' => 'fees.import', 'display_name' => 'استيراد الرسوم', 'description' => 'يسمح باستيراد بيانات الرسوم والمدفوعات من Excel.', 'sort_order' => 712],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'group_name' => 'المالية والرسوم',
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'sort_order' => $permission['sort_order'],
                ]
            );
        }
    }
}
