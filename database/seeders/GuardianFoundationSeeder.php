<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GuardianFoundationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ensurePermissions();
        $this->seedGuardians();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensurePermissions(): void
    {
        $permissions = [
            [
                'name' => 'guardians.view',
                'display_name' => 'عرض أولياء الأمور',
                'description' => 'يسمح بمشاهدة بيانات أولياء الأمور المرتبطين بالطلاب.',
                'sort_order' => 410,
            ],
            [
                'name' => 'guardians.create',
                'display_name' => 'إضافة أولياء أمور',
                'description' => 'يسمح بإنشاء ملفات أولياء أمور جديدة.',
                'sort_order' => 420,
            ],
            [
                'name' => 'guardians.update',
                'display_name' => 'تعديل أولياء الأمور',
                'description' => 'يسمح بتعديل بيانات أولياء الأمور.',
                'sort_order' => 430,
            ],
            [
                'name' => 'guardians.export',
                'display_name' => 'تصدير أولياء الأمور',
                'description' => 'يسمح بتصدير بيانات أولياء الأمور إلى ملف Excel.',
                'sort_order' => 435,
            ],
            [
                'name' => 'guardians.import',
                'display_name' => 'استيراد أولياء الأمور',
                'description' => 'يسمح باستيراد بيانات أولياء الأمور من ملف Excel وفق القالب المعتمد.',
                'sort_order' => 436,
            ],
        ];

        $createdPermissions = collect();

        foreach ($permissions as $permissionData) {
            $createdPermissions->push(Permission::query()->updateOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => 'web'],
                [
                    'group_name' => 'الأشخاص',
                    'display_name' => $permissionData['display_name'],
                    'description' => $permissionData['description'],
                    'sort_order' => $permissionData['sort_order'],
                ]
            ));
        }

        Role::query()
            ->whereIn('name', ['super_admin', 'system_admin', 'school_admin', 'registrar'])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role): Role => $role->givePermissionTo($createdPermissions));
    }

    private function seedGuardians(): void
    {
        $students = Student::query()->orderBy('student_number')->get();

        if ($students->isEmpty()) {
            return;
        }

        $firstNamesMale = ['أحمد', 'محمود', 'خالد', 'حسام', 'فراس', 'بسام', 'طارق', 'ماهر', 'سامي', 'رامي', 'علي', 'حسن'];
        $firstNamesFemale = ['منى', 'سعاد', 'ريم', 'هالة', 'عبير', 'رنا', 'ليلى', 'نسرين', 'وفاء', 'سمر', 'هند', 'ديمة'];
        $fatherNames = ['محمد', 'محمود', 'خالد', 'علي', 'أحمد', 'حسن', 'سمير', 'عبد الرحمن'];
        $lastNames = ['الخطيب', 'الحسن', 'الحموي', 'الشامي', 'العلي', 'اليوسف', 'الناصر', 'المصري', 'الديري', 'القاسم'];
        $occupations = ['مهندس', 'طبيب', 'مدرس', 'محاسب', 'موظف', 'تاجر', 'محام', 'مدير مبيعات', 'ربة منزل', 'صيدلاني'];
        $relations = ['father', 'mother', 'father', 'mother', 'guardian'];

        for ($index = 1; $index <= 70; $index++) {
            $relationType = $relations[($index - 1) % count($relations)];
            $gender = $relationType === 'mother' ? 'female' : 'male';
            $firstName = $gender === 'female'
                ? $firstNamesFemale[($index - 1) % count($firstNamesFemale)]
                : $firstNamesMale[($index - 1) % count($firstNamesMale)];

            $guardianNumber = 'GUA-' . now()->format('Y') . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT);

            $guardian = Guardian::query()->updateOrCreate(
                ['guardian_number' => $guardianNumber],
                [
                    'first_name' => $firstName,
                    'father_name' => $fatherNames[$index % count($fatherNames)],
                    'last_name' => $lastNames[$index % count($lastNames)],
                    'gender' => $gender,
                    'relation_type' => $relationType,
                    'national_id' => 'GNID-' . str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                    'occupation' => $occupations[$index % count($occupations)],
                    'mobile' => '+963 944 ' . str_pad((string) (700000 + $index), 6, '0', STR_PAD_LEFT),
                    'phone' => '+963 11 ' . str_pad((string) (700000 + $index), 6, '0', STR_PAD_LEFT),
                    'email' => 'guardian' . $index . '@school-erp.local',
                    'address' => 'دمشق - حي المدارس - بناء ' . (($index % 12) + 1),
                    'workplace' => $index % 4 === 0 ? 'قطاع خاص' : 'قطاع عام',
                    'is_emergency_contact' => true,
                    'has_custody' => $index % 9 !== 0,
                    'is_financial_responsible' => $index % 5 !== 0,
                    'notes' => $index % 13 === 0 ? 'بيانات تجريبية لاختبار ملاحظات ولي الأمر.' : null,
                    'status' => $index % 17 === 0 ? 'inactive' : 'active',
                    'is_active' => $index % 17 !== 0,
                ]
            );

            $linkedStudents = collect([
                $students[($index - 1) % $students->count()],
                $students[$index % $students->count()],
            ])->unique('id');

            $syncData = [];
            foreach ($linkedStudents as $studentOffset => $student) {
                $syncData[$student->id] = [
                    'relationship_type' => $relationType,
                    'is_primary' => $studentOffset === 0,
                    'can_pick_up' => true,
                    'is_financial_responsible' => (bool) $guardian->is_financial_responsible,
                ];
            }

            $guardian->students()->syncWithoutDetaching($syncData);
        }
    }
}
