<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Guardian;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GuardiansExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithTitle, ShouldAutoSize
{
    public function query()
    {
        return Guardian::query()
            ->with('students:id,student_number,full_name')
            ->orderBy('guardian_number');
    }

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

    public function map($row): array
    {
        /** @var Guardian $row */
        return [
            $row->guardian_number,
            $row->first_name,
            $row->father_name,
            $row->last_name,
            $row->gender === 'female' ? 'أنثى' : 'ذكر',
            $this->relationLabel((string) $row->relation_type),
            $row->national_id,
            $row->occupation,
            $row->mobile,
            $row->phone,
            $row->email,
            $row->address,
            $row->workplace,
            $row->is_emergency_contact ? 'نعم' : 'لا',
            $row->has_custody ? 'نعم' : 'لا',
            $row->is_financial_responsible ? 'نعم' : 'لا',
            $row->students->pluck('student_number')->implode(', '),
            $row->notes,
            $row->status,
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
            'Q' => 32,
            'R' => 34,
            'S' => 14,
        ];
    }

    public function title(): string
    {
        return 'Guardians';
    }

    private function relationLabel(string $relation): string
    {
        return match ($relation) {
            'father' => 'الأب',
            'mother' => 'الأم',
            'grandfather' => 'الجد',
            'grandmother' => 'الجدة',
            'uncle' => 'العم/الخال',
            'aunt' => 'العمة/الخالة',
            default => 'وصي/آخر',
        };
    }
}
