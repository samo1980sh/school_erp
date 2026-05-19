<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSubject extends Model
{
    protected $fillable = [
        'academic_year_id',
        'grade_id',
        'subject_id',
        'sort_order',
        'weekly_periods',
        'coefficient',
        'is_core',
        'is_exam_subject',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'weekly_periods' => 'integer',
            'coefficient' => 'decimal:2',
            'is_core' => 'boolean',
            'is_exam_subject' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function getDisplayTitleAttribute(): string
    {
        $title = trim(implode(' - ', array_filter([
            $this->subject?->name,
            $this->grade?->name,
            $this->academicYear?->name,
        ])));

        return $title !== ''
            ? $title
            : 'Grade subject plan #' . $this->getKey();
    }
}