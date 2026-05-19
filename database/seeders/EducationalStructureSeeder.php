<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EducationalStage;
use App\Models\Grade;
use Illuminate\Database\Seeder;

class EducationalStructureSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            [
                'code' => 'KINDERGARTEN',
                'sort_order' => 10,
                'name' => 'رياض الأطفال',
                'description' => 'مرحلة تمهيدية قبل التعليم الأساسي، وتشمل صفوف الروضة الأولى والثانية والثالثة حسب سياسة المدرسة.',
                'is_active' => true,
            ],
            [
                'code' => 'BASIC-CYCLE-1',
                'sort_order' => 20,
                'name' => 'الحلقة الأولى من التعليم الأساسي',
                'description' => 'تشمل الصفوف من الأول حتى السادس، وتستخدم كأساس لتنظيم الصفوف والشعب والمواد لاحقًا.',
                'is_active' => true,
            ],
            [
                'code' => 'BASIC-CYCLE-2',
                'sort_order' => 30,
                'name' => 'الحلقة الثانية من التعليم الأساسي',
                'description' => 'تشمل الصفوف من السابع حتى التاسع، وتستخدم لتنظيم المرحلة الانتقالية قبل التعليم الثانوي.',
                'is_active' => true,
            ],
            [
                'code' => 'SECONDARY',
                'sort_order' => 40,
                'name' => 'المرحلة الثانوية',
                'description' => 'تشمل الصفوف الثانوية والمسارات العلمية أو الأدبية بحسب خطة المدرسة.',
                'is_active' => true,
            ],
        ];

        $stageModels = [];

        foreach ($stages as $stageData) {
            $stageModels[$stageData['code']] = EducationalStage::query()->updateOrCreate(
                ['code' => $stageData['code']],
                $stageData
            );
        }

        $grades = [
            ['stage' => 'KINDERGARTEN', 'sort_order' => 10, 'name' => 'الروضة الأولى', 'code' => 'KG-1', 'grade_number' => null, 'description' => 'الصف الأول في مرحلة رياض الأطفال.'],
            ['stage' => 'KINDERGARTEN', 'sort_order' => 20, 'name' => 'الروضة الثانية', 'code' => 'KG-2', 'grade_number' => null, 'description' => 'الصف الثاني في مرحلة رياض الأطفال.'],
            ['stage' => 'KINDERGARTEN', 'sort_order' => 30, 'name' => 'الروضة الثالثة', 'code' => 'KG-3', 'grade_number' => null, 'description' => 'الصف الثالث التمهيدي قبل الانتقال إلى التعليم الأساسي.'],
            ['stage' => 'BASIC-CYCLE-1', 'sort_order' => 110, 'name' => 'الصف الأول', 'code' => 'GRADE-01', 'grade_number' => 1, 'description' => 'الصف الأول من التعليم الأساسي.'],
            ['stage' => 'BASIC-CYCLE-1', 'sort_order' => 120, 'name' => 'الصف الثاني', 'code' => 'GRADE-02', 'grade_number' => 2, 'description' => 'الصف الثاني من التعليم الأساسي.'],
            ['stage' => 'BASIC-CYCLE-1', 'sort_order' => 130, 'name' => 'الصف الثالث', 'code' => 'GRADE-03', 'grade_number' => 3, 'description' => 'الصف الثالث من التعليم الأساسي.'],
            ['stage' => 'BASIC-CYCLE-1', 'sort_order' => 140, 'name' => 'الصف الرابع', 'code' => 'GRADE-04', 'grade_number' => 4, 'description' => 'الصف الرابع من التعليم الأساسي.'],
            ['stage' => 'BASIC-CYCLE-1', 'sort_order' => 150, 'name' => 'الصف الخامس', 'code' => 'GRADE-05', 'grade_number' => 5, 'description' => 'الصف الخامس من التعليم الأساسي.'],
            ['stage' => 'BASIC-CYCLE-1', 'sort_order' => 160, 'name' => 'الصف السادس', 'code' => 'GRADE-06', 'grade_number' => 6, 'description' => 'الصف السادس من التعليم الأساسي.'],
            ['stage' => 'BASIC-CYCLE-2', 'sort_order' => 210, 'name' => 'الصف السابع', 'code' => 'GRADE-07', 'grade_number' => 7, 'description' => 'الصف السابع من التعليم الأساسي.'],
            ['stage' => 'BASIC-CYCLE-2', 'sort_order' => 220, 'name' => 'الصف الثامن', 'code' => 'GRADE-08', 'grade_number' => 8, 'description' => 'الصف الثامن من التعليم الأساسي.'],
            ['stage' => 'BASIC-CYCLE-2', 'sort_order' => 230, 'name' => 'الصف التاسع', 'code' => 'GRADE-09', 'grade_number' => 9, 'description' => 'الصف التاسع من التعليم الأساسي.'],
            ['stage' => 'SECONDARY', 'sort_order' => 310, 'name' => 'الصف العاشر', 'code' => 'GRADE-10', 'grade_number' => 10, 'description' => 'الصف الأول في المرحلة الثانوية.'],
            ['stage' => 'SECONDARY', 'sort_order' => 320, 'name' => 'الصف الحادي عشر العلمي', 'code' => 'GRADE-11-SCI', 'grade_number' => 11, 'description' => 'الصف الحادي عشر - المسار العلمي.'],
            ['stage' => 'SECONDARY', 'sort_order' => 330, 'name' => 'الصف الحادي عشر الأدبي', 'code' => 'GRADE-11-LIT', 'grade_number' => 11, 'description' => 'الصف الحادي عشر - المسار الأدبي.'],
            ['stage' => 'SECONDARY', 'sort_order' => 340, 'name' => 'الصف الثاني عشر العلمي', 'code' => 'GRADE-12-SCI', 'grade_number' => 12, 'description' => 'الصف الثاني عشر - المسار العلمي.'],
            ['stage' => 'SECONDARY', 'sort_order' => 350, 'name' => 'الصف الثاني عشر الأدبي', 'code' => 'GRADE-12-LIT', 'grade_number' => 12, 'description' => 'الصف الثاني عشر - المسار الأدبي.'],
        ];

        foreach ($grades as $gradeData) {
            $stage = $stageModels[$gradeData['stage']] ?? null;

            if (! $stage instanceof EducationalStage) {
                continue;
            }

            Grade::query()->updateOrCreate(
                ['code' => $gradeData['code']],
                [
                    'educational_stage_id' => $stage->id,
                    'sort_order' => $gradeData['sort_order'],
                    'name' => $gradeData['name'],
                    'grade_number' => $gradeData['grade_number'],
                    'description' => $gradeData['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
