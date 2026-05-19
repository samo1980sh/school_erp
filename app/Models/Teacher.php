<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'teacher_number',
        'full_name',
        'gender',
        'national_id',
        'birth_date',
        'email',
        'phone',
        'mobile',
        'address',
        'qualification',
        'specialization',
        'job_title',
        'employment_type',
        'hire_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim((string) $this->full_name) !== ''
            ? (string) $this->full_name
            : 'Teacher #' . $this->getKey();
    }
}
