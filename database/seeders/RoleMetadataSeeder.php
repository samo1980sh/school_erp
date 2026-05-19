<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleMetadataSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'مدير النظام الرئيسي',
                'description' => 'يمتلك الصلاحيات الكاملة على النظام بالكامل، ويُستخدم للحساب الرئيسي المحمي فقط.',
                'sort_order' => 10,
            ],
            [
                'name' => 'system_admin',
                'display_name' => 'مدير النظام',
                'description' => 'يدير إعدادات النظام والمستخدمين والأدوار والصلاحيات دون أن يكون الحساب الرئيسي المحمي.',
                'sort_order' => 20,
            ],
            [
                'name' => 'school_admin',
                'display_name' => 'مدير المدرسة',
                'description' => 'يدير العمليات الإدارية الأساسية الخاصة بالمدرسة ويتابع المستخدمين والبيانات التشغيلية.',
                'sort_order' => 30,
            ],
            [
                'name' => 'academic_manager',
                'display_name' => 'المدير الأكاديمي',
                'description' => 'يتابع الهيكل الأكاديمي والسنوات الدراسية والمراحل والصفوف والشعب والمواد والاختبارات.',
                'sort_order' => 40,
            ],
            [
                'name' => 'registrar',
                'display_name' => 'مسؤول التسجيل',
                'description' => 'يدير بيانات الطلاب وأولياء الأمور وعمليات التسجيل والانتساب والتحديثات المرتبطة بها.',
                'sort_order' => 50,
            ],
            [
                'name' => 'accountant',
                'display_name' => 'المحاسب',
                'description' => 'يتابع الرسوم والمدفوعات والتقارير المالية المرتبطة بالطلاب والمدرسة.',
                'sort_order' => 60,
            ],
            [
                'name' => 'teacher',
                'display_name' => 'المعلم',
                'description' => 'يمثل حساب المعلم، ويُستخدم لاحقًا للوصول إلى لوحة أو بوابة المعلم وفق الصلاحيات المحددة.',
                'sort_order' => 70,
            ],
            [
                'name' => 'guardian',
                'display_name' => 'ولي الأمر',
                'description' => 'يمثل حساب ولي الأمر، ويُستخدم لاحقًا للوصول إلى بوابة أولياء الأمور مع تقييد البيانات حسب الطلاب المرتبطين به.',
                'sort_order' => 80,
            ],
            [
                'name' => 'student',
                'display_name' => 'الطالب',
                'description' => 'يمثل حساب الطالب، ويُستخدم لاحقًا للوصول إلى بوابة الطالب مع تقييد البيانات الخاصة به فقط.',
                'sort_order' => 90,
            ],
            [
                'name' => 'limited_admin',
                'display_name' => 'مدير محدود الصلاحيات',
                'description' => 'حساب إداري محدود يستخدم للاختبار أو للموظفين الذين يحتاجون صلاحيات إدارية ضيقة فقط.',
                'sort_order' => 100,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::query()->updateOrCreate(
                [
                    'name' => $roleData['name'],
                    'guard_name' => 'web',
                ],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                    'sort_order' => $roleData['sort_order'],
                ]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
