<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'academic_term_id',
        'grade_id',
        'section_id',
        'enrollment_number',
        'enrollment_date',
        'enrollment_type',
        'status',
        'is_current',
        'previous_school',
        'registered_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SchoolSection::class, 'section_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function getDisplayTitleAttribute(): string
    {
        $studentName = self::studentDisplayName($this->student);

        $title = trim(implode(' - ', array_filter([
            $studentName,
            $this->academicYear?->name,
            $this->grade?->name,
            $this->section?->name,
        ])));

        return $title !== ''
            ? $title
            : 'Student enrollment #' . $this->getKey();
    }

    public static function studentDisplayName(?Student $student): string
    {
        if (! $student instanceof Student) {
            return '';
        }

        $fullName = trim((string) ($student->full_name ?? ''));

        if ($fullName !== '') {
            return $fullName;
        }

        $name = trim((string) ($student->name ?? ''));

        if ($name !== '') {
            return $name;
        }

        $parts = [
            $student->first_name ?? null,
            $student->father_name ?? null,
            $student->last_name ?? null,
        ];

        $name = trim(implode(' ', array_filter(array_map(
            fn (mixed $part): string => trim((string) $part),
            $parts
        ))));

        return $name !== '' ? $name : (string) ($student->student_number ?? '');
    }
}
