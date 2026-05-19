<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacProfessionalSeeder extends Seeder
{
    private string $guardName = 'web';

    private string $defaultPassword = '23250077';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionDefinitions = $this->permissionDefinitions();
        $permissionNames = array_keys($permissionDefinitions);

        $sortOrder = 10;

        foreach ($permissionDefinitions as $permissionName => $metadata) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $this->guardName,
            ]);

            $permission->forceFill([
                'group_name' => $metadata['group_name'],
                'display_name' => $metadata['display_name'],
                'description' => $metadata['description'],
                'sort_order' => $sortOrder,
            ])->save();

            $sortOrder += 10;
        }

        foreach ($this->roles() as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $this->guardName,
            ]);

            $role->syncPermissions($rolePermissions === ['*'] ? $permissionNames : $rolePermissions);
        }

        $this->seedUsers();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissionDefinitions(): array
    {
        return [
            'admin_panel.access' => [
                'group_name' => 'لوحة الإدارة',
                'display_name' => 'الدخول إلى لوحة الإدارة',
                'description' => 'يسمح للمستخدم بتسجيل الدخول إلى لوحة إدارة النظام الرئيسية.',
            ],
            'dashboard.view' => [
                'group_name' => 'لوحة الإدارة',
                'display_name' => 'عرض لوحة التحكم',
                'description' => 'يسمح للمستخدم بمشاهدة صفحة لوحة التحكم الرئيسية والمؤشرات العامة.',
            ],

            'users.view' => [
                'group_name' => 'إدارة المستخدمين',
                'display_name' => 'عرض المستخدمين',
                'description' => 'يسمح بالدخول إلى صفحة المستخدمين واستعراض الحسابات الموجودة في النظام.',
            ],
            'users.create' => [
                'group_name' => 'إدارة المستخدمين',
                'display_name' => 'إضافة مستخدمين',
                'description' => 'يسمح بإنشاء حسابات مستخدمين جديدة داخل النظام.',
            ],
            'users.update' => [
                'group_name' => 'إدارة المستخدمين',
                'display_name' => 'تعديل المستخدمين',
                'description' => 'يسمح بتعديل بيانات المستخدمين الأساسية مثل الاسم والبريد الإلكتروني.',
            ],
            'users.change_password' => [
                'group_name' => 'إدارة المستخدمين',
                'display_name' => 'تغيير كلمات المرور',
                'description' => 'يسمح بتغيير كلمة مرور المستخدمين من لوحة الإدارة.',
            ],
            'users.assign_roles' => [
                'group_name' => 'إدارة المستخدمين',
                'display_name' => 'ربط المستخدمين بالأدوار',
                'description' => 'يسمح بإسناد الأدوار الإدارية أو إزالتها من حسابات المستخدمين.',
            ],

            'roles.view' => [
                'group_name' => 'الأدوار والصلاحيات',
                'display_name' => 'عرض الأدوار',
                'description' => 'يسمح بمشاهدة قائمة الأدوار الإدارية وتعريفاتها.',
            ],
            'roles.create' => [
                'group_name' => 'الأدوار والصلاحيات',
                'display_name' => 'إضافة أدوار',
                'description' => 'يسمح بإنشاء أدوار جديدة لاستخدامها في توزيع الصلاحيات.',
            ],
            'roles.update' => [
                'group_name' => 'الأدوار والصلاحيات',
                'display_name' => 'تعديل الأدوار',
                'description' => 'يسمح بتعديل أسماء الأدوار وبياناتها الأساسية، باستثناء الأدوار المحمية.',
            ],
            'roles.assign_permissions' => [
                'group_name' => 'الأدوار والصلاحيات',
                'display_name' => 'ربط الصلاحيات بالأدوار',
                'description' => 'يسمح بتحديد الصلاحيات المرتبطة بكل دور إداري.',
            ],

            'permissions.view' => [
                'group_name' => 'الأدوار والصلاحيات',
                'display_name' => 'عرض الصلاحيات',
                'description' => 'يسمح بمشاهدة الصلاحيات المتاحة في النظام وتصنيفاتها.',
            ],
            'permissions.create' => [
                'group_name' => 'الأدوار والصلاحيات',
                'display_name' => 'إضافة صلاحيات',
                'description' => 'يسمح بإنشاء صلاحيات جديدة عند إضافة أقسام أو وظائف جديدة للنظام.',
            ],
            'permissions.update' => [
                'group_name' => 'الأدوار والصلاحيات',
                'display_name' => 'تعديل الصلاحيات',
                'description' => 'يسمح بتعديل بيانات الصلاحيات ووصفها وتصنيفها.',
            ],

            'school_identity.view' => [
                'group_name' => 'هوية المدرسة والإعدادات',
                'display_name' => 'عرض هوية المدرسة',
                'description' => 'يسمح بمشاهدة بيانات المدرسة الأساسية مثل الاسم والشعار ومعلومات التواصل.',
            ],
            'school_identity.update' => [
                'group_name' => 'هوية المدرسة والإعدادات',
                'display_name' => 'تعديل هوية المدرسة',
                'description' => 'يسمح بتحديث بيانات هوية المدرسة والشعار ومعلومات التواصل.',
            ],
            'system_settings.view' => [
                'group_name' => 'هوية المدرسة والإعدادات',
                'display_name' => 'عرض إعدادات النظام',
                'description' => 'يسمح بمشاهدة الإعدادات العامة التي تتحكم بسلوك النظام.',
            ],
            'system_settings.update' => [
                'group_name' => 'هوية المدرسة والإعدادات',
                'display_name' => 'تعديل إعدادات النظام',
                'description' => 'يسمح بتعديل الإعدادات العامة للنظام.',
            ],

            'academic_years.view' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'عرض السنوات الدراسية',
                'description' => 'يسمح بمشاهدة السنوات الدراسية المعتمدة في المدرسة.',
            ],
            'academic_years.create' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'إضافة سنوات دراسية',
                'description' => 'يسمح بإنشاء سنة دراسية جديدة.',
            ],
            'academic_years.update' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'تعديل السنوات الدراسية',
                'description' => 'يسمح بتعديل بيانات السنوات الدراسية وحالتها.',
            ],
            'academic_terms.view' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'عرض الفصول الدراسية',
                'description' => 'يسمح بمشاهدة الفصول أو الترمات المرتبطة بالسنة الدراسية.',
            ],
            'academic_terms.create' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'إضافة فصول دراسية',
                'description' => 'يسمح بإنشاء فصل دراسي جديد.',
            ],
            'academic_terms.update' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'تعديل الفصول الدراسية',
                'description' => 'يسمح بتعديل بيانات الفصول الدراسية.',
            ],
            'educational_stages.view' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'عرض المراحل التعليمية',
                'description' => 'يسمح بمشاهدة المراحل التعليمية مثل الابتدائي والإعدادي والثانوي.',
            ],
            'educational_stages.create' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'إضافة مراحل تعليمية',
                'description' => 'يسمح بإنشاء مرحلة تعليمية جديدة.',
            ],
            'educational_stages.update' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'تعديل المراحل التعليمية',
                'description' => 'يسمح بتعديل بيانات المراحل التعليمية.',
            ],
            'grades.view' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'عرض الصفوف',
                'description' => 'يسمح بمشاهدة الصفوف الدراسية التابعة للمراحل التعليمية.',
            ],
            'grades.create' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'إضافة صفوف',
                'description' => 'يسمح بإنشاء صف دراسي جديد.',
            ],
            'grades.update' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'تعديل الصفوف',
                'description' => 'يسمح بتعديل بيانات الصفوف الدراسية.',
            ],
            'classrooms.view' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'عرض القاعات',
                'description' => 'يسمح بمشاهدة القاعات أو الغرف الصفية.',
            ],
            'classrooms.create' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'إضافة قاعات',
                'description' => 'يسمح بإنشاء قاعة أو غرفة صفية جديدة.',
            ],
            'classrooms.update' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'تعديل القاعات',
                'description' => 'يسمح بتعديل بيانات القاعات والغرف الصفية.',
            ],
            'sections.view' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'عرض الشعب',
                'description' => 'يسمح بمشاهدة الشعب الدراسية المرتبطة بالصفوف.',
            ],
            'sections.create' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'إضافة شعب',
                'description' => 'يسمح بإنشاء شعبة دراسية جديدة.',
            ],
            'sections.update' => [
                'group_name' => 'الهيكل الأكاديمي',
                'display_name' => 'تعديل الشعب',
                'description' => 'يسمح بتعديل بيانات الشعب الدراسية.',
            ],

            'students.view' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'عرض الطلاب',
                'description' => 'يسمح بمشاهدة بيانات الطلاب وملفاتهم الأساسية.',
            ],
            'students.create' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'إضافة طلاب',
                'description' => 'يسمح بإنشاء ملفات طلاب جديدة.',
            ],
            'students.update' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'تعديل الطلاب',
                'description' => 'يسمح بتعديل بيانات الطلاب المسجلة.',
            ],
            'students.export' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'تصدير الطلاب',
                'description' => 'يسمح بتصدير بيانات الطلاب للتقارير أو ملفات Excel.',
            ],
            'guardians.view' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'عرض أولياء الأمور',
                'description' => 'يسمح بمشاهدة بيانات أولياء الأمور المرتبطين بالطلاب.',
            ],
            'guardians.create' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'إضافة أولياء أمور',
                'description' => 'يسمح بإنشاء ملفات أولياء أمور جديدة.',
            ],
            'guardians.update' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'تعديل أولياء الأمور',
                'description' => 'يسمح بتعديل بيانات أولياء الأمور.',
            ],
            'teachers.view' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'عرض المعلمين',
                'description' => 'يسمح بمشاهدة بيانات المعلمين.',
            ],
            'teachers.create' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'إضافة معلمين',
                'description' => 'يسمح بإضافة ملفات معلمين جديدة.',
            ],
            'teachers.update' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'تعديل المعلمين',
                'description' => 'يسمح بتعديل بيانات المعلمين.',
            ],
            'employees.view' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'عرض الموظفين',
                'description' => 'يسمح بمشاهدة بيانات الموظفين الإداريين.',
            ],
            'employees.create' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'إضافة موظفين',
                'description' => 'يسمح بإضافة موظفين إداريين جدد.',
            ],
            'employees.update' => [
                'group_name' => 'الأشخاص',
                'display_name' => 'تعديل الموظفين',
                'description' => 'يسمح بتعديل بيانات الموظفين الإداريين.',
            ],

            'enrollments.view' => [
                'group_name' => 'التسجيل والانتساب',
                'display_name' => 'عرض التسجيلات',
                'description' => 'يسمح بمشاهدة سجلات تسجيل الطلاب في الصفوف والشعب.',
            ],
            'enrollments.create' => [
                'group_name' => 'التسجيل والانتساب',
                'display_name' => 'إضافة تسجيلات',
                'description' => 'يسمح بتسجيل طالب ضمن سنة وصف وشعبة.',
            ],
            'enrollments.update' => [
                'group_name' => 'التسجيل والانتساب',
                'display_name' => 'تعديل التسجيلات',
                'description' => 'يسمح بتعديل حالة أو بيانات تسجيل الطالب.',
            ],

            'attendance.view' => [
                'group_name' => 'الحضور والدوام',
                'display_name' => 'عرض الحضور',
                'description' => 'يسمح بمشاهدة سجلات حضور وغياب الطلاب.',
            ],
            'attendance.create' => [
                'group_name' => 'الحضور والدوام',
                'display_name' => 'تسجيل الحضور',
                'description' => 'يسمح بإدخال سجلات الحضور والغياب.',
            ],
            'attendance.update' => [
                'group_name' => 'الحضور والدوام',
                'display_name' => 'تعديل الحضور',
                'description' => 'يسمح بتعديل سجلات الحضور والغياب.',
            ],
            'attendance.reports' => [
                'group_name' => 'الحضور والدوام',
                'display_name' => 'تقارير الحضور',
                'description' => 'يسمح بعرض تقارير الحضور والغياب.',
            ],

            'subjects.view' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'عرض المواد',
                'description' => 'يسمح بمشاهدة المواد الدراسية.',
            ],
            'subjects.create' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'إضافة مواد',
                'description' => 'يسمح بإضافة مواد دراسية جديدة.',
            ],
            'subjects.update' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'تعديل المواد',
                'description' => 'يسمح بتعديل بيانات المواد الدراسية.',
            ],
            'exams.view' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'عرض الاختبارات',
                'description' => 'يسمح بمشاهدة الاختبارات والامتحانات.',
            ],
            'exams.create' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'إضافة اختبارات',
                'description' => 'يسمح بإنشاء اختبار أو امتحان جديد.',
            ],
            'exams.update' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'تعديل الاختبارات',
                'description' => 'يسمح بتعديل بيانات الاختبارات والامتحانات.',
            ],
            'marks.view' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'عرض الدرجات',
                'description' => 'يسمح بمشاهدة درجات الطلاب.',
            ],
            'marks.create' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'إدخال الدرجات',
                'description' => 'يسمح بإدخال درجات الطلاب.',
            ],
            'marks.update' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'تعديل الدرجات',
                'description' => 'يسمح بتعديل درجات الطلاب.',
            ],
            'marks.reports' => [
                'group_name' => 'المواد والاختبارات والدرجات',
                'display_name' => 'تقارير الدرجات',
                'description' => 'يسمح بعرض تقارير الدرجات والنتائج.',
            ],

            'fees.view' => [
                'group_name' => 'المالية والرسوم',
                'display_name' => 'عرض الرسوم',
                'description' => 'يسمح بمشاهدة الرسوم المالية المترتبة على الطلاب.',
            ],
            'fees.create' => [
                'group_name' => 'المالية والرسوم',
                'display_name' => 'إضافة رسوم',
                'description' => 'يسمح بإضافة رسوم أو بنود مالية جديدة.',
            ],
            'fees.update' => [
                'group_name' => 'المالية والرسوم',
                'display_name' => 'تعديل الرسوم',
                'description' => 'يسمح بتعديل الرسوم والبنود المالية.',
            ],
            'fees.payments' => [
                'group_name' => 'المالية والرسوم',
                'display_name' => 'تسجيل الدفعات',
                'description' => 'يسمح بتسجيل دفعات الطلاب وتحديث حالة السداد.',
            ],
            'fees.reports' => [
                'group_name' => 'المالية والرسوم',
                'display_name' => 'تقارير الرسوم',
                'description' => 'يسمح بعرض تقارير الرسوم والتحصيل المالي.',
            ],

            'transport.view' => [
                'group_name' => 'النقل المدرسي',
                'display_name' => 'عرض النقل',
                'description' => 'يسمح بمشاهدة بيانات النقل المدرسي والمسارات.',
            ],
            'transport.create' => [
                'group_name' => 'النقل المدرسي',
                'display_name' => 'إضافة بيانات نقل',
                'description' => 'يسمح بإضافة مسارات أو بيانات نقل جديدة.',
            ],
            'transport.update' => [
                'group_name' => 'النقل المدرسي',
                'display_name' => 'تعديل بيانات النقل',
                'description' => 'يسمح بتعديل بيانات النقل المدرسي.',
            ],

            'documents.view' => [
                'group_name' => 'المستندات والتقارير',
                'display_name' => 'عرض المستندات',
                'description' => 'يسمح بمشاهدة مستندات الطلاب أو المدرسة.',
            ],
            'documents.create' => [
                'group_name' => 'المستندات والتقارير',
                'display_name' => 'إضافة مستندات',
                'description' => 'يسمح بإضافة مستندات جديدة.',
            ],
            'documents.update' => [
                'group_name' => 'المستندات والتقارير',
                'display_name' => 'تعديل المستندات',
                'description' => 'يسمح بتعديل بيانات المستندات.',
            ],
            'reports.view' => [
                'group_name' => 'المستندات والتقارير',
                'display_name' => 'عرض التقارير',
                'description' => 'يسمح بمشاهدة تقارير النظام.',
            ],
            'reports.export' => [
                'group_name' => 'المستندات والتقارير',
                'display_name' => 'تصدير التقارير',
                'description' => 'يسمح بتصدير التقارير وطباعتها.',
            ],

            'teacher_portal.access' => [
                'group_name' => 'البوابات',
                'display_name' => 'دخول بوابة المعلم',
                'description' => 'يسمح للمستخدم بالدخول إلى لوحة أو بوابة المعلم عند إنشائها لاحقًا.',
            ],
            'guardian_portal.access' => [
                'group_name' => 'البوابات',
                'display_name' => 'دخول بوابة ولي الأمر',
                'description' => 'يسمح للمستخدم بالدخول إلى لوحة أو بوابة ولي الأمر عند إنشائها لاحقًا.',
            ],
            'student_portal.access' => [
                'group_name' => 'البوابات',
                'display_name' => 'دخول بوابة الطالب',
                'description' => 'يسمح للمستخدم بالدخول إلى لوحة أو بوابة الطالب عند إنشائها لاحقًا.',
            ],
        ];
    }

    private function roles(): array
    {
        return [
            'super_admin' => ['*'],

            'system_admin' => [
                'admin_panel.access',
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'users.change_password',
                'users.assign_roles',
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.assign_permissions',
                'permissions.view',
                'permissions.create',
                'permissions.update',
                'school_identity.view',
                'school_identity.update',
                'system_settings.view',
                'system_settings.update',
                'reports.view',
                'reports.export',
            ],

            'school_admin' => [
                'admin_panel.access',
                'dashboard.view',
                'school_identity.view',
                'school_identity.update',
                'academic_years.view',
                'academic_years.create',
                'academic_years.update',
                'academic_terms.view',
                'academic_terms.create',
                'academic_terms.update',
                'educational_stages.view',
                'educational_stages.create',
                'educational_stages.update',
                'grades.view',
                'grades.create',
                'grades.update',
                'classrooms.view',
                'classrooms.create',
                'classrooms.update',
                'sections.view',
                'sections.create',
                'sections.update',
                'students.view',
                'students.create',
                'students.update',
                'students.export',
                'guardians.view',
                'guardians.create',
                'guardians.update',
                'teachers.view',
                'teachers.create',
                'teachers.update',
                'employees.view',
                'employees.create',
                'employees.update',
                'enrollments.view',
                'enrollments.create',
                'enrollments.update',
                'attendance.view',
                'attendance.reports',
                'subjects.view',
                'exams.view',
                'marks.view',
                'marks.reports',
                'fees.view',
                'fees.reports',
                'transport.view',
                'documents.view',
                'reports.view',
                'reports.export',
            ],

            'academic_manager' => [
                'admin_panel.access',
                'dashboard.view',
                'academic_years.view',
                'academic_terms.view',
                'educational_stages.view',
                'educational_stages.create',
                'educational_stages.update',
                'grades.view',
                'grades.create',
                'grades.update',
                'classrooms.view',
                'classrooms.create',
                'classrooms.update',
                'sections.view',
                'sections.create',
                'sections.update',
                'students.view',
                'students.update',
                'teachers.view',
                'subjects.view',
                'subjects.create',
                'subjects.update',
                'exams.view',
                'exams.create',
                'exams.update',
                'marks.view',
                'marks.create',
                'marks.update',
                'marks.reports',
                'attendance.view',
                'attendance.reports',
                'reports.view',
            ],

            'registrar' => [
                'admin_panel.access',
                'dashboard.view',
                'academic_years.view',
                'academic_terms.view',
                'educational_stages.view',
                'grades.view',
                'classrooms.view',
                'sections.view',
                'students.view',
                'students.create',
                'students.update',
                'students.export',
                'guardians.view',
                'guardians.create',
                'guardians.update',
                'enrollments.view',
                'enrollments.create',
                'enrollments.update',
                'documents.view',
                'documents.create',
                'documents.update',
            ],

            'accountant' => [
                'admin_panel.access',
                'dashboard.view',
                'students.view',
                'guardians.view',
                'fees.view',
                'fees.create',
                'fees.update',
                'fees.payments',
                'fees.reports',
                'reports.view',
                'reports.export',
            ],

            'teacher' => [
                'dashboard.view',
                'teacher_portal.access',
                'students.view',
                'attendance.view',
                'attendance.create',
                'attendance.update',
                'subjects.view',
                'exams.view',
                'marks.view',
                'marks.create',
                'marks.update',
            ],

            'guardian' => [
                'guardian_portal.access',
                'students.view',
                'attendance.view',
                'marks.view',
                'fees.view',
                'documents.view',
            ],

            'student' => [
                'student_portal.access',
                'attendance.view',
                'marks.view',
                'documents.view',
            ],

            'limited_admin' => [
                'admin_panel.access',
                'dashboard.view',
                'users.view',
            ],
        ];
    }

    private function seedUsers(): void
    {
        $this->ensureUser(
            email: env('SEED_SUPER_ADMIN_EMAIL', 'admin@school-erp.local'),
            name: 'Super Admin',
            role: 'super_admin',
            resetPassword: false,
        );

        $this->ensureUser('system.admin@school-erp.local', 'System Admin', 'system_admin');
        $this->ensureUser('school.admin@school-erp.local', 'School Admin', 'school_admin');
        $this->ensureUser('academic.manager@school-erp.local', 'Academic Manager', 'academic_manager');
        $this->ensureUser('registrar@school-erp.local', 'Registrar Officer', 'registrar');
        $this->ensureUser('accountant@school-erp.local', 'Accountant', 'accountant');
        $this->ensureUser('teacher@school-erp.local', 'Teacher User', 'teacher');
        $this->ensureUser('guardian@school-erp.local', 'Guardian User', 'guardian');
        $this->ensureUser('student@school-erp.local', 'Student User', 'student');
        $this->ensureUser('limited@school-erp.local', 'Limited Admin', 'limited_admin');
    }

    private function ensureUser(string $email, string $name, string $role, bool $resetPassword = true): void
    {
        $user = User::firstOrNew([
            'email' => $email,
        ]);

        $user->name = $name;

        if (! $user->exists || $resetPassword) {
            $user->password = Hash::make($this->defaultPassword);
        }

        if (Schema::hasColumn('users', 'email_verified_at') && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        $user->syncRoles([$role]);
    }
}
