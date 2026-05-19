<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\StudentMark;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentMarksExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return StudentMark::query()
            ->with([
                'exam:id,name,code,exam_type',
                'student:id,student_number,first_name,father_name,last_name',
                'academicYear:id,name',
                'academicTerm:id,name',
                'grade:id,name',
                'section:id,name',
                'subject:id,name,code',
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'exam_code',
            'exam_name',
            'student_number',
            'student_name',
            'academic_year',
            'term',
            'grade',
            'section',
            'subject',
            'mark',
            'max_mark',
            'status',
            'notes',
        ];
    }

    public function map($row): array
    {
        return [
            $row->exam?->code,
            $row->exam?->name,
            $row->student?->student_number,
            trim(implode(' ', array_filter([
                $row->student?->first_name,
                $row->student?->father_name,
                $row->student?->last_name,
            ]))),
            $row->academicYear?->name,
            $row->academicTerm?->name,
            $row->grade?->name,
            $row->section?->name,
            $row->subject?->name,
            $row->mark,
            $row->max_mark,
            $row->status,
            $row->notes,
        ];
    }
}
