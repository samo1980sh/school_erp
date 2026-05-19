<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        static::saving(function (self $payment): void {
            $amount = (float) ($payment->amount ?? 0);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => app()->getLocale() === 'en'
                        ? 'Payment amount must be greater than zero.'
                        : 'يجب أن يكون مبلغ الدفعة أكبر من الصفر.',
                ]);
            }

            $fee = StudentFee::query()->find($payment->student_fee_id);

            if (! $fee instanceof StudentFee) {
                throw ValidationException::withMessages([
                    'student_fee_id' => app()->getLocale() === 'en'
                        ? 'The linked student fee is invalid.'
                        : 'الرسم المرتبط غير صحيح.',
                ]);
            }

            if ($fee->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'student_fee_id' => app()->getLocale() === 'en'
                        ? 'Cannot record a payment for a cancelled fee.'
                        : 'لا يمكن تسجيل دفعة على رسم ملغى.',
                ]);
            }

            $remaining = $fee->remainingAmountForPayment($payment->exists ? (int) $payment->getKey() : null);

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => app()->getLocale() === 'en'
                        ? 'Payment amount cannot be greater than the remaining fee balance.'
                        : 'لا يمكن أن يكون مبلغ الدفعة أكبر من المتبقي على الرسم.',
                ]);
            }

            $payment->student_id = $fee->student_id;
            $payment->academic_year_id = $fee->academic_year_id;
        });

        static::saved(function (self $payment): void {
            $currentFeeId = (int) $payment->student_fee_id;
            $originalFeeId = (int) $payment->getOriginal('student_fee_id');

            self::syncFee($currentFeeId);

            if ($originalFeeId > 0 && $originalFeeId !== $currentFeeId) {
                self::syncFee($originalFeeId);
            }
        });

        static::deleted(function (self $payment): void {
            self::syncFee((int) $payment->student_fee_id);
        });
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

    private static function syncFee(int $feeId): void
    {
        if ($feeId <= 0) {
            return;
        }

        $fee = StudentFee::query()->find($feeId);

        if ($fee instanceof StudentFee) {
            $fee->syncPaymentTotals();
        }
    }
}
