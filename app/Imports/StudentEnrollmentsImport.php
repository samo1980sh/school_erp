<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolSection;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentEnrollmentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $studentNumber = trim((string) ($row['student_number'] ?? ''));
            $academicYearCode = trim((string) ($row['academic_year_code'] ?? ''));
            $gradeCode = trim((string) ($row['grade_code'] ?? ''));
            $sectionCode = trim((string) ($row['section_code'] ?? ''));

            if ($studentNumber === '' || $academicYearCode === '' || $gradeCode === '' || $sectionCode === '') {
                continue;
            }

            $student = Student::query()->where('student_number', $studentNumber)->first();
            $academicYear = AcademicYear::query()->where('code', $academicYearCode)->first();
            $grade = Grade::query()->where('code', $gradeCode)->first();
            $section = SchoolSection::query()->where('code', $sectionCode)->first();

            if (! $student || ! $academicYear || ! $grade || ! $section) {
                continue;
            }

            $academicTermCode = trim((string) ($row['academic_term_code'] ?? ''));
            $academicTerm = $academicTermCode !== ''
                ? AcademicTerm::query()->where('code', $academicTermCode)->first()
                : null;

            $enrollmentNumber = trim((string) ($row['enrollment_number'] ?? ''));

            if ($enrollmentNumber === '') {
                $enrollmentNumber = sprintf('ENR-%s-%04d', str_replace('AY-', '', $academicYear->code), $student->id);
            }

            $isCurrent = $this->boolValue($row['is_current'] ?? true);

            if ($isCurrent) {
                StudentEnrollment::query()
                    ->where('student_id', $student->id)
                    ->where('id', '<>', 0)
                    ->update(['is_current' => false]);
            }

            $enrollment = StudentEnrollment::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'academic_term_id' => $academicTerm?->id,
                    'grade_id' => $grade->id,
                    'section_id' => $section->id,
                    'enrollment_number' => $enrollmentNumber,
                    'enrollment_date' => $this->dateValue($row['enrollment_date'] ?? null),
                    'enrollment_type' => trim((string) ($row['enrollment_type'] ?? 'new')) ?: 'new',
                    'status' => trim((string) ($row['status'] ?? 'enrolled')) ?: 'enrolled',
                    'is_current' => $isCurrent,
                    'previous_school' => trim((string) ($row['previous_school'] ?? '')) ?: null,
                    'registered_by_user_id' => auth()->id(),
                    'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                ]
            );

            if ($enrollment->is_current) {
                $this->syncStudentCurrentPlacement($student, $academicYear, $grade, $section);
            }
        }
    }

    private function boolValue(mixed $value): bool
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'y', 'نعم'], true);
    }

    private function dateValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : now()->format('Y-m-d');
    }

    private function syncStudentCurrentPlacement(Student $student, AcademicYear $academicYear, Grade $grade, SchoolSection $section): void
    {
        $updates = [];

        if (Schema::hasColumn('students', 'academic_year_id')) {
            $updates['academic_year_id'] = $academicYear->id;
        }

        if (Schema::hasColumn('students', 'grade_id')) {
            $updates['grade_id'] = $grade->id;
        }

        if (Schema::hasColumn('students', 'section_id')) {
            $updates['section_id'] = $section->id;
        }

        if ($updates !== []) {
            $student->forceFill($updates)->save();
        }
    }
}
