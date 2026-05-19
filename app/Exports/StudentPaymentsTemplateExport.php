<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentPaymentsTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['payment_number', 'fee_number', 'student_number', 'amount', 'paid_on', 'payment_method', 'reference_number', 'notes'];
    }

    public function array(): array
    {
        return [];
    }
}
