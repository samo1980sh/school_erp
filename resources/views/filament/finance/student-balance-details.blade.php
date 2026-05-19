@php
$isEnglish = app()->getLocale() === 'en';

$label = fn (string $ar, string $en): string => $isEnglish ? $en : $ar;

$money = function ($value): string {
return '<span class="finance-ltr">' . e(number_format((float) $value, 0)) . ' SYP</span>';
};

$ltr = function ($value): string {
$value = trim((string) $value);

if ($value === '') {
return '—';
}

return '<span class="finance-ltr">' . e($value) . '</span>';
};

$statusLabel = function (?string $status) use ($label): string {
return match ((string) $status) {
'paid' => $label('مدفوع', 'Paid'),
'partial' => $label('مدفوع جزئيًا', 'Partially paid'),
'cancelled' => $label('ملغى', 'Cancelled'),
'unpaid' => $label('غير مدفوع', 'Unpaid'),
default => (string) $status,
};
};

$statusClass = function (?string $status): string {
return match ((string) $status) {
'paid' => 'finance-badge finance-badge-success',
'partial' => 'finance-badge finance-badge-warning',
'cancelled' => 'finance-badge finance-badge-gray',
'unpaid' => 'finance-badge finance-badge-danger',
default => 'finance-badge finance-badge-gray',
};
};

$methodLabel = function (?string $method) use ($label): string {
return match ((string) $method) {
'cash' => $label('نقدًا', 'Cash'),
'bank_transfer' => $label('تحويل بنكي', 'Bank transfer'),
'card' => $label('بطاقة', 'Card'),
default => (string) $method,
};
};
@endphp

<style>
    .finance-details-wrap {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        width: 100%;
    }

    .finance-summary-card,
    .finance-section {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
        overflow: hidden;
    }

    .finance-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        padding: 18px;
    }

    .finance-summary-item {
        border: 1px solid #eef2f7;
        border-radius: 14px;
        padding: 14px;
        background: #f9fafb;
    }

    .finance-summary-label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .finance-summary-value {
        font-size: 15px;
        font-weight: 700;
        color: #111827;
        line-height: 1.7;
    }

    .finance-summary-sub {
        font-size: 13px;
        color: #6b7280;
        margin-top: 2px;
    }

    .finance-section-header {
        padding: 16px 18px;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .finance-section-title {
        font-size: 16px;
        font-weight: 800;
        color: #111827;
        margin: 0;
    }

    .finance-section-desc {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
        line-height: 1.8;
    }

    .finance-table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .finance-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 980px;
    }

    .finance-table th {
        background: #ffffff;
        color: #374151;
        font-weight: 800;
        text-align: start;
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .finance-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #111827;
        vertical-align: top;
        line-height: 1.7;
    }

    .finance-table tr:last-child td {
        border-bottom: 0;
    }

    .finance-table tr:hover td {
        background: #f9fafb;
    }

    .finance-ltr {
        direction: ltr;
        unicode-bidi: plaintext;
        display: inline-block;
        text-align: left;
        white-space: nowrap;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    }

    .finance-money-success {
        color: #047857;
        font-weight: 700;
    }

    .finance-money-danger {
        color: #dc2626;
        font-weight: 700;
    }

    .finance-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 3px 9px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .finance-badge-success {
        color: #047857;
        background: #d1fae5;
        border: 1px solid #a7f3d0;
    }

    .finance-badge-warning {
        color: #92400e;
        background: #fef3c7;
        border: 1px solid #fde68a;
    }

    .finance-badge-danger {
        color: #b91c1c;
        background: #fee2e2;
        border: 1px solid #fecaca;
    }

    .finance-badge-gray {
        color: #374151;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
    }

    .finance-empty {
        padding: 28px;
        text-align: center;
        color: #6b7280;
        font-size: 14px;
    }

    @media (max-width: 1024px) {
        .finance-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .finance-summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="finance-details-wrap">
    <div class="finance-summary-card">
        <div class="finance-summary-grid">
            <div class="finance-summary-item">
                <div class="finance-summary-label">{{ $label('الطالب', 'Student') }}</div>
                <div class="finance-summary-value">{{ $record->student_name }}</div>
                <div class="finance-summary-sub">{!! $ltr($record->student_number) !!}</div>
            </div>

            <div class="finance-summary-item">
                <div class="finance-summary-label">{{ $label('السنة الدراسية', 'Academic year') }}</div>
                <div class="finance-summary-value">{{ $record->academic_year_name }}</div>
            </div>

            <div class="finance-summary-item">
                <div class="finance-summary-label">{{ $label('إجمالي الرسوم', 'Total fees') }}</div>
                <div class="finance-summary-value">{!! $money($record->total_fees) !!}</div>
            </div>

            <div class="finance-summary-item">
                <div class="finance-summary-label">{{ $label('المتبقي', 'Remaining') }}</div>
                <div class="finance-summary-value finance-money-danger">{!! $money($record->total_remaining) !!}</div>
            </div>
        </div>
    </div>

    <section class="finance-section">
        <div class="finance-section-header">
            <h3 class="finance-section-title">{{ $label('تفاصيل الرسوم', 'Fee details') }}</h3>
            <div class="finance-section-desc">
                {{ $label('يعرض هذا الجدول كل المطالبات المالية المفروضة على الطالب في السنة الدراسية المحددة.', 'This table shows every financial charge assigned to the student in the selected academic year.') }}
            </div>
        </div>

        <div class="finance-table-scroll">
            <table class="finance-table">
                <thead>
                    <tr>
                        <th>{{ $label('رقم الرسم', 'Fee no.') }}</th>
                        <th>{{ $label('نوع الرسم', 'Fee type') }}</th>
                        <th>{{ $label('المبلغ', 'Amount') }}</th>
                        <th>{{ $label('المدفوع', 'Paid') }}</th>
                        <th>{{ $label('المتبقي', 'Remaining') }}</th>
                        <th>{{ $label('الاستحقاق', 'Due date') }}</th>
                        <th>{{ $label('الحالة', 'Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fees as $fee)
                    <tr>
                        <td>{!! $ltr($fee->fee_number) !!}</td>
                        <td>
                            <strong>{{ $fee->feeType?->name ?? '—' }}</strong>
                        </td>
                        <td>{!! $money($fee->amount) !!}</td>
                        <td class="finance-money-success">{!! $money($fee->paid_amount) !!}</td>
                        <td class="finance-money-danger">{!! $money($fee->balance_amount) !!}</td>
                        <td>{!! $ltr($fee->due_on?->format('Y-m-d') ?? '—') !!}</td>
                        <td>
                            <span class="{{ $statusClass($fee->status) }}">
                                {{ $statusLabel($fee->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="finance-empty">
                                {{ $label('لا توجد رسوم لهذا الطالب.', 'No fees found for this student.') }}
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="finance-section">
        <div class="finance-section-header">
            <h3 class="finance-section-title">{{ $label('إيصالات الدفع', 'Payment receipts') }}</h3>
            <div class="finance-section-desc">
                {{ $label('يعرض هذا الجدول الدفعات الفعلية والإيصالات المرتبطة برسوم الطالب.', 'This table shows actual payments and receipts linked to the student fees.') }}
            </div>
        </div>

        <div class="finance-table-scroll">
            <table class="finance-table">
                <thead>
                    <tr>
                        <th>{{ $label('رقم الإيصال', 'Receipt no.') }}</th>
                        <th>{{ $label('رقم الرسم', 'Fee no.') }}</th>
                        <th>{{ $label('نوع الرسم', 'Fee type') }}</th>
                        <th>{{ $label('المبلغ', 'Amount') }}</th>
                        <th>{{ $label('طريقة الدفع', 'Method') }}</th>
                        <th>{{ $label('تاريخ الدفع', 'Paid on') }}</th>
                        <th>{{ $label('ملاحظات', 'Notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                    <tr>
                        <td>{!! $ltr($payment->payment_number) !!}</td>
                        <td>{!! $ltr($payment->studentFee?->fee_number) !!}</td>
                        <td>
                            <strong>{{ $payment->studentFee?->feeType?->name ?? '—' }}</strong>
                        </td>
                        <td class="finance-money-success">{!! $money($payment->amount) !!}</td>
                        <td>{{ $methodLabel($payment->payment_method) }}</td>
                        <td>{!! $ltr($payment->paid_on?->format('Y-m-d') ?? '—') !!}</td>
                        <td>{{ $payment->notes ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="finance-empty">
                                {{ $label('لا توجد إيصالات دفع لهذا الطالب.', 'No payment receipts found for this student.') }}
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>