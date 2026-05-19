<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolSection;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StudentEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedPermissions();
        $this->seedEnrollments();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedPermissions(): void
    {
        $permissions = [
            [
                'name' => 'enrollments.export',
                'group_name' => 'التسجيل والانتساب',
                'display_name' => 'تصدير تسجيلات الطلاب',
                'description' => 'يسمح بتصدير سجلات تسجيل الطلاب وارتباطهم بالسنة والصف والشعبة إلى ملف Excel.',
                'sort_order' => 530,
            ],
            [
                'name' => 'enrollments.import',
                'group_name' => 'التسجيل والانتساب',
                'display_name' => 'استيراد تسجيلات الطلاب',
                'description' => 'يسمح باستيراد سجلات تسجيل الطلاب وارتباطهم بالصفوف والشعب من ملف Excel.',
                'sort_order' => 540,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                ],
                [
                    'group_name' => $permission['group_name'],
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'sort_order' => $permission['sort_order'],
                ]
            );
        }

        $rolePermissionMap = [
            'super_admin' => ['enrollments.export', 'enrollments.import'],
            'system_admin' => ['enrollments.export', 'enrollments.import'],
            'school_admin' => ['enrollments.export', 'enrollments.import'],
            'registrar' => ['enrollments.export', 'enrollments.import'],
        ];

        foreach ($rolePermissionMap as $roleName => $permissionNames) {
            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', $roleName)
                ->first();

            if ($role instanceof Role) {
                $role->givePermissionTo($permissionNames);
            }
        }
    }

    private function seedEnrollments(): void
    {
        $students = Student::query()->orderBy('id')->get();

        if ($students->isEmpty()) {
            return;
        }

        $currentYear = AcademicYear::query()
            ->where('is_current', true)
            ->first()
            ?? AcademicYear::query()->orderByDesc('starts_on')->first();

        if (! $currentYear instanceof AcademicYear) {
            return;
        }

        $currentTerm = AcademicTerm::query()
            ->where('academic_year_id', $currentYear->id)
            ->where('is_current', true)
            ->first()
            ?? AcademicTerm::query()
                ->where('academic_year_id', $currentYear->id)
                ->orderBy('sort_order')
                ->first();

        $grades = Grade::query()->where('is_active', true)->orderBy('sort_order')->get();
        $sections = SchoolSection::query()->where('is_active', true)->orderBy('sort_order')->get();

        if ($grades->isEmpty() || $sections->isEmpty()) {
            return;
        }

        foreach ($students as $index => $student) {
            $grade = $this->resolveStudentGrade($student, $grades, $index);
            $section = $this->resolveStudentSection($student, $sections, $grade, $currentYear, $index);

            if (! $grade instanceof Grade || ! $section instanceof SchoolSection) {
                continue;
            }

            StudentEnrollment::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $currentYear->id,
                ],
                [
                    'academic_term_id' => $currentTerm?->id,
                    'grade_id' => $grade->id,
                    'section_id' => $section->id,
                    'enrollment_number' => sprintf('ENR-%s-%04d', str_replace('AY-', '', $currentYear->code), $index + 1),
                    'enrollment_date' => $currentYear->starts_on ?? now()->toDateString(),
                    'enrollment_type' => $index % 7 === 0 ? 'transfer' : ($index % 5 === 0 ? 'returning' : 'new'),
                    'status' => 'enrolled',
                    'is_current' => true,
                    'previous_school' => $index % 7 === 0 ? 'مدرسة سابقة تجريبية' : null,
                    'registered_by_user_id' => null,
                    'notes' => $index % 9 === 0 ? 'بيان تجريبي لاختبار الملاحظات.' : null,
                ]
            );

            $this->syncStudentCurrentPlacement($student, $currentYear, $grade, $section);
        }
    }

    private function resolveStudentGrade(Student $student, $grades, int $index): ?Grade
    {
        if (isset($student->grade_id) && $student->grade_id) {
            $grade = $grades->firstWhere('id', $student->grade_id);

            if ($grade instanceof Grade) {
                return $grade;
            }
        }

        return $grades->values()->get($index % $grades->count());
    }

    private function resolveStudentSection(Student $student, $sections, Grade $grade, AcademicYear $year, int $index): ?SchoolSection
    {
        if (isset($student->section_id) && $student->section_id) {
            $section = $sections->firstWhere('id', $student->section_id);

            if ($section instanceof SchoolSection) {
                return $section;
            }
        }

        $matchingSections = $sections
            ->where('grade_id', $grade->id)
            ->where('academic_year_id', $year->id)
            ->values();

        if ($matchingSections->isEmpty()) {
            $matchingSections = $sections->where('grade_id', $grade->id)->values();
        }

        if ($matchingSections->isEmpty()) {
            return $sections->values()->get($index % $sections->count());
        }

        return $matchingSections->get($index % $matchingSections->count());
    }

    private function syncStudentCurrentPlacement(Student $student, AcademicYear $academicYear, Grade $grade, SchoolSection $section): void
    {
        $updates = [];

        if (Schema::hasColumn('students', 'academic_year_id')) {
            $updates['academic_year_id'] = $academicYear->id;
        }

        if (Schema::hasColumn('students', 'grade_id')) {
            $updates['grade_id'] = $grade->id;
        }

        if (Schema::hasColumn('students', 'section_id')) {
            $updates['section_id'] = $section->id;
        }

        if ($updates !== []) {
            $student->forceFill($updates)->save();
        }
    }
}
