<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentAttendancesTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return [
            'student_number',
            'enrollment_number',
            'attendance_date',
            'status',
            'arrival_time',
            'departure_time',
            'minutes_late',
            'excuse_reason',
            'notes',
        ];
    }

    public function array(): array
    {
        return [
            [
                'STU-2026-0001',
                'ENR-2026-0001',
                now()->toDateString(),
                'present',
                '08:00',
                '13:30',
                '0',
                '',
                'Sample row. Allowed status values: present, absent, late, excused.',
            ],
        ];
    }
}
