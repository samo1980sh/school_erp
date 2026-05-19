<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeesTemplateExport implements FromArray, WithHeadings
{
    public static function templateHeadings(): array
    {
        return [
            'employee_number',
            'first_name',
            'father_name',
            'last_name',
            'gender',
            'birth_date',
            'national_id',
            'marital_status',
            'job_title',
            'department',
            'employment_type',
            'hire_date',
            'contract_type',
            'status',
            'email',
            'phone',
            'mobile',
            'address',
            'qualification',
            'specialization',
            'is_active',
            'notes',
        ];
    }

    public function headings(): array
    {
        return self::templateHeadings();
    }

    public function array(): array
    {
        return [[
            'EMP-0101',
            'أحمد',
            'محمود',
            'الخطيب',
            'male',
            '1988-05-12',
            'EMP-NID-010101',
            'married',
            'موظف شؤون طلاب',
            'شؤون الطلاب',
            'administrative',
            '2020-09-01',
            'full_time',
            'active',
            'employee@example.local',
            '+963 11 5550000',
            '+963 944555000',
            'دمشق - المزة',
            'إجازة جامعية',
            'إدارة أعمال',
            1,
            'مثال توضيحي، احذف هذا السطر قبل الاستيراد.',
        ]];
    }
}
