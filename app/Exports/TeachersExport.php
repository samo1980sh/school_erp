<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Teacher;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TeachersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return Teacher::query()
            ->orderBy('full_name')
            ->orderBy('teacher_number')
            ->get();
    }

    public function headings(): array
    {
        return [
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
    }

    public function map($teacher): array
    {
        return [
            $teacher->teacher_number,
            $teacher->full_name,
            $teacher->gender,
            $teacher->national_id,
            $teacher->birth_date?->format('Y-m-d'),
            $teacher->email,
            $teacher->phone,
            $teacher->mobile,
            $teacher->address,
            $teacher->qualification,
            $teacher->specialization,
            $teacher->job_title,
            $teacher->employment_type,
            $teacher->hire_date?->format('Y-m-d'),
            $teacher->status,
            $teacher->notes,
        ];
    }
}
