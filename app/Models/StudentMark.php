<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMark extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'student_enrollment_id',
        'academic_year_id',
        'academic_term_id',
        'grade_id',
        'section_id',
        'subject_id',
        'mark',
        'max_mark',
        'status',
        'notes',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'mark' => 'decimal:2',
            'max_mark' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function getDisplayTitleAttribute(): string
    {
        $title = trim(implode(' - ', array_filter([
            $this->student?->full_name ?? $this->student?->name,
            $this->exam?->name,
            $this->subject?->name,
        ])));

        return $title !== '' ? $title : 'Student mark #' . $this->getKey();
    }
}
