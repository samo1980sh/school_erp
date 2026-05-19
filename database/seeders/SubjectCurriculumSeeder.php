<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeSubject;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['sort_order' => 10, 'name' => 'اللغة العربية', 'code' => 'ARABIC', 'category' => 'core', 'default_weekly_periods' => 5, 'description' => 'مادة اللغة العربية وتشمل القراءة والكتابة والقواعد والتعبير.'],
            ['sort_order' => 20, 'name' => 'اللغة الإنجليزية', 'code' => 'ENGLISH', 'category' => 'core', 'default_weekly_periods' => 4, 'description' => 'مادة اللغة الإنجليزية لتنمية مهارات القراءة والكتابة والمحادثة.'],
            ['sort_order' => 30, 'name' => 'الرياضيات', 'code' => 'MATH', 'category' => 'core', 'default_weekly_periods' => 5, 'description' => 'مادة الرياضيات وتشمل الحساب والجبر والهندسة وحل المشكلات.'],
            ['sort_order' => 40, 'name' => 'العلوم العامة', 'code' => 'SCIENCE', 'category' => 'core', 'default_weekly_periods' => 4, 'description' => 'مادة العلوم العامة للصفوف الأساسية.'],
            ['sort_order' => 50, 'name' => 'الفيزياء', 'code' => 'PHYSICS', 'category' => 'scientific', 'default_weekly_periods' => 3, 'description' => 'مادة الفيزياء للصفوف المتقدمة والمسار العلمي.'],
            ['sort_order' => 60, 'name' => 'الكيمياء', 'code' => 'CHEMISTRY', 'category' => 'scientific', 'default_weekly_periods' => 3, 'description' => 'مادة الكيمياء للصفوف المتقدمة والمسار العلمي.'],
            ['sort_order' => 70, 'name' => 'علم الأحياء', 'code' => 'BIOLOGY', 'category' => 'scientific', 'default_weekly_periods' => 3, 'description' => 'مادة الأحياء للصفوف المتقدمة والمسار العلمي.'],
            ['sort_order' => 80, 'name' => 'التربية الإسلامية', 'code' => 'ISLAMIC', 'category' => 'core', 'default_weekly_periods' => 2, 'description' => 'مادة التربية الإسلامية والقيم الأخلاقية.'],
            ['sort_order' => 90, 'name' => 'الدراسات الاجتماعية', 'code' => 'SOCIAL', 'category' => 'core', 'default_weekly_periods' => 3, 'description' => 'مادة الدراسات الاجتماعية للصفوف الأساسية.'],
            ['sort_order' => 100, 'name' => 'التاريخ', 'code' => 'HISTORY', 'category' => 'humanities', 'default_weekly_periods' => 2, 'description' => 'مادة التاريخ للصفوف المتوسطة والثانوية.'],
            ['sort_order' => 110, 'name' => 'الجغرافيا', 'code' => 'GEOGRAPHY', 'category' => 'humanities', 'default_weekly_periods' => 2, 'description' => 'مادة الجغرافيا للصفوف المتوسطة والثانوية.'],
            ['sort_order' => 120, 'name' => 'اللغة الفرنسية', 'code' => 'FRENCH', 'category' => 'language', 'default_weekly_periods' => 2, 'description' => 'لغة أجنبية إضافية حسب خطة المدرسة.'],
            ['sort_order' => 130, 'name' => 'الحاسوب وتقانة المعلومات', 'code' => 'COMPUTER', 'category' => 'skills', 'default_weekly_periods' => 2, 'description' => 'مهارات الحاسوب وتقانة المعلومات والتحول الرقمي.'],
            ['sort_order' => 140, 'name' => 'التربية الفنية', 'code' => 'ART', 'category' => 'activity', 'default_weekly_periods' => 1, 'description' => 'مادة التربية الفنية والأنشطة الإبداعية.'],
            ['sort_order' => 150, 'name' => 'التربية الموسيقية', 'code' => 'MUSIC', 'category' => 'activity', 'default_weekly_periods' => 1, 'description' => 'مادة التربية الموسيقية والأنشطة الفنية.'],
            ['sort_order' => 160, 'name' => 'التربية الرياضية', 'code' => 'PE', 'category' => 'activity', 'default_weekly_periods' => 2, 'description' => 'مادة التربية الرياضية والصحة البدنية.'],
            ['sort_order' => 170, 'name' => 'المهارات الحياتية', 'code' => 'LIFE-SKILLS', 'category' => 'skills', 'default_weekly_periods' => 1, 'description' => 'مهارات حياتية وسلوكية داعمة للطالب.'],
            ['sort_order' => 180, 'name' => 'التربية الوطنية', 'code' => 'CIVICS', 'category' => 'humanities', 'default_weekly_periods' => 1, 'description' => 'مفاهيم المواطنة والأنظمة والقيم الاجتماعية.'],
        ];

        foreach ($subjects as $subjectData) {
            Subject::query()->updateOrCreate(
                ['code' => $subjectData['code']],
                $subjectData + ['is_active' => true]
            );
        }

        $currentYear = AcademicYear::query()
            ->where('is_current', true)
            ->first()
            ?? AcademicYear::query()->orderByDesc('starts_on')->first();

        if (! $currentYear instanceof AcademicYear) {
            return;
        }

        $subjectsByCode = Subject::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('code');

        $grades = Grade::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($grades as $grade) {
            $subjectCodes = $this->subjectCodesForGrade($grade);
            $sortOrder = 10;

            foreach ($subjectCodes as $subjectCode) {
                $subject = $subjectsByCode->get($subjectCode);

                if (! $subject instanceof Subject) {
                    continue;
                }

                GradeSubject::query()->updateOrCreate(
                    [
                        'academic_year_id' => $currentYear->id,
                        'grade_id' => $grade->id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'sort_order' => $sortOrder,
                        'weekly_periods' => $this->weeklyPeriodsFor($subjectCode, (int) ($grade->grade_number ?? 0)),
                        'coefficient' => $this->coefficientFor($subjectCode),
                        'is_core' => ! in_array($subjectCode, ['ART', 'MUSIC', 'PE', 'LIFE-SKILLS'], true),
                        'is_exam_subject' => ! in_array($subjectCode, ['ART', 'MUSIC', 'PE', 'LIFE-SKILLS'], true),
                        'status' => 'active',
                        'notes' => null,
                    ]
                );

                $sortOrder += 10;
            }
        }
    }

    private function subjectCodesForGrade(Grade $grade): array
    {
        $number = (int) ($grade->grade_number ?? 0);

        if ($number === 0) {
            return ['ARABIC', 'ENGLISH', 'MATH', 'SCIENCE', 'ISLAMIC', 'ART', 'MUSIC', 'PE', 'LIFE-SKILLS'];
        }

        if ($number <= 4) {
            return ['ARABIC', 'ENGLISH', 'MATH', 'SCIENCE', 'ISLAMIC', 'SOCIAL', 'COMPUTER', 'ART', 'MUSIC', 'PE', 'LIFE-SKILLS'];
        }

        if ($number <= 9) {
            return ['ARABIC', 'ENGLISH', 'FRENCH', 'MATH', 'SCIENCE', 'ISLAMIC', 'HISTORY', 'GEOGRAPHY', 'COMPUTER', 'ART', 'PE', 'CIVICS'];
        }

        return ['ARABIC', 'ENGLISH', 'FRENCH', 'MATH', 'PHYSICS', 'CHEMISTRY', 'BIOLOGY', 'ISLAMIC', 'HISTORY', 'GEOGRAPHY', 'COMPUTER', 'PE', 'CIVICS'];
    }

    private function weeklyPeriodsFor(string $subjectCode, int $gradeNumber): int
    {
        return match ($subjectCode) {
            'ARABIC', 'MATH' => $gradeNumber >= 10 ? 5 : 6,
            'ENGLISH' => 4,
            'SCIENCE' => 4,
            'PHYSICS', 'CHEMISTRY', 'BIOLOGY' => 3,
            'FRENCH', 'COMPUTER', 'PE' => 2,
            'ART', 'MUSIC', 'LIFE-SKILLS', 'CIVICS' => 1,
            default => 2,
        };
    }

    private function coefficientFor(string $subjectCode): float
    {
        return match ($subjectCode) {
            'ARABIC', 'MATH' => 2.00,
            'ENGLISH', 'PHYSICS', 'CHEMISTRY', 'BIOLOGY', 'SCIENCE' => 1.50,
            default => 1.00,
        };
    }
}
