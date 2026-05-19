<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\StudentFinancialBalance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentFinancialBalancesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return StudentFinancialBalance::query()
            ->orderBy('academic_year_name')
            ->orderBy('student_name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'student_number',
            'student_name',
            'academic_year',
            'fees_count',
            'total_fees',
            'total_paid',
            'total_remaining',
            'overdue_fees_count',
            'last_payment_date',
            'balance_status',
        ];
    }

    public function map($balance): array
    {
        return [
            $balance->student_number,
            $balance->student_name,
            $balance->academic_year_name,
            $balance->fees_count,
            (float) $balance->total_fees,
            (float) $balance->total_paid,
            (float) $balance->total_remaining,
            $balance->overdue_fees_count,
            $balance->last_payment_date?->format('Y-m-d'),
            $balance->balance_status,
        ];
    }
}
