<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendance extends Model
{
    protected $fillable = [
        'academic_year_id',
        'academic_term_id',
        'grade_id',
        'section_id',
        'student_enrollment_id',
        'student_id',
        'attendance_date',
        'status',
        'arrival_time',
        'departure_time',
        'minutes_late',
        'excuse_reason',
        'notes',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'minutes_late' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StudentAttendance $attendance): void {
            if (! $attendance->student_enrollment_id) {
                return;
            }

            $enrollment = StudentEnrollment::query()->find($attendance->student_enrollment_id);

            if (! $enrollment instanceof StudentEnrollment) {
                return;
            }

            $attendance->student_id = $enrollment->student_id;
            $attendance->academic_year_id = $enrollment->academic_year_id;
            $attendance->academic_term_id = $enrollment->academic_term_id;
            $attendance->grade_id = $enrollment->grade_id;
            $attendance->section_id = $enrollment->section_id;
        });
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

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function getDisplayTitleAttribute(): string
    {
        $parts = array_filter([
            $this->student?->full_name ?? $this->student?->name,
            $this->attendance_date?->format('Y-m-d'),
        ]);

        $title = trim(implode(' - ', $parts));

        return $title !== '' ? $title : 'Attendance #' . $this->getKey();
    }
}
