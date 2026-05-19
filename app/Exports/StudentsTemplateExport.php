<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class StudentsTemplateExport implements FromArray, WithHeadings, WithColumnWidths, WithTitle
{
    public function headings(): array
    {
        return [
            'الرقم المدرسي',
            'الاسم الأول',
            'اسم الأب',
            'اسم الأم',
            'الكنية',
            'الجنس',
            'تاريخ الميلاد',
            'مكان الولادة',
            'الرقم الوطني',
            'تاريخ التسجيل',
            'السنة الدراسية',
            'الصف',
            'الشعبة',
            'الهاتف',
            'البريد الإلكتروني',
            'العنوان',
            'زمرة الدم',
            'ملاحظات صحية',
            'ملاحظات',
            'الحالة',
        ];
    }

    public function array(): array
    {
        return [[
            'STD-2026-0001',
            'أحمد',
            'محمود',
            'فاطمة',
            'الخطيب',
            'ذكر',
            '2015-04-20',
            'دمشق',
            'NID-00000001',
            '2025-09-01',
            '2025-2026',
            'الصف الأول',
            'الشعبة أ',
            '+963 944 555 001',
            'student@example.local',
            'دمشق - المزة',
            'O+',
            '',
            'صف تجريبي داخل قالب الاستيراد',
            'نشط',
        ]];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 16,
            'C' => 16,
            'D' => 16,
            'E' => 16,
            'F' => 12,
            'G' => 16,
            'H' => 18,
            'I' => 18,
            'J' => 16,
            'K' => 18,
            'L' => 18,
            'M' => 16,
            'N' => 20,
            'O' => 26,
            'P' => 30,
            'Q' => 12,
            'R' => 28,
            'S' => 28,
            'T' => 14,
        ];
    }

    public function title(): string
    {
        return 'Students template';
    }
}
