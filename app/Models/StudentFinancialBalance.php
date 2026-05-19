<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFinancialBalance extends Model
{
    protected $table = 'student_financial_balances';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'academic_year_id' => 'integer',
            'fees_count' => 'integer',
            'overdue_fees_count' => 'integer',
            'total_fees' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'total_remaining' => 'decimal:2',
            'last_payment_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getBalanceStatusAttribute(): string
    {
        if ((float) $this->total_remaining <= 0) {
            return 'paid';
        }

        if ((int) $this->overdue_fees_count > 0) {
            return 'overdue';
        }

        if ((float) $this->total_paid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }
}
