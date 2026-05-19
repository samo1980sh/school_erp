<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentFeesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $year = AcademicYear::query()->where('is_current', true)->first();

        foreach ($rows as $row) {
            $studentNumber = trim((string) ($row['student_number'] ?? ''));
            $feeCode = trim((string) ($row['fee_code'] ?? ''));
            $feeNumber = trim((string) ($row['fee_number'] ?? ''));

            if ($studentNumber === '' || $feeCode === '' || ! $year) {
                continue;
            }

            $student = Student::query()->where('student_number', $studentNumber)->first();
            $feeType = FeeType::query()->where('code', $feeCode)->first();

            if (! $student || ! $feeType) {
                continue;
            }

            $enrollment = StudentEnrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $year->id)
                ->first();

            $amount = (float) ($row['amount'] ?? $feeType->amount);
            $discount = (float) ($row['discount_amount'] ?? 0);
            $paid = (float) ($row['paid_amount'] ?? 0);
            $balance = max($amount - $discount - $paid, 0);

            StudentFee::query()->updateOrCreate(
                [
                    'fee_type_id' => $feeType->id,
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                ],
                [
                    'fee_number' => $feeNumber !== '' ? $feeNumber : 'SF-' . $year->name . '-' . str_pad((string) (StudentFee::query()->count() + 1), 5, '0', STR_PAD_LEFT),
                    'student_enrollment_id' => $enrollment?->id,
                    'grade_id' => $enrollment?->grade_id,
                    'section_id' => $enrollment?->section_id,
                    'amount' => $amount,
                    'discount_amount' => $discount,
                    'paid_amount' => $paid,
                    'balance_amount' => $balance,
                    'due_on' => filled($row['due_on'] ?? null) ? Carbon::parse($row['due_on']) : $feeType->due_on,
                    'status' => trim((string) ($row['status'] ?? ($balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')))),
                    'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                ]
            );
        }
    }
}
