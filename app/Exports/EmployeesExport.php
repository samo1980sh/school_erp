<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Employee::query()
            ->orderBy('sort_order')
            ->orderBy('employee_number')
            ->get();
    }

    public function headings(): array
    {
        return EmployeesTemplateExport::templateHeadings();
    }

    public function map($employee): array
    {
        return [
            $employee->employee_number,
            $employee->first_name,
            $employee->father_name,
            $employee->last_name,
            $employee->gender,
            optional($employee->birth_date)->format('Y-m-d'),
            $employee->national_id,
            $employee->marital_status,
            $employee->job_title,
            $employee->department,
            $employee->employment_type,
            optional($employee->hire_date)->format('Y-m-d'),
            $employee->contract_type,
            $employee->status,
            $employee->email,
            $employee->phone,
            $employee->mobile,
            $employee->address,
            $employee->qualification,
            $employee->specialization,
            $employee->is_active ? 1 : 0,
            $employee->notes,
        ];
    }
}
