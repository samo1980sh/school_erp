<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $data = [
                'employee_number' => $this->stringValue($row, 'employee_number'),
                'first_name' => $this->stringValue($row, 'first_name'),
                'father_name' => $this->nullableString($row, 'father_name'),
                'last_name' => $this->nullableString($row, 'last_name'),
                'gender' => $this->stringValue($row, 'gender', 'male'),
                'birth_date' => $this->nullableString($row, 'birth_date'),
                'national_id' => $this->nullableString($row, 'national_id'),
                'marital_status' => $this->nullableString($row, 'marital_status'),
                'job_title' => $this->stringValue($row, 'job_title'),
                'department' => $this->nullableString($row, 'department'),
                'employment_type' => $this->stringValue($row, 'employment_type', 'administrative'),
                'hire_date' => $this->nullableString($row, 'hire_date'),
                'contract_type' => $this->nullableString($row, 'contract_type'),
                'status' => $this->stringValue($row, 'status', 'active'),
                'email' => $this->nullableString($row, 'email'),
                'phone' => $this->nullableString($row, 'phone'),
                'mobile' => $this->nullableString($row, 'mobile'),
                'address' => $this->nullableString($row, 'address'),
                'qualification' => $this->nullableString($row, 'qualification'),
                'specialization' => $this->nullableString($row, 'specialization'),
                'is_active' => $this->boolValue($row, 'is_active', true),
                'notes' => $this->nullableString($row, 'notes'),
            ];

            Validator::make($data, [
                'employee_number' => ['required', 'string', 'max:255'],
                'first_name' => ['required', 'string', 'max:255'],
                'job_title' => ['required', 'string', 'max:255'],
                'gender' => ['required', Rule::in(['male', 'female'])],
                'status' => ['required', Rule::in(['active', 'inactive', 'on_leave', 'ended'])],
                'email' => ['nullable', 'email', 'max:255'],
                'national_id' => ['nullable', 'string', 'max:255'],
            ], [], [
                'employee_number' => 'employee_number row ' . ($index + 2),
            ])->validate();

            Employee::query()->updateOrCreate(
                ['employee_number' => $data['employee_number']],
                $data + [
                    'sort_order' => ((int) Employee::query()->max('sort_order')) + 10,
                ]
            );
        }
    }

    private function stringValue($row, string $key, ?string $default = null): string
    {
        $value = trim((string) ($row[$key] ?? $default ?? ''));

        return $value;
    }

    private function nullableString($row, string $key): ?string
    {
        $value = trim((string) ($row[$key] ?? ''));

        return $value !== '' ? $value : null;
    }

    private function boolValue($row, string $key, bool $default): bool
    {
        $value = $row[$key] ?? $default;

        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'active', 'نعم', 'مفعل'], true);
    }
}
