<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TeachersImport implements ToCollection
{
    public function collection(Collection $rows): void
    {
        $header = null;

        foreach ($rows as $index => $row) {
            $values = $row instanceof Collection ? $row->values()->all() : (array) $row;

            if ($index === 0) {
                $header = array_map(fn ($value): string => trim((string) $value), $values);
                continue;
            }

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $data = $this->mapRow($header ?? [], $values);

            $teacherNumber = trim((string) ($data['teacher_number'] ?? ''));
            $fullName = trim((string) ($data['full_name'] ?? ''));

            if ($teacherNumber === '' || $fullName === '') {
                continue;
            }

            Teacher::query()->updateOrCreate(
                ['teacher_number' => $teacherNumber],
                [
                    'full_name' => $fullName,
                    'gender' => $this->normalizeOption($data['gender'] ?? 'male', ['male', 'female'], 'male'),
                    'national_id' => $this->emptyToNull($data['national_id'] ?? null),
                    'birth_date' => $this->dateValue($data['birth_date'] ?? null),
                    'email' => $this->emptyToNull($data['email'] ?? null),
                    'phone' => $this->emptyToNull($data['phone'] ?? null),
                    'mobile' => $this->emptyToNull($data['mobile'] ?? null),
                    'address' => $this->emptyToNull($data['address'] ?? null),
                    'qualification' => $this->emptyToNull($data['qualification'] ?? null),
                    'specialization' => $this->emptyToNull($data['specialization'] ?? null),
                    'job_title' => $this->emptyToNull($data['job_title'] ?? null),
                    'employment_type' => $this->normalizeOption($data['employment_type'] ?? 'full_time', ['full_time', 'part_time', 'visiting'], 'full_time'),
                    'hire_date' => $this->dateValue($data['hire_date'] ?? null),
                    'status' => $this->normalizeOption($data['status'] ?? 'active', ['active', 'on_leave', 'inactive', 'archived'], 'active'),
                    'notes' => $this->emptyToNull($data['notes'] ?? null),
                ]
            );
        }
    }

    private function mapRow(array $header, array $values): array
    {
        if ($header !== [] && in_array('teacher_number', $header, true)) {
            $mapped = [];

            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }

                $mapped[$key] = $values[$index] ?? null;
            }

            return $mapped;
        }

        $keys = [
            'teacher_number',
            'full_name',
            'gender',
            'national_id',
            'birth_date',
            'email',
            'phone',
            'mobile',
            'address',
            'qualification',
            'specialization',
            'job_title',
            'employment_type',
            'hire_date',
            'status',
            'notes',
        ];

        return array_combine($keys, array_pad($values, count($keys), null)) ?: [];
    }

    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function emptyToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeOption(mixed $value, array $allowed, string $default): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
