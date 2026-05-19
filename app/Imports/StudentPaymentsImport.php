<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\StudentFee;
use App\Models\StudentPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentPaymentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $year = AcademicYear::query()->where('is_current', true)->first();

        foreach ($rows as $row) {
            $feeNumber = trim((string) ($row['fee_number'] ?? ''));
            $paymentNumber = trim((string) ($row['payment_number'] ?? ''));

            if ($feeNumber === '' || ! $year) {
                continue;
            }

            $fee = StudentFee::query()->where('fee_number', $feeNumber)->first();

            if (! $fee) {
                continue;
            }

            $amount = (float) ($row['amount'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $payment = StudentPayment::query()->updateOrCreate(
                ['payment_number' => $paymentNumber !== '' ? $paymentNumber : 'PAY-' . $year->name . '-' . str_pad((string) (StudentPayment::query()->count() + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'student_fee_id' => $fee->id,
                    'student_id' => $fee->student_id,
                    'academic_year_id' => $year->id,
                    'amount' => $amount,
                    'paid_on' => filled($row['paid_on'] ?? null) ? Carbon::parse($row['paid_on']) : now()->toDateString(),
                    'payment_method' => trim((string) ($row['payment_method'] ?? 'cash')) ?: 'cash',
                    'reference_number' => trim((string) ($row['reference_number'] ?? '')) ?: null,
                    'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                ]
            );

            $paid = (float) $fee->payments()->sum('amount');
            $netAmount = max(((float) $fee->amount) - ((float) $fee->discount_amount), 0);
            $balance = max($netAmount - $paid, 0);

            $fee->forceFill([
                'paid_amount' => $paid,
                'balance_amount' => $balance,
                'status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            ])->save();
        }
    }
}
