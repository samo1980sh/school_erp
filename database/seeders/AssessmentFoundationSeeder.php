<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\GradeSubject;
use App\Models\StudentEnrollment;
use App\Models\StudentMark;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AssessmentFoundationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ensurePermissions();
        $this->seedExamsAndMarks();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensurePermissions(): void
    {
        $permissions = [
            ['name' => 'exams.view', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'عرض الاختبارات', 'description' => 'يسمح بمشاهدة الاختبارات والامتحانات المرتبطة بالصفوف والمواد.', 'sort_order' => 610],
            ['name' => 'exams.create', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'إضافة اختبارات', 'description' => 'يسمح بإنشاء اختبارات وامتحانات جديدة للمواد والصفوف.', 'sort_order' => 620],
            ['name' => 'exams.update', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'تعديل الاختبارات', 'description' => 'يسمح بتعديل بيانات الاختبارات والدرجات العظمى وحالتها.', 'sort_order' => 630],
            ['name' => 'exams.export', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'تصدير الاختبارات', 'description' => 'يسمح بتصدير بيانات الاختبارات إلى Excel.', 'sort_order' => 635],
            ['name' => 'exams.import', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'استيراد الاختبارات', 'description' => 'يسمح باستيراد بيانات الاختبارات من ملفات Excel.', 'sort_order' => 636],
            ['name' => 'marks.view', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'عرض الدرجات', 'description' => 'يسمح بمشاهدة درجات الطلاب في الاختبارات.', 'sort_order' => 640],
            ['name' => 'marks.create', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'إدخال الدرجات', 'description' => 'يسمح بإدخال درجات الطلاب للاختبارات.', 'sort_order' => 650],
            ['name' => 'marks.update', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'تعديل الدرجات', 'description' => 'يسمح بتعديل درجات الطلاب وحالاتها.', 'sort_order' => 660],
            ['name' => 'marks.reports', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'تقارير الدرجات', 'description' => 'يسمح بعرض تقارير الدرجات والنتائج.', 'sort_order' => 665],
            ['name' => 'marks.export', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'تصدير الدرجات', 'description' => 'يسمح بتصدير درجات الطلاب إلى Excel.', 'sort_order' => 666],
            ['name' => 'marks.import', 'group_name' => 'المواد والاختبارات والدرجات', 'display_name' => 'استيراد الدرجات', 'description' => 'يسمح باستيراد درجات الطلاب من ملفات Excel.', 'sort_order' => 667],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'group_name' => $permission['group_name'],
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'sort_order' => $permission['sort_order'],
                ]
            );
        }
    }

    private function seedExamsAndMarks(): void
    {
        $year = AcademicYear::query()->where('is_current', true)->first()
            ?? AcademicYear::query()->orderByDesc('starts_on')->first();

        if (! $year) {
            return;
        }

        $term = AcademicTerm::query()->where('academic_year_id', $year->id)->where('is_current', true)->first()
            ?? AcademicTerm::query()->where('academic_year_id', $year->id)->orderBy('sort_order')->first();

        if (! $term) {
            return;
        }

        $enrollmentsByGrade = StudentEnrollment::query()
            ->with('student:id,student_number,first_name,father_name,last_name')
            ->where('academic_year_id', $year->id)
            ->whereIn('status', ['active', 'promoted', 'graduated'])
            ->orderBy('grade_id')
            ->orderBy('section_id')
            ->orderBy('id')
            ->get()
            ->groupBy('grade_id');

        if ($enrollmentsByGrade->isEmpty()) {
            $enrollmentsByGrade = StudentEnrollment::query()
                ->with('student:id,student_number,first_name,father_name,last_name')
                ->where('academic_year_id', $year->id)
                ->orderBy('grade_id')
                ->orderBy('section_id')
                ->orderBy('id')
                ->get()
                ->groupBy('grade_id');
        }

        if ($enrollmentsByGrade->isEmpty()) {
            return;
        }

        $examTypes = [
            ['type' => 'monthly', 'name' => 'اختبار شهري', 'max' => 100, 'passing' => 50, 'weight' => 30, 'status' => 'published', 'week' => 4],
            ['type' => 'midterm', 'name' => 'اختبار منتصف الفصل', 'max' => 100, 'passing' => 50, 'weight' => 30, 'status' => 'published', 'week' => 8],
            ['type' => 'final', 'name' => 'الاختبار النهائي', 'max' => 100, 'passing' => 50, 'weight' => 40, 'status' => 'planned', 'week' => 14],
        ];

        $sortOrder = 10;

        foreach ($enrollmentsByGrade as $gradeId => $enrollments) {
            $subjects = $this->subjectsForGrade((int) $gradeId, $year->id);

            if ($subjects->isEmpty()) {
                continue;
            }

            foreach ($subjects as $subject) {
                foreach ($examTypes as $examType) {
                    $subjectCode = (string) ($subject->code ?: 'SUBJECT');
                    $code = sprintf('EX-%s-G%s-%s-%s', $year->name, $gradeId, $subjectCode, strtoupper($examType['type']));

                    $exam = Exam::query()->updateOrCreate(
                        ['code' => $code],
                        [
                            'academic_year_id' => $year->id,
                            'academic_term_id' => $term->id,
                            'grade_id' => (int) $gradeId,
                            'subject_id' => $subject->id,
                            'name' => $examType['name'] . ' - ' . ($subject->name ?? $subjectCode),
                            'exam_type' => $examType['type'],
                            'exam_date' => Carbon::parse($term->starts_on ?? now())->addWeeks((int) $examType['week'])->toDateString(),
                            'max_mark' => $examType['max'],
                            'passing_mark' => $examType['passing'],
                            'weight_percent' => $examType['weight'],
                            'status' => $examType['status'],
                            'notes' => 'بيانات تجريبية للاختبارات والدرجات.',
                            'sort_order' => $sortOrder,
                        ]
                    );

                    $this->seedMarksForExam($exam, $enrollments);
                    $sortOrder += 10;
                }
            }
        }
    }

    private function subjectsForGrade(int $gradeId, int $yearId): Collection
    {
        $plans = GradeSubject::query()
            ->with('subject:id,name,code')
            ->where('academic_year_id', $yearId)
            ->where('grade_id', $gradeId)
            ->where('status', 'active')
            ->orderByDesc('is_core')
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        if ($plans->isNotEmpty()) {
            return $plans
                ->pluck('subject')
                ->filter()
                ->unique('id')
                ->values();
        }

        return Subject::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->limit(4)
            ->get();
    }

    private function seedMarksForExam(Exam $exam, Collection $enrollments): void
    {
        foreach ($enrollments->take(35)->values() as $index => $enrollment) {
            $mark = null;
            $status = 'draft';

            if ($exam->status === 'published') {
                $base = 52 + (($index * 7 + $exam->id) % 45);
                $mark = min((float) $exam->max_mark, (float) $base);
                $status = 'final';
            }

            StudentMark::query()->updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $enrollment->student_id,
                ],
                [
                    'student_enrollment_id' => $enrollment->id,
                    'academic_year_id' => $exam->academic_year_id,
                    'academic_term_id' => $exam->academic_term_id,
                    'grade_id' => $exam->grade_id,
                    'section_id' => $enrollment->section_id,
                    'subject_id' => $exam->subject_id,
                    'mark' => $mark,
                    'max_mark' => $exam->max_mark,
                    'status' => $status,
                    'notes' => null,
                    'recorded_at' => $status === 'final' ? now() : null,
                ]
            );
        }
    }
}
