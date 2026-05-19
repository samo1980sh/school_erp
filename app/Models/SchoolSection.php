<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSection extends Model
{
    protected $table = 'sections';

    protected $fillable = [
        'academic_year_id',
        'grade_id',
        'classroom_id',
        'name',
        'code',
        'capacity',
        'gender_policy',
        'status',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'academic_year_id' => 'integer',
            'grade_id' => 'integer',
            'classroom_id' => 'integer',
            'capacity' => 'integer',
            'sort_order' => 'integer',
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

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getDisplayNameAttribute(): string
    {
        $gradeName = $this->grade?->name;
        $yearName = $this->academicYear?->name;

        return trim(implode(' - ', array_filter([
            $yearName,
            $gradeName,
            $this->name,
        ])));
    }
}
