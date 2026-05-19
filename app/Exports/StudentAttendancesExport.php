<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\StudentAttendance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentAttendancesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return StudentAttendance::query()
            ->with([
                'student:id,student_number,first_name,last_name',
                'studentEnrollment:id,enrollment_number',
                'academicYear:id,name',
                'academicTerm:id,name',
                'grade:id,name',
                'section:id,name',
            ])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'student_number',
            'student_name',
            'enrollment_number',
            'academic_year',
            'term',
            'grade',
            'section',
            'attendance_date',
            'status',
            'arrival_time',
            'departure_time',
            'minutes_late',
            'excuse_reason',
            'notes',
        ];
    }

    public function map($row): array
    {
        /** @var StudentAttendance $row */
        return [
            $row->student?->student_number,
            trim((string) (($row->student?->first_name ?? '') . ' ' . ($row->student?->last_name ?? ''))),
            $row->studentEnrollment?->enrollment_number,
            $row->academicYear?->name,
            $row->academicTerm?->name,
            $row->grade?->name,
            $row->section?->name,
            $row->attendance_date?->format('Y-m-d'),
            $row->status,
            $row->arrival_time,
            $row->departure_time,
            $row->minutes_late,
            $row->excuse_reason,
            $row->notes,
        ];
    }
}
