<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentEnrollmentsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'enrollment_number',
            'student_number',
            'academic_year_code',
            'academic_term_code',
            'grade_code',
            'section_code',
            'enrollment_date',
            'enrollment_type',
            'status',
            'is_current',
            'previous_school',
            'notes',
        ];
    }

    public function array(): array
    {
        return [
            [
                'ENR-2025-9999',
                'STU-2025-0001',
                'AY-2025-2026',
                'AY-2025-2026-T1',
                'KG1',
                'KG1-A',
                now()->format('Y-m-d'),
                'new',
                'enrolled',
                1,
                '',
                'Sample row - replace values before import.',
            ],
        ];
    }
}
