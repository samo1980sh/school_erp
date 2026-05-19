<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\StudentEnrollment;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentEnrollmentsExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'enrollment_number',
            'student_number',
            'student_name',
            'academic_year_code',
            'academic_term_code',
            'grade_code',
            'section_code',
            'enrollment_date',
            'enrollment_type',
            'status',
            'is_current',
            'previous_school',
            'notes',
        ];
    }

    public function array(): array
    {
        return StudentEnrollment::query()
            ->with(['student', 'academicYear', 'academicTerm', 'grade', 'section'])
            ->orderByDesc('is_current')
            ->orderByDesc('enrollment_date')
            ->get()
            ->map(fn (StudentEnrollment $enrollment): array => [
                'enrollment_number' => $enrollment->enrollment_number,
                'student_number' => $enrollment->student?->student_number,
                'student_name' => StudentEnrollment::studentDisplayName($enrollment->student),
                'academic_year_code' => $enrollment->academicYear?->code,
                'academic_term_code' => $enrollment->academicTerm?->code,
                'grade_code' => $enrollment->grade?->code,
                'section_code' => $enrollment->section?->code,
                'enrollment_date' => $enrollment->enrollment_date?->format('Y-m-d'),
                'enrollment_type' => $enrollment->enrollment_type,
                'status' => $enrollment->status,
                'is_current' => $enrollment->is_current ? 1 : 0,
                'previous_school' => $enrollment->previous_school,
                'notes' => $enrollment->notes,
            ])
            ->values()
            ->all();
    }
}
