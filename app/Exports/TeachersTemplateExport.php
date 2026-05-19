<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeachersTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'teacher_number',
            'full_name',
            'gender',
            'national_id',
            'birth_date',
            'email',
            'phone',
            'mobile',
            'address',
            'qualification',
            'specialization',
            'job_title',
            'employment_type',
            'hire_date',
            'status',
            'notes',
        ];
    }

    public function array(): array
    {
        return [
            [
                'TCH-2026-0001',
                'أحمد محمود الخطيب',
                'male',
                '01010101001',
                '1986-05-12',
                'teacher@example.local',
                '+963 11 555 1001',
                '+963 944 555 101',
                'دمشق - المزة',
                'إجازة في التربية',
                'اللغة العربية',
                'معلم لغة عربية',
                'full_time',
                '2020-09-01',
                'active',
                'سطر تجريبي يمكن حذفه قبل الاستيراد.',
            ],
        ];
    }
}
