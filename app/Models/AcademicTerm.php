<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'name',
        'code',
        'starts_on',
        'ends_on',
        'status',
        'is_current',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'academic_year_id' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (AcademicTerm $academicTerm): void {
            if (! $academicTerm->is_current) {
                return;
            }

            static::withoutEvents(function () use ($academicTerm): void {
                static::query()
                    ->where('academic_year_id', $academicTerm->academic_year_id)
                    ->whereKeyNot($academicTerm->getKey())
                    ->update(['is_current' => false]);
            });
        });
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function getDisplayNameAttribute(): string
    {
        return filled($this->code)
            ? "{$this->name} ({$this->code})"
            : (string) $this->name;
    }
}
