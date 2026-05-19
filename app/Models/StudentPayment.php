<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'student_fee_id',
        'student_id',
        'academic_year_id',
        'amount',
        'paid_on',
        'payment_method',
        'reference_number',
        'received_by_employee_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_on' => 'date',
        ];
    }

    public function studentFee(): BelongsTo
    {
        return $this->belongsTo(StudentFee::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function receivedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by_employee_id');
    }

    public function getDisplayTitleAttribute(): string
    {
        return trim(implode(' - ', array_filter([
            $this->payment_number,
            $this->student?->full_name ?? $this->student?->name,
        ])));
    }
}
