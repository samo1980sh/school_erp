<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class GuardiansTemplateExport implements FromArray, WithHeadings, WithColumnWidths, WithTitle, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'رقم ولي الأمر',
            'الاسم الأول',
            'اسم الأب',
            'الكنية',
            'الجنس',
            'صلة القرابة',
            'الرقم الوطني',
            'المهنة',
            'الجوال',
            'الهاتف',
            'البريد الإلكتروني',
            'العنوان',
            'مكان العمل',
            'جهة اتصال طارئة',
            'له حق الحضانة/المتابعة',
            'مسؤول ماليًا',
            'أرقام الطلاب المرتبطين',
            'ملاحظات',
            'الحالة',
        ];
    }

    public function array(): array
    {
        return [
            [
                'GUA-2026-0001',
                'أحمد',
                'محمود',
                'الخطيب',
                'ذكر',
                'الأب',
                'GNID-00000001',
                'مهندس',
                '+963 944 700001',
                '+963 11 700001',
                'guardian1@school-erp.local',
                'دمشق - حي المدارس',
                'شركة خاصة',
                'نعم',
                'نعم',
                'نعم',
                'STD-2026-0001, STD-2026-0002',
                'سطر تجريبي، احذفه عند الاستيراد الفعلي.',
                'active',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 16,
            'C' => 16,
            'D' => 16,
            'E' => 12,
            'F' => 16,
            'G' => 18,
            'H' => 18,
            'I' => 20,
            'J' => 20,
            'K' => 28,
            'L' => 34,
            'M' => 24,
            'N' => 18,
            'O' => 22,
            'P' => 18,
            'Q' => 36,
            'R' => 34,
            'S' => 14,
        ];
    }

    public function title(): string
    {
        return 'Guardians template';
    }
}
