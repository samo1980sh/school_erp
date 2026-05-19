<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentFeesTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['fee_number', 'student_number', 'fee_code', 'amount', 'discount_amount', 'paid_amount', 'balance_amount', 'due_on', 'status', 'notes'];
    }

    public function array(): array
    {
        return [];
    }
}
