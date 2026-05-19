<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
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
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (AcademicYear $academicYear): void {
            if (! $academicYear->is_current) {
                return;
            }

            static::withoutEvents(function () use ($academicYear): void {
                static::query()
                    ->whereKeyNot($academicYear->getKey())
                    ->update(['is_current' => false]);
            });
        });
    }

    public function terms(): HasMany
    {
        return $this->hasMany(AcademicTerm::class);
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
