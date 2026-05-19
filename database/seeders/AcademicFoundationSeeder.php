<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AcademicFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $today = CarbonImmutable::today();
        $currentAcademicStartYear = $today->month >= 9 ? $today->year : $today->year - 1;

        for ($startYear = 2020; $startYear <= 2036; $startYear++) {
            $endYear = $startYear + 1;
            $yearCode = sprintf('AY-%d-%d', $startYear, $endYear);
            $yearStart = CarbonImmutable::create($startYear, 9, 1);
            $yearEnd = CarbonImmutable::create($endYear, 8, 31);

            $academicYear = AcademicYear::query()->updateOrCreate(
                ['code' => $yearCode],
                [
                    'sort_order' => (($startYear - 2020) + 1) * 10,
                    'name' => sprintf('%d-%d', $startYear, $endYear),
                    'starts_on' => $yearStart->toDateString(),
                    'ends_on' => $yearEnd->toDateString(),
                    'status' => $this->statusForRange($today, $yearStart, $yearEnd),
                    'is_current' => $startYear === $currentAcademicStartYear,
                    'notes' => $this->notesForYear($startYear, $currentAcademicStartYear),
                ]
            );

            $this->seedTermsForYear($academicYear, $today, $startYear, $endYear, $currentAcademicStartYear);
        }
    }

    private function seedTermsForYear(
        AcademicYear $academicYear,
        CarbonImmutable $today,
        int $startYear,
        int $endYear,
        int $currentAcademicStartYear
    ): void {
        $terms = [
            [
                'sort_order' => 10,
                'name' => 'الفصل الدراسي الأول',
                'code_suffix' => 'T1',
                'starts_on' => CarbonImmutable::create($startYear, 9, 1),
                'ends_on' => CarbonImmutable::create($endYear, 1, 15),
            ],
            [
                'sort_order' => 20,
                'name' => 'الفصل الدراسي الثاني',
                'code_suffix' => 'T2',
                'starts_on' => CarbonImmutable::create($endYear, 1, 20),
                'ends_on' => CarbonImmutable::create($endYear, 6, 15),
            ],
            [
                'sort_order' => 30,
                'name' => 'الفصل الصيفي',
                'code_suffix' => 'SUMMER',
                'starts_on' => CarbonImmutable::create($endYear, 7, 1),
                'ends_on' => CarbonImmutable::create($endYear, 8, 15),
            ],
        ];

        foreach ($terms as $term) {
            $termStart = $term['starts_on'];
            $termEnd = $term['ends_on'];

            AcademicTerm::query()->updateOrCreate(
                ['code' => sprintf('%s-%s', $academicYear->code, $term['code_suffix'])],
                [
                    'academic_year_id' => $academicYear->id,
                    'sort_order' => $term['sort_order'],
                    'name' => $term['name'],
                    'starts_on' => $termStart->toDateString(),
                    'ends_on' => $termEnd->toDateString(),
                    'status' => $this->statusForRange($today, $termStart, $termEnd),
                    'is_current' => $startYear === $currentAcademicStartYear
                        && $today->betweenIncluded($termStart, $termEnd),
                    'notes' => 'سجل تجريبي منظم لاختبار التقويم الأكاديمي والفلاتر والصلاحيات.',
                ]
            );
        }
    }

    private function statusForRange(CarbonImmutable $today, CarbonImmutable $startsOn, CarbonImmutable $endsOn): string
    {
        if ($today->lt($startsOn)) {
            return 'planned';
        }

        if ($today->gt($endsOn)) {
            return 'closed';
        }

        return 'active';
    }

    private function notesForYear(int $startYear, int $currentAcademicStartYear): string
    {
        if ($startYear === $currentAcademicStartYear) {
            return 'السنة الدراسية الحالية المستخدمة افتراضيًا في العمليات الأكاديمية.';
        }

        if ($startYear < $currentAcademicStartYear) {
            return 'سنة دراسية مغلقة موجودة لأغراض الأرشفة والتقارير التاريخية.';
        }

        return 'سنة دراسية مخططة للاستخدام المستقبلي واختبار فلاتر السنوات القادمة.';
    }
}
