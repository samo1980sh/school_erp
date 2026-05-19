<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Throwable;

class GuardiansImport implements ToCollection, WithStartRow
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
                $guardianNumber = trim((string) ($values[0] ?? ''));
                $firstName = trim((string) ($values[1] ?? ''));
                $fatherName = trim((string) ($values[2] ?? '')) ?: null;
                $lastName = trim((string) ($values[3] ?? '')) ?: null;
                $gender = $this->normalizeGender((string) ($values[4] ?? ''));
                $relationType = $this->normalizeRelation((string) ($values[5] ?? 'guardian'));
                $nationalId = trim((string) ($values[6] ?? '')) ?: null;
                $studentNumbers = $this->studentNumbers((string) ($values[16] ?? ''));
                $status = $this->normalizeStatus((string) ($values[18] ?? 'active'));

                if ($guardianNumber === '') {
                    throw new \RuntimeException('رقم ولي الأمر مطلوب.');
                }

                if ($firstName === '') {
                    throw new \RuntimeException('الاسم الأول مطلوب.');
                }

                $guardian = Guardian::query()->updateOrCreate(
                    ['guardian_number' => $guardianNumber],
                    [
                        'first_name' => $firstName,
                        'father_name' => $fatherName,
                        'last_name' => $lastName,
                        'gender' => $gender,
                        'relation_type' => $relationType,
                        'national_id' => $nationalId,
                        'occupation' => trim((string) ($values[7] ?? '')) ?: null,
                        'mobile' => trim((string) ($values[8] ?? '')) ?: null,
                        'phone' => trim((string) ($values[9] ?? '')) ?: null,
                        'email' => trim((string) ($values[10] ?? '')) ?: null,
                        'address' => trim((string) ($values[11] ?? '')) ?: null,
                        'workplace' => trim((string) ($values[12] ?? '')) ?: null,
                        'is_emergency_contact' => $this->normalizeBoolean($values[13] ?? true),
                        'has_custody' => $this->normalizeBoolean($values[14] ?? true),
                        'is_financial_responsible' => $this->normalizeBoolean($values[15] ?? true),
                        'notes' => trim((string) ($values[17] ?? '')) ?: null,
                        'status' => $status,
                        'is_active' => $status === 'active',
                    ]
                );

                if ($studentNumbers !== []) {
                    $students = Student::query()
                        ->whereIn('student_number', $studentNumbers)
                        ->get(['id', 'student_number']);

                    $missing = array_values(array_diff($studentNumbers, $students->pluck('student_number')->all()));

                    if ($missing !== []) {
                        throw new \RuntimeException('أرقام طلاب غير موجودة: ' . implode(', ', $missing));
                    }

                    $syncData = [];
                    foreach ($students as $student) {
                        $syncData[$student->id] = [
                            'relationship_type' => $relationType,
                            'is_primary' => $this->normalizeBoolean($values[14] ?? true),
                            'can_pick_up' => $this->normalizeBoolean($values[13] ?? true),
                            'is_financial_responsible' => $this->normalizeBoolean($values[15] ?? true),
                        ];
                    }

                    $guardian->students()->syncWithoutDetaching($syncData);
                }

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
                'file' => 'لم يتم استيراد أي ولي أمر. تأكد من تعبئة الملف ابتداءً من الصف الثاني.',
            ]);
        }
    }

    private function studentNumbers(string $value): array
    {
        return collect(preg_split('/[,،;\n]+/', $value) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeGender(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return in_array($value, ['female', 'f', 'أنثى', 'انثى', 'أم', 'mother'], true) ? 'female' : 'male';
    }

    private function normalizeRelation(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'الأب', 'اب', 'father', 'dad' => 'father',
            'الأم', 'ام', 'mother', 'mom' => 'mother',
            'الجد', 'grandfather' => 'grandfather',
            'الجدة', 'grandmother' => 'grandmother',
            'العم', 'الخال', 'uncle' => 'uncle',
            'العمة', 'الخالة', 'aunt' => 'aunt',
            default => 'guardian',
        };
    }

    private function normalizeStatus(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'نشط', 'active' => 'active',
            'غير نشط', 'inactive' => 'inactive',
            'محظور', 'blocked' => 'blocked',
            'متوفى', 'deceased' => 'deceased',
            default => 'active',
        };
    }

    private function normalizeBoolean(mixed $value): bool
    {
        $value = mb_strtolower(trim((string) $value));

        if ($value === '') {
            return false;
        }

        return in_array($value, ['1', 'true', 'yes', 'y', 'نعم', 'صح'], true);
    }
}
