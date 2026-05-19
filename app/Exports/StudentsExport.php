<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class StudentsExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithTitle
{
    public function query(): Builder
    {
        return Student::query()
            ->with(['academicYear:id,name', 'grade:id,name', 'section:id,name'])
            ->orderBy('student_number');
    }

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

    public function map($row): array
    {
        /** @var Student $row */
        return [
            $row->student_number,
            $row->first_name,
            $row->father_name,
            $row->mother_name,
            $row->last_name,
            $row->gender === 'female' ? 'أنثى' : 'ذكر',
            $row->birth_date?->format('Y-m-d'),
            $row->place_of_birth,
            $row->national_id,
            $row->enrollment_date?->format('Y-m-d'),
            $row->academicYear?->name,
            $row->grade?->name,
            $row->section?->name,
            $row->phone,
            $row->email,
            $row->address,
            $row->blood_type,
            $row->medical_notes,
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
        return 'Students';
    }
}
