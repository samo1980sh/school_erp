<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EmployeeFoundationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedPermissions();
        $this->seedEmployees();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedPermissions(): void
    {
        $permissions = [
            [
                'name' => 'employees.view',
                'group_name' => 'الأشخاص',
                'display_name' => 'عرض الموظفين',
                'description' => 'يسمح بمشاهدة بيانات الموظفين الإداريين في المدرسة.',
                'sort_order' => 470,
            ],
            [
                'name' => 'employees.create',
                'group_name' => 'الأشخاص',
                'display_name' => 'إضافة موظفين',
                'description' => 'يسمح بإضافة ملفات موظفين إداريين جدد.',
                'sort_order' => 480,
            ],
            [
                'name' => 'employees.update',
                'group_name' => 'الأشخاص',
                'display_name' => 'تعديل الموظفين',
                'description' => 'يسمح بتعديل بيانات الموظفين الإداريين.',
                'sort_order' => 490,
            ],
            [
                'name' => 'employees.export',
                'group_name' => 'الأشخاص',
                'display_name' => 'تصدير الموظفين',
                'description' => 'يسمح بتصدير بيانات الموظفين إلى ملف Excel.',
                'sort_order' => 491,
            ],
            [
                'name' => 'employees.import',
                'group_name' => 'الأشخاص',
                'display_name' => 'استيراد الموظفين',
                'description' => 'يسمح باستيراد بيانات الموظفين من ملف Excel بعد التحقق منها.',
                'sort_order' => 492,
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

        foreach (['super_admin', 'system_admin', 'school_admin'] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role) {
                $role->givePermissionTo(array_column($permissions, 'name'));
            }
        }
    }

    private function seedEmployees(): void
    {
        $firstNames = [
            'أحمد', 'محمد', 'محمود', 'خالد', 'رامي', 'سامي', 'ليلى', 'هبة', 'نور', 'سارة',
            'مريم', 'رنا', 'مازن', 'فادي', 'حسام', 'علي', 'عمر', 'جمال', 'ندى', 'دانا',
        ];

        $fatherNames = ['محمود', 'حسن', 'خالد', 'محمد', 'أحمد', 'فؤاد', 'جمال', 'سعيد'];
        $lastNames = ['الخطيب', 'الشامي', 'العلي', 'اليوسف', 'الحموي', 'الدمشقي', 'النجار', 'الحسن'];

        $jobs = [
            ['title' => 'مدير إداري', 'department' => 'الإدارة'],
            ['title' => 'سكرتير المدرسة', 'department' => 'الإدارة'],
            ['title' => 'موظف شؤون طلاب', 'department' => 'شؤون الطلاب'],
            ['title' => 'محاسب', 'department' => 'المالية'],
            ['title' => 'أمين صندوق', 'department' => 'المالية'],
            ['title' => 'مشرف طلاب', 'department' => 'الإشراف'],
            ['title' => 'مسؤول استقبال', 'department' => 'الاستقبال'],
            ['title' => 'مسؤول نقل', 'department' => 'النقل المدرسي'],
            ['title' => 'أمين مكتبة', 'department' => 'المكتبة'],
            ['title' => 'مسؤول تقنية معلومات', 'department' => 'تقنية المعلومات'],
        ];

        $qualifications = ['إجازة جامعية', 'معهد متوسط', 'ثانوية عامة', 'دبلوم إدارة', 'دبلوم محاسبة'];
        $specializations = ['إدارة أعمال', 'محاسبة', 'تربية', 'تقنية معلومات', 'شؤون طلاب', 'إدارة مكتبية'];

        for ($i = 1; $i <= 60; $i++) {
            $job = $jobs[($i - 1) % count($jobs)];
            $firstName = $firstNames[($i - 1) % count($firstNames)];
            $fatherName = $fatherNames[($i - 1) % count($fatherNames)];
            $lastName = $lastNames[($i - 1) % count($lastNames)];

            Employee::query()->updateOrCreate(
                ['employee_number' => 'EMP-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                [
                    'first_name' => $firstName,
                    'father_name' => $fatherName,
                    'last_name' => $lastName,
                    'gender' => in_array($firstName, ['ليلى', 'هبة', 'نور', 'سارة', 'مريم', 'رنا', 'ندى', 'دانا'], true) ? 'female' : 'male',
                    'birth_date' => now()->subYears(24 + ($i % 25))->subDays($i * 7)->toDateString(),
                    'national_id' => 'EMP-NID-' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                    'marital_status' => $i % 3 === 0 ? 'married' : 'single',
                    'job_title' => $job['title'],
                    'department' => $job['department'],
                    'employment_type' => 'administrative',
                    'hire_date' => now()->subYears($i % 12)->subMonths($i % 10)->toDateString(),
                    'contract_type' => $i % 4 === 0 ? 'part_time' : 'full_time',
                    'status' => $i % 17 === 0 ? 'on_leave' : 'active',
                    'email' => 'employee' . $i . '@school-erp.local',
                    'phone' => '+963 11 ' . str_pad((string) (5000000 + $i), 7, '0', STR_PAD_LEFT),
                    'mobile' => '+963 9' . str_pad((string) (40000000 + $i), 8, '0', STR_PAD_LEFT),
                    'address' => 'دمشق - حي المدارس - بناء ' . (($i % 20) + 1),
                    'qualification' => $qualifications[($i - 1) % count($qualifications)],
                    'specialization' => $specializations[($i - 1) % count($specializations)],
                    'notes' => $i % 10 === 0 ? 'سجل تجريبي لاختبار الفلاتر والتصدير.' : null,
                    'sort_order' => $i * 10,
                    'is_active' => $i % 17 !== 0,
                ]
            );
        }
    }
}
