<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'educational_stage_id',
        'sort_order',
        'name',
        'code',
        'grade_number',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'educational_stage_id' => 'integer',
            'sort_order' => 'integer',
            'grade_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function educationalStage(): BelongsTo
    {
        return $this->belongsTo(EducationalStage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
