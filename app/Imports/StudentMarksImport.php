<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentMark;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentMarksImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $examCode = trim((string) ($row['exam_code'] ?? ''));
            $studentNumber = trim((string) ($row['student_number'] ?? ''));

            if ($examCode === '' || $studentNumber === '') {
                continue;
            }

            $exam = Exam::query()->where('code', $examCode)->first();
            $student = Student::query()->where('student_number', $studentNumber)->first();

            if (! $exam || ! $student) {
                continue;
            }

            $enrollment = StudentEnrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $exam->academic_year_id)
                ->where('grade_id', $exam->grade_id)
                ->latest('id')
                ->first();

            $status = trim((string) ($row['status'] ?? 'draft'));
            $status = in_array($status, ['draft', 'final', 'absent', 'exempt'], true) ? $status : 'draft';

            $mark = $status === 'absent' || $status === 'exempt'
                ? null
                : ($row['mark'] === null || $row['mark'] === '' ? null : (float) $row['mark']);

            StudentMark::query()->updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                ],
                [
                    'student_enrollment_id' => $enrollment?->id,
                    'academic_year_id' => $exam->academic_year_id,
                    'academic_term_id' => $exam->academic_term_id,
                    'grade_id' => $exam->grade_id,
                    'section_id' => $enrollment?->section_id,
                    'subject_id' => $exam->subject_id,
                    'mark' => $mark,
                    'max_mark' => $exam->max_mark,
                    'status' => $status,
                    'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                    'recorded_at' => in_array($status, ['final', 'absent', 'exempt'], true) ? now() : null,
                ]
            );
        }
    }

    public function rules(): array
    {
        return [
            '*.exam_code' => ['required', 'string', Rule::exists('exams', 'code')],
            '*.student_number' => ['required', 'string', Rule::exists('students', 'student_number')],
            '*.mark' => ['nullable', 'numeric', 'min:0'],
            '*.status' => ['nullable', 'string', Rule::in(['draft', 'final', 'absent', 'exempt'])],
            '*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
