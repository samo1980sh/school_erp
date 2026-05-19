<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TeacherFoundationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedPermissions();
        $this->seedTeachers();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedPermissions(): void
    {
        $permissions = [
            [
                'name' => 'teachers.export',
                'display_name' => 'تصدير المعلمين',
                'description' => 'يسمح بتصدير بيانات المعلمين إلى ملف Excel لاستخدامها في التقارير أو المراجعة.',
                'sort_order' => 470,
            ],
            [
                'name' => 'teachers.import',
                'display_name' => 'استيراد المعلمين',
                'description' => 'يسمح باستيراد بيانات المعلمين من ملف Excel وفق القالب المعتمد.',
                'sort_order' => 475,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                ],
                [
                    'group_name' => 'الأشخاص',
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'sort_order' => $permission['sort_order'],
                ]
            );
        }

        $teacherPermissions = [
            'teachers.view',
            'teachers.create',
            'teachers.update',
            'teachers.export',
            'teachers.import',
        ];

        Role::query()
            ->whereIn('name', ['super_admin', 'system_admin', 'school_admin', 'academic_manager'])
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Role $role) use ($teacherPermissions): void {
                $role->givePermissionTo($teacherPermissions);
            });
    }

    private function seedTeachers(): void
    {
        $firstNames = [
            'أحمد', 'محمد', 'محمود', 'خالد', 'سامر', 'رامي', 'عمر', 'يوسف', 'حسام', 'علاء',
            'نور', 'سارة', 'ريم', 'هبة', 'لينا', 'رنا', 'مها', 'دانا', 'ميساء', 'عبير',
        ];

        $lastNames = [
            'الخطيب', 'الحسن', 'المصري', 'العلي', 'الديب', 'العباس', 'النجار', 'الشامي',
            'الحموي', 'القدسي', 'اليوسف', 'العثمان', 'السيد', 'الحلبي', 'الرفاعي',
        ];

        $specializations = [
            ['اللغة العربية', 'إجازة في اللغة العربية', 'معلم لغة عربية'],
            ['اللغة الإنكليزية', 'إجازة في الأدب الإنكليزي', 'معلم لغة إنكليزية'],
            ['الرياضيات', 'إجازة في الرياضيات', 'معلم رياضيات'],
            ['العلوم', 'إجازة في العلوم', 'معلم علوم'],
            ['الفيزياء', 'إجازة في الفيزياء', 'معلم فيزياء'],
            ['الكيمياء', 'إجازة في الكيمياء', 'معلم كيمياء'],
            ['التاريخ', 'إجازة في التاريخ', 'معلم تاريخ'],
            ['الجغرافيا', 'إجازة في الجغرافيا', 'معلم جغرافيا'],
            ['التربية الإسلامية', 'إجازة في الشريعة', 'معلم تربية إسلامية'],
            ['المعلوماتية', 'إجازة في هندسة المعلوماتية', 'معلم معلوماتية'],
            ['التربية الفنية', 'إجازة في الفنون الجميلة', 'معلم تربية فنية'],
            ['التربية الرياضية', 'إجازة في التربية الرياضية', 'معلم تربية رياضية'],
        ];

        for ($i = 1; $i <= 60; $i++) {
            $firstName = $firstNames[($i - 1) % count($firstNames)];
            $lastName = $lastNames[($i - 1) % count($lastNames)];
            [$specialization, $qualification, $jobTitle] = $specializations[($i - 1) % count($specializations)];

            $isFemale = in_array($firstName, ['نور', 'سارة', 'ريم', 'هبة', 'لينا', 'رنا', 'مها', 'دانا', 'ميساء', 'عبير'], true);
            $teacherNumber = sprintf('TCH-2026-%04d', $i);

            Teacher::query()->updateOrCreate(
                ['teacher_number' => $teacherNumber],
                [
                    'full_name' => $firstName . ' ' . $lastName,
                    'gender' => $isFemale ? 'female' : 'male',
                    'national_id' => sprintf('02%09d', 300000 + $i),
                    'birth_date' => now()->subYears(25 + ($i % 25))->subDays($i * 7)->format('Y-m-d'),
                    'email' => sprintf('teacher.%03d@school-erp.local', $i),
                    'phone' => sprintf('+963 11 555 %04d', 2000 + $i),
                    'mobile' => sprintf('+963 9%02d 555 %03d', 30 + ($i % 60), $i),
                    'address' => 'دمشق - حي تعليمي رقم ' . (($i % 12) + 1),
                    'qualification' => $qualification,
                    'specialization' => $specialization,
                    'job_title' => $jobTitle,
                    'employment_type' => $i % 13 === 0 ? 'visiting' : ($i % 7 === 0 ? 'part_time' : 'full_time'),
                    'hire_date' => now()->subYears($i % 12)->startOfYear()->addMonths(8)->format('Y-m-d'),
                    'status' => $i % 17 === 0 ? 'on_leave' : 'active',
                    'notes' => $i % 10 === 0 ? 'بيانات تجريبية لاختبار البحث والفلاتر والتصدير.' : null,
                ]
            );
        }
    }
}
