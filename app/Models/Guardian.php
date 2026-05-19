<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    protected $fillable = [
        'guardian_number',
        'first_name',
        'father_name',
        'last_name',
        'full_name',
        'gender',
        'relation_type',
        'national_id',
        'occupation',
        'phone',
        'mobile',
        'email',
        'address',
        'workplace',
        'is_emergency_contact',
        'has_custody',
        'is_financial_responsible',
        'notes',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_emergency_contact' => 'boolean',
            'has_custody' => 'boolean',
            'is_financial_responsible' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Guardian $guardian): void {
            $guardian->full_name = $guardian->buildFullName();
            $guardian->is_active = $guardian->status === 'active';
        });
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student')
            ->withPivot([
                'relationship_type',
                'is_primary',
                'can_pick_up',
                'is_financial_responsible',
                'notes',
            ])
            ->withTimestamps();
    }

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->full_name ?: $this->buildFullName()));
    }

    public function getStudentsLabelAttribute(): string
    {
        if (! $this->relationLoaded('students')) {
            return '-';
        }

        return $this->students
            ->pluck('display_name')
            ->filter()
            ->implode('، ') ?: '-';
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
