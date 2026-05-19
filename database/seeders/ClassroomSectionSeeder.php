<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolSection;
use Illuminate\Database\Seeder;

class ClassroomSectionSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedClassrooms();
        $this->seedSections();
    }

    private function seedClassrooms(): void
    {
        $types = [
            'classroom',
            'classroom',
            'classroom',
            'lab',
            'library',
            'hall',
            'office',
        ];

        for ($index = 1; $index <= 60; $index++) {
            $floor = (int) ceil($index / 15);
            $roomNumber = (($floor - 1) * 100) + (($index - 1) % 15) + 1;
            $type = $types[($index - 1) % count($types)];

            Classroom::query()->updateOrCreate(
                ['code' => sprintf('ROOM-%03d', $index)],
                [
                    'name' => sprintf('قاعة %d', $roomNumber),
                    'building' => $index <= 30 ? 'المبنى الرئيسي' : 'المبنى الأكاديمي',
                    'floor' => sprintf('الطابق %d', $floor),
                    'room_number' => (string) $roomNumber,
                    'capacity' => match ($type) {
                        'lab' => 24,
                        'library' => 40,
                        'hall' => 120,
                        'office' => 8,
                        default => 32,
                    },
                    'type' => $type,
                    'is_active' => true,
                    'sort_order' => $index * 10,
                    'notes' => $type === 'classroom'
                        ? 'قاعة صفية قياسية مجهزة للاستخدام اليومي.'
                        : 'مساحة مدرسية متخصصة تستخدم بحسب الجدول والحاجة.',
                ]
            );
        }
    }

    private function seedSections(): void
    {
        $academicYear = AcademicYear::query()
            ->where('is_current', true)
            ->first()
            ?: AcademicYear::query()->orderByDesc('starts_on')->first();

        if (! $academicYear instanceof AcademicYear) {
            return;
        }

        $grades = Grade::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($grades->isEmpty()) {
            return;
        }

        $classrooms = Classroom::query()
            ->where('type', 'classroom')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $letters = [
            'A' => 'الشعبة أ',
            'B' => 'الشعبة ب',
            'C' => 'الشعبة ج',
            'D' => 'الشعبة د',
        ];

        $classroomIndex = 0;

        foreach ($grades as $gradeIndex => $grade) {
            foreach ($letters as $letterCode => $letterName) {
                $classroom = $classrooms->get($classroomIndex % max($classrooms->count(), 1));
                $classroomIndex++;

                $sectionCode = sprintf(
                    '%s-%s-%s',
                    $academicYear->code,
                    $grade->code,
                    $letterCode
                );

                SchoolSection::query()->updateOrCreate(
                    ['code' => $sectionCode],
                    [
                        'academic_year_id' => $academicYear->id,
                        'grade_id' => $grade->id,
                        'classroom_id' => $classroom?->id,
                        'name' => $letterName,
                        'capacity' => 30,
                        'gender_policy' => 'mixed',
                        'status' => 'active',
                        'sort_order' => (($gradeIndex + 1) * 100) + match ($letterCode) {
                            'A' => 10,
                            'B' => 20,
                            'C' => 30,
                            default => 40,
                        },
                        'notes' => 'شعبة تجريبية افتراضية مرتبطة بالسنة الدراسية الحالية والصف الدراسي المحدد.',
                    ]
                );
            }
        }
    }
}
