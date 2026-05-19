<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\StudentFee;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentFeesExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['fee_number', 'student_number', 'student_name', 'fee_code', 'fee_name', 'amount', 'discount_amount', 'paid_amount', 'balance_amount', 'due_on', 'status', 'notes'];
    }

    public function array(): array
    {
        return StudentFee::query()
            ->with(['student:id,student_number,first_name,father_name,last_name', 'feeType:id,code,name'])
            ->orderBy('fee_number')
            ->get()
            ->map(fn (StudentFee $fee): array => [
                $fee->fee_number,
                $fee->student?->student_number,
                trim(implode(' ', array_filter([$fee->student?->first_name, $fee->student?->father_name, $fee->student?->last_name]))),
                $fee->feeType?->code,
                $fee->feeType?->name,
                $fee->amount,
                $fee->discount_amount,
                $fee->paid_amount,
                $fee->balance_amount,
                $fee->due_on?->format('Y-m-d'),
                $fee->status,
                $fee->notes,
            ])
            ->toArray();
    }
}
