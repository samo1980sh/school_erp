<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\FeeType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FeeTypesExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['code', 'name', 'academic_year', 'grade', 'amount', 'due_on', 'status', 'notes'];
    }

    public function array(): array
    {
        return FeeType::query()
            ->with(['academicYear:id,name', 'grade:id,name'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (FeeType $feeType): array => [
                $feeType->code,
                $feeType->name,
                $feeType->academicYear?->name,
                $feeType->grade?->name,
                $feeType->amount,
                $feeType->due_on?->format('Y-m-d'),
                $feeType->status,
                $feeType->notes,
            ])
            ->toArray();
    }
}
