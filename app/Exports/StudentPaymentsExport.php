<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\StudentPayment;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentPaymentsExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['payment_number', 'fee_number', 'student_number', 'student_name', 'amount', 'paid_on', 'payment_method', 'reference_number', 'notes'];
    }

    public function array(): array
    {
        return StudentPayment::query()
            ->with(['student:id,student_number,first_name,father_name,last_name', 'studentFee:id,fee_number'])
            ->orderByDesc('paid_on')
            ->get()
            ->map(fn (StudentPayment $payment): array => [
                $payment->payment_number,
                $payment->studentFee?->fee_number,
                $payment->student?->student_number,
                trim(implode(' ', array_filter([$payment->student?->first_name, $payment->student?->father_name, $payment->student?->last_name]))),
                $payment->amount,
                $payment->paid_on?->format('Y-m-d'),
                $payment->payment_method,
                $payment->reference_number,
                $payment->notes,
            ])
            ->toArray();
    }
}
