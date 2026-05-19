<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'student_number',
        'first_name',
        'father_name',
        'mother_name',
        'last_name',
        'full_name',
        'gender',
        'birth_date',
        'place_of_birth',
        'national_id',
        'enrollment_date',
        'current_academic_year_id',
        'current_grade_id',
        'current_section_id',
        'phone',
        'email',
        'address',
        'blood_type',
        'medical_notes',
        'notes',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'enrollment_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Student $student): void {
            $student->full_name = $student->buildFullName();
        });
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'current_academic_year_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'current_grade_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SchoolSection::class, 'current_section_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->full_name ?: $this->buildFullName()));
    }

    public function getPlacementLabelAttribute(): string
    {
        return trim(implode(' - ', array_filter([
            $this->academicYear?->name,
            $this->grade?->name,
            $this->section?->name,
        ]))) ?: '-';
    }

    private function buildFullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->father_name,
            $this->last_name,
        ])));
    }
}
