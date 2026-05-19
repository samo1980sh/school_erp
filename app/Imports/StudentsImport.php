<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolSection;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class StudentsImport implements SkipsEmptyRows, ToCollection, WithStartRow
{
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows): void
    {
        $errors = [];
        $imported = 0;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + $this->startRow();
            $values = $row->values();

            if ($values->filter(fn ($value): bool => trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            try {
                $studentNumber = trim((string) ($values[0] ?? ''));
                $firstName = trim((string) ($values[1] ?? ''));
                $fatherName = trim((string) ($values[2] ?? '')) ?: null;
                $motherName = trim((string) ($values[3] ?? '')) ?: null;
                $lastName = trim((string) ($values[4] ?? '')) ?: null;
                $gender = $this->normalizeGender((string) ($values[5] ?? ''));
                $birthDate = $this->normalizeDate($values[6] ?? null);
                $placeOfBirth = trim((string) ($values[7] ?? '')) ?: null;
                $nationalId = trim((string) ($values[8] ?? '')) ?: null;
                $enrollmentDate = $this->normalizeDate($values[9] ?? null);
                $academicYear = $this->findAcademicYear((string) ($values[10] ?? ''));
                $grade = $this->findGrade((string) ($values[11] ?? ''));
                $section = $this->findSection((string) ($values[12] ?? ''), $grade?->id, $academicYear?->id);
                $status = $this->normalizeStatus((string) ($values[19] ?? 'active'));

                if ($studentNumber === '') {
                    throw new \RuntimeException('الرقم المدرسي مطلوب.');
                }

                if ($firstName === '') {
                    throw new \RuntimeException('الاسم الأول مطلوب.');
                }

                if (! $academicYear) {
                    throw new \RuntimeException('السنة الدراسية غير موجودة أو غير مطابقة.');
                }

                if (! $grade) {
                    throw new \RuntimeException('الصف الدراسي غير موجود أو غير مطابق.');
                }

                Student::query()->updateOrCreate(
                    ['student_number' => $studentNumber],
                    [
                        'first_name' => $firstName,
                        'father_name' => $fatherName,
                        'mother_name' => $motherName,
                        'last_name' => $lastName,
                        'gender' => $gender,
                        'birth_date' => $birthDate,
                        'place_of_birth' => $placeOfBirth,
                        'national_id' => $nationalId,
                        'enrollment_date' => $enrollmentDate,
                        'current_academic_year_id' => $academicYear->id,
                        'current_grade_id' => $grade->id,
                        'current_section_id' => $section?->id,
                        'phone' => trim((string) ($values[13] ?? '')) ?: null,
                        'email' => trim((string) ($values[14] ?? '')) ?: null,
                        'address' => trim((string) ($values[15] ?? '')) ?: null,
                        'blood_type' => trim((string) ($values[16] ?? '')) ?: null,
                        'medical_notes' => trim((string) ($values[17] ?? '')) ?: null,
                        'notes' => trim((string) ($values[18] ?? '')) ?: null,
                        'status' => $status,
                        'is_active' => $status === 'active',
                    ]
                );

                $imported++;
            } catch (Throwable $exception) {
                $errors[] = 'Row ' . $rowNumber . ': ' . $exception->getMessage();
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => implode(PHP_EOL, array_slice($errors, 0, 20)),
            ]);
        }

        if ($imported === 0) {
            throw ValidationException::withMessages([
                'file' => 'لم يتم استيراد أي طالب. تأكد من تعبئة الملف ابتداءً من الصف الثاني.',
            ]);
        }
    }

    private function normalizeGender(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return in_array($value, ['female', 'f', 'أنثى', 'انثى', 'بنت'], true) ? 'female' : 'male';
    }

    private function normalizeStatus(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'نشط', 'active' => 'active',
            'منقول', 'transferred' => 'transferred',
            'منسحب', 'withdrawn' => 'withdrawn',
            'متخرج', 'graduated' => 'graduated',
            'موقوف', 'suspended' => 'suspended',
            default => 'active',
        };
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return date('Y-m-d', strtotime((string) $value)) ?: null;
    }

    private function findAcademicYear(string $value): ?AcademicYear
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return AcademicYear::query()
            ->where('name', $value)
            ->orWhere('code', $value)
            ->first();
    }

    private function findGrade(string $value): ?Grade
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return Grade::query()
            ->where('name', $value)
            ->orWhere('code', $value)
            ->first();
    }

    private function findSection(string $value, ?int $gradeId, ?int $academicYearId): ?SchoolSection
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return SchoolSection::query()
            ->when($gradeId, fn ($query) => $query->where('grade_id', $gradeId))
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->where(function ($query) use ($value): void {
                $query->where('name', $value)->orWhere('code', $value);
            })
            ->first();
    }
}
