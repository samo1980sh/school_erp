<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolSection;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StudentFoundationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ensurePermissions();
        $this->seedStudents();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensurePermissions(): void
    {
        $permission = Permission::query()->updateOrCreate(
            ['name' => 'students.import', 'guard_name' => 'web'],
            [
                'group_name' => 'الأشخاص',
                'display_name' => 'استيراد الطلاب',
                'description' => 'يسمح باستيراد بيانات الطلاب من ملف Excel وفق القالب المعتمد.',
                'sort_order' => 405,
            ]
        );

        Role::query()
            ->whereIn('name', ['super_admin', 'system_admin', 'school_admin', 'registrar'])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role): Role => $role->givePermissionTo($permission));
    }

    private function seedStudents(): void
    {
        $currentYear = AcademicYear::query()->where('is_current', true)->first()
            ?? AcademicYear::query()->orderByDesc('starts_on')->first();

        $grades = Grade::query()->where('is_active', true)->orderBy('sort_order')->get();
        $sections = SchoolSection::query()->orderBy('grade_id')->orderBy('sort_order')->get();

        if (! $currentYear || $grades->isEmpty()) {
            return;
        }

        $firstNamesMale = ['أحمد', 'محمد', 'عمر', 'يوسف', 'كريم', 'محمود', 'حسام', 'رامي', 'أنس', 'فادي', 'سامر', 'ليث', 'يزن', 'مازن', 'نور الدين'];
        $firstNamesFemale = ['لين', 'تالا', 'نور', 'سارة', 'ريم', 'لمى', 'جنى', 'رنا', 'هبة', 'مريم', 'دانا', 'لارا', 'ملك', 'آية', 'شام'];
        $fatherNames = ['خالد', 'محمود', 'علي', 'حسن', 'أحمد', 'فراس', 'بسام', 'طارق', 'ماهر', 'سامر'];
        $motherNames = ['فاطمة', 'منى', 'سعاد', 'ريم', 'هالة', 'عبير', 'رنا', 'ليلى', 'نسرين', 'وفاء'];
        $lastNames = ['الخطيب', 'الحسن', 'الحموي', 'الشامي', 'العلي', 'اليوسف', 'الناصر', 'المصري', 'الديري', 'القاسم'];
        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'O+', 'O-'];
        $statuses = ['active', 'active', 'active', 'active', 'transferred', 'withdrawn'];

        for ($index = 1; $index <= 80; $index++) {
            $gender = $index % 3 === 0 ? 'female' : 'male';
            $firstName = $gender === 'female'
                ? $firstNamesFemale[($index - 1) % count($firstNamesFemale)]
                : $firstNamesMale[($index - 1) % count($firstNamesMale)];

            $grade = $grades[($index - 1) % $grades->count()];
            $gradeSections = $sections->where('grade_id', $grade->id)->values();
            $section = $gradeSections->isNotEmpty()
                ? $gradeSections[($index - 1) % $gradeSections->count()]
                : null;

            $studentNumber = 'STD-' . now()->format('Y') . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT);

            Student::query()->updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'first_name' => $firstName,
                    'father_name' => $fatherNames[$index % count($fatherNames)],
                    'mother_name' => $motherNames[$index % count($motherNames)],
                    'last_name' => $lastNames[$index % count($lastNames)],
                    'gender' => $gender,
                    'birth_date' => now()->subYears(6 + (($index - 1) % 12))->subDays($index * 9)->toDateString(),
                    'place_of_birth' => $index % 4 === 0 ? 'ريف دمشق' : 'دمشق',
                    'national_id' => 'NID-' . str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                    'enrollment_date' => $currentYear->starts_on,
                    'current_academic_year_id' => $currentYear->id,
                    'current_grade_id' => $grade->id,
                    'current_section_id' => $section?->id,
                    'phone' => '+963 944 ' . str_pad((string) (550000 + $index), 6, '0', STR_PAD_LEFT),
                    'email' => 'student' . $index . '@school-erp.local',
                    'address' => 'دمشق - حي المدارس - بناء ' . (($index % 12) + 1),
                    'blood_type' => $bloodTypes[$index % count($bloodTypes)],
                    'medical_notes' => $index % 17 === 0 ? 'حساسية غذائية تحتاج متابعة.' : null,
                    'notes' => $index % 11 === 0 ? 'بيانات تجريبية لاختبار الملاحظات.' : null,
                    'status' => $statuses[$index % count($statuses)],
                    'is_active' => $index % 13 !== 0,
                ]
            );
        }
    }
}
