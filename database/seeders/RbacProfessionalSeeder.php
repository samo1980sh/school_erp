<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
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

        $permissions = $this->permissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $this->guardName,
            ]);
        }

        foreach ($this->roles() as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $this->guardName,
            ]);

            $role->syncPermissions($rolePermissions === ['*'] ? $permissions : $rolePermissions);
        }

        $this->seedUsers();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissions(): array
    {
        return [
            // Dashboard
            'dashboard.view',

            // RBAC
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

            // School identity / settings
            'school_identity.view',
            'school_identity.update',
            'system_settings.view',
            'system_settings.update',

            // Academic foundation
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

            // People
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

            // Enrollment
            'enrollments.view',
            'enrollments.create',
            'enrollments.update',

            // Attendance
            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.reports',

            // Exams and grades
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

            // Finance
            'fees.view',
            'fees.create',
            'fees.update',
            'fees.payments',
            'fees.reports',

            // Transport
            'transport.view',
            'transport.create',
            'transport.update',

            // Documents and reports
            'documents.view',
            'documents.create',
            'documents.update',

            'reports.view',
            'reports.export',

            // Portals
            'teacher_portal.access',
            'guardian_portal.access',
            'student_portal.access',
        ];
    }

    private function roles(): array
    {
        return [
            'super_admin' => ['*'],

            'system_admin' => [
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

        if (array_key_exists('email_verified_at', $user->getAttributes()) || in_array('email_verified_at', $user->getFillable(), true)) {
            $user->email_verified_at = $user->email_verified_at ?? Carbon::now();
        }

        $user->save();

        $user->syncRoles([$role]);
    }
}
