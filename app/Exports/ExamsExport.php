<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Exam;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExamsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return Exam::query()
            ->with([
                'academicYear:id,name,code',
                'academicTerm:id,name,code',
                'grade:id,name',
                'subject:id,name,code',
            ])
            ->orderByDesc('exam_date')
            ->orderBy('sort_order')
            ->get();
    }

    public function headings(): array
    {
        return [
            'academic_year',
            'academic_year_code',
            'academic_term',
            'academic_term_code',
            'grade',
            'subject',
            'subject_code',
            'code',
            'name',
            'exam_type',
            'exam_date',
            'max_mark',
            'passing_mark',
            'weight_percent',
            'status',
            'sort_order',
            'notes',
        ];
    }

    public function map($row): array
    {
        return [
            $row->academicYear?->name,
            $row->academicYear?->code,
            $row->academicTerm?->name,
            $row->academicTerm?->code,
            $row->grade?->name,
            $row->subject?->name,
            $row->subject?->code,
            $row->code,
            $row->name,
            $row->exam_type,
            $row->exam_date?->format('Y-m-d'),
            $row->max_mark,
            $row->passing_mark,
            $row->weight_percent,
            $row->status,
            $row->sort_order,
            $row->notes,
        ];
    }
}
