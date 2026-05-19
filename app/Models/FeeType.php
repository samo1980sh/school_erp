<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeType extends Model
{
    protected $fillable = [
        'academic_year_id',
        'grade_id',
        'sort_order',
        'code',
        'name',
        'amount',
        'due_on',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'amount' => 'decimal:2',
            'due_on' => 'date',
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

    public function studentFees(): HasMany
    {
        return $this->hasMany(StudentFee::class);
    }

    public function getDisplayTitleAttribute(): string
    {
        return trim(sprintf('%s - %s', $this->name, $this->code));
    }
}
