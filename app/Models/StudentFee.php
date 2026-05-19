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

    protected static function booted(): void
    {
        static::saving(function (self $fee): void {
            $amount = max((float) ($fee->amount ?? 0), 0);
            $discount = max((float) ($fee->discount_amount ?? 0), 0);
            $paid = max((float) ($fee->paid_amount ?? 0), 0);

            $netAmount = max($amount - $discount, 0);

            $fee->discount_amount = min($discount, $amount);
            $fee->paid_amount = min($paid, $netAmount);
            $fee->balance_amount = max($netAmount - (float) $fee->paid_amount, 0);

            if ($fee->status !== 'cancelled') {
                $fee->status = match (true) {
                    $netAmount <= 0 => 'paid',
                    (float) $fee->paid_amount <= 0 => 'unpaid',
                    (float) $fee->balance_amount <= 0 => 'paid',
                    default => 'partial',
                };
            }
        });
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

    public function netAmount(): float
    {
        return max((float) $this->amount - (float) $this->discount_amount, 0);
    }

    public function remainingAmountForPayment(?int $excludingPaymentId = null): float
    {
        $paidQuery = $this->payments();

        if ($excludingPaymentId !== null) {
            $paidQuery->whereKeyNot($excludingPaymentId);
        }

        $paid = (float) $paidQuery->sum('amount');

        return max($this->netAmount() - $paid, 0);
    }

    public function syncPaymentTotals(): void
    {
        $netAmount = $this->netAmount();
        $paid = min((float) $this->payments()->sum('amount'), $netAmount);
        $balance = max($netAmount - $paid, 0);

        $status = $this->status === 'cancelled'
            ? 'cancelled'
            : match (true) {
                $netAmount <= 0 => 'paid',
                $paid <= 0 => 'unpaid',
                $balance <= 0 => 'paid',
                default => 'partial',
            };

        $this->forceFill([
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'status' => $status,
        ])->saveQuietly();
    }
}
