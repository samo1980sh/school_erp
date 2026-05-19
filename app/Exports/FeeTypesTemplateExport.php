<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FeeTypesTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['code', 'name', 'amount', 'due_on', 'status', 'notes'];
    }

    public function array(): array
    {
        return [];
    }
}
