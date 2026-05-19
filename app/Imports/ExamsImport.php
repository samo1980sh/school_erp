<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ExamsImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $yearKey = trim((string) ($row['academic_year_code'] ?? $row['academic_year'] ?? ''));
            $termKey = trim((string) ($row['academic_term_code'] ?? $row['academic_term'] ?? ''));
            $gradeName = trim((string) ($row['grade_name'] ?? $row['grade'] ?? ''));
            $subjectCode = trim((string) ($row['subject_code'] ?? ''));
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($yearKey === '' || $termKey === '' || $gradeName === '' || $subjectCode === '' || $code === '' || $name === '') {
                continue;
            }

            $year = AcademicYear::query()
                ->where('code', $yearKey)
                ->orWhere('name', $yearKey)
                ->first();

            if (! $year) {
                continue;
            }

            $term = AcademicTerm::query()
                ->where('academic_year_id', $year->id)
                ->where(function ($query) use ($termKey): void {
                    $query->where('code', $termKey)->orWhere('name', $termKey);
                })
                ->first();

            $grade = Grade::query()->where('name', $gradeName)->first();
            $subject = Subject::query()->where('code', $subjectCode)->first();

            if (! $term || ! $grade || ! $subject) {
                continue;
            }

            $examType = trim((string) ($row['exam_type'] ?? 'monthly'));
            $examType = in_array($examType, ['quiz', 'monthly', 'midterm', 'final', 'activity'], true) ? $examType : 'monthly';

            $status = trim((string) ($row['status'] ?? 'planned'));
            $status = in_array($status, ['planned', 'published', 'closed', 'cancelled'], true) ? $status : 'planned';

            Exam::query()->updateOrCreate(
                ['code' => $code],
                [
                    'academic_year_id' => $year->id,
                    'academic_term_id' => $term->id,
                    'grade_id' => $grade->id,
                    'subject_id' => $subject->id,
                    'name' => $name,
                    'exam_type' => $examType,
                    'exam_date' => $this->normalizeDate($row['exam_date'] ?? null),
                    'max_mark' => (float) ($row['max_mark'] ?? 100),
                    'passing_mark' => (float) ($row['passing_mark'] ?? 50),
                    'weight_percent' => (float) ($row['weight_percent'] ?? 100),
                    'status' => $status,
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                    'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                ]
            );
        }
    }

    public function rules(): array
    {
        return [
            '*.academic_year_code' => ['nullable', 'string'],
            '*.academic_year' => ['nullable', 'string'],
            '*.academic_term_code' => ['nullable', 'string'],
            '*.academic_term' => ['nullable', 'string'],
            '*.grade_name' => ['nullable', 'string'],
            '*.grade' => ['nullable', 'string'],
            '*.subject_code' => ['required', 'string'],
            '*.code' => ['required', 'string', 'max:255'],
            '*.name' => ['required', 'string', 'max:255'],
            '*.exam_type' => ['nullable', 'string', Rule::in(['quiz', 'monthly', 'midterm', 'final', 'activity'])],
            '*.exam_date' => ['nullable'],
            '*.max_mark' => ['nullable', 'numeric', 'min:1'],
            '*.passing_mark' => ['nullable', 'numeric', 'min:0'],
            '*.weight_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            '*.status' => ['nullable', 'string', Rule::in(['planned', 'published', 'closed', 'cancelled'])],
            '*.sort_order' => ['nullable', 'integer', 'min:0'],
            '*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromDate(1900, 1, 1)
                ->addDays(((int) $value) - 2)
                ->toDateString();
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
