<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\GradeSubject;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExamsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'academic_year_code',
            'academic_term_code',
            'grade_name',
            'subject_code',
            'code',
            'name',
            'exam_type',
            'exam_date',
            'max_mark',
            'passing_mark',
            'weight_percent',
            'status',
            'sort_order',
            'notes',
        ];
    }

    public function array(): array
    {
        $year = AcademicYear::query()
            ->with(['terms' => fn ($query) => $query->orderBy('sort_order')])
            ->where('is_current', true)
            ->first()
            ?? AcademicYear::query()
                ->with(['terms' => fn ($query) => $query->orderBy('sort_order')])
                ->orderByDesc('starts_on')
                ->first();

        $term = $year?->terms?->first();

        $plans = GradeSubject::query()
            ->with(['grade:id,name', 'subject:id,name,code'])
            ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
            ->where('status', 'active')
            ->orderBy('grade_id')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        if (! $year || ! $term || $plans->isEmpty()) {
            return [
                [
                    'AY-2025-2026',
                    'AY-2025-2026-T1',
                    'الصف الأول',
                    'ARABIC',
                    'EX-2025-2026-G1-ARABIC-MONTHLY',
                    'اختبار شهري - اللغة العربية',
                    'monthly',
                    '2025-10-15',
                    100,
                    50,
                    30,
                    'published',
                    10,
                    'Optional note',
                ],
            ];
        }

        return $plans
            ->values()
            ->map(fn (GradeSubject $plan, int $index): array => [
                $year->code ?? $year->name,
                $term->code ?? $term->name,
                $plan->grade?->name,
                $plan->subject?->code,
                sprintf('EX-%s-G%s-%s-MONTHLY', $year->name, $plan->grade_id, $plan->subject?->code ?? 'SUBJECT'),
                'اختبار شهري - ' . ($plan->subject?->name ?? 'المادة'),
                'monthly',
                Carbon::parse($term->starts_on ?? now())->addWeeks(4 + $index)->format('Y-m-d'),
                100,
                50,
                30,
                'published',
                ($index + 1) * 10,
                '',
            ])
            ->all();
    }
}
