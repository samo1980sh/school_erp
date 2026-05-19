<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Exam;
use App\Models\StudentEnrollment;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentMarksTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'exam_code',
            'student_number',
            'mark',
            'status',
            'notes',
        ];
    }

    public function array(): array
    {
        $exam = Exam::query()->orderByDesc('id')->first();

        $enrollments = StudentEnrollment::query()
            ->with('student:id,student_number')
            ->when($exam, fn ($query) => $query
                ->where('academic_year_id', $exam->academic_year_id)
                ->where('grade_id', $exam->grade_id))
            ->limit(8)
            ->get();

        if (! $exam || $enrollments->isEmpty()) {
            return [
                ['EX-2025-2026-G1-ARABIC-MONTHLY', 'STU-2026-0001', 85, 'final', 'Optional note'],
            ];
        }

        return $enrollments
            ->map(fn ($enrollment): array => [
                $exam->code,
                $enrollment->student?->student_number,
                '',
                'draft',
                '',
            ])
            ->values()
            ->all();
    }
}
