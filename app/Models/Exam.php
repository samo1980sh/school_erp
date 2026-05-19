<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'academic_year_id',
        'academic_term_id',
        'grade_id',
        'subject_id',
        'name',
        'code',
        'exam_type',
        'exam_date',
        'max_mark',
        'passing_mark',
        'weight_percent',
        'status',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'max_mark' => 'decimal:2',
            'passing_mark' => 'decimal:2',
            'weight_percent' => 'decimal:2',
            'sort_order' => 'integer',
        ];
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(StudentMark::class);
    }

    public function getDisplayTitleAttribute(): string
    {
        $title = trim(implode(' - ', array_filter([
            $this->name,
            $this->subject?->name,
            $this->grade?->name,
            $this->academicTerm?->name,
        ])));

        return $title !== '' ? $title : 'Exam #' . $this->getKey();
    }
}
