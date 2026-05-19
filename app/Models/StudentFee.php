<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFee extends Model
{
    protected $fillable = [
        'fee_number',
        'fee_type_id',
        'student_id',
        'student_enrollment_id',
        'academic_year_id',
        'grade_id',
        'section_id',
        'amount',
        'discount_amount',
        'paid_amount',
        'balance_amount',
        'due_on',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'due_on' => 'date',
        ];
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SchoolSection::class, 'section_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class);
    }

    public function getDisplayTitleAttribute(): string
    {
        return trim(implode(' - ', array_filter([
            $this->fee_number,
            $this->student?->full_name ?? $this->student?->name,
            $this->feeType?->name,
        ])));
    }
}
