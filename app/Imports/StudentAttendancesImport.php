<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentAttendancesImport implements ToCollection, WithHeadingRow
{
    private const STATUSES = ['present', 'absent', 'late', 'excused'];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $studentNumber = trim((string) ($row['student_number'] ?? ''));
            $enrollmentNumber = trim((string) ($row['enrollment_number'] ?? ''));
            $attendanceDate = trim((string) ($row['attendance_date'] ?? ''));
            $status = strtolower(trim((string) ($row['status'] ?? 'present')));

            if ($studentNumber === '' && $enrollmentNumber === '') {
                continue;
            }

            if ($attendanceDate === '') {
                throw ValidationException::withMessages([
                    'attendance_date' => "Row {$rowNumber}: attendance_date is required.",
                ]);
            }

            if (! in_array($status, self::STATUSES, true)) {
                throw ValidationException::withMessages([
                    'status' => "Row {$rowNumber}: invalid status. Use present, absent, late, or excused.",
                ]);
            }

            $enrollment = $this->findEnrollment($enrollmentNumber, $studentNumber, $rowNumber);
            $date = CarbonImmutable::parse($attendanceDate)->toDateString();

            StudentAttendance::query()->updateOrCreate(
                [
                    'student_id' => $enrollment->student_id,
                    'attendance_date' => $date,
                ],
                [
                    'student_enrollment_id' => $enrollment->id,
                    'academic_year_id' => $enrollment->academic_year_id,
                    'academic_term_id' => $enrollment->academic_term_id,
                    'grade_id' => $enrollment->grade_id,
                    'section_id' => $enrollment->section_id,
                    'status' => $status,
                    'arrival_time' => $this->emptyToNull($row['arrival_time'] ?? null),
                    'departure_time' => $this->emptyToNull($row['departure_time'] ?? null),
                    'minutes_late' => (int) ($row['minutes_late'] ?? 0),
                    'excuse_reason' => $this->emptyToNull($row['excuse_reason'] ?? null),
                    'notes' => $this->emptyToNull($row['notes'] ?? null),
                ]
            );
        }
    }

    private function findEnrollment(string $enrollmentNumber, string $studentNumber, int $rowNumber): StudentEnrollment
    {
        if ($enrollmentNumber !== '') {
            $enrollment = StudentEnrollment::query()
                ->where('enrollment_number', $enrollmentNumber)
                ->first();

            if ($enrollment instanceof StudentEnrollment) {
                return $enrollment;
            }
        }

        if ($studentNumber !== '') {
            $student = Student::query()
                ->where('student_number', $studentNumber)
                ->first();

            if ($student instanceof Student) {
                $enrollment = StudentEnrollment::query()
                    ->where('student_id', $student->id)
                    ->orderByDesc('id')
                    ->first();

                if ($enrollment instanceof StudentEnrollment) {
                    return $enrollment;
                }
            }
        }

        throw ValidationException::withMessages([
            'student' => "Row {$rowNumber}: matching student enrollment was not found.",
        ]);
    }

    private function emptyToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
