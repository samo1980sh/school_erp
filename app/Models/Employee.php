<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'employee_number',
        'first_name',
        'father_name',
        'last_name',
        'gender',
        'birth_date',
        'national_id',
        'marital_status',
        'job_title',
        'department',
        'employment_type',
        'hire_date',
        'contract_type',
        'status',
        'email',
        'phone',
        'mobile',
        'address',
        'qualification',
        'specialization',
        'notes',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->father_name,
            $this->last_name,
        ])));
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;

        return $name !== '' ? $name : (string) $this->employee_number;
    }
}
