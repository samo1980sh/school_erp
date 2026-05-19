<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StudentAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedPermissions();

        if (! Schema::hasTable('student_enrollments') || ! Schema::hasTable('student_attendances')) {
            return;
        }

        $enrollments = StudentEnrollment::query()
            ->with(['student:id,student_number,first_name,last_name', 'academicYear:id,name', 'academicTerm:id,name', 'grade:id,name', 'section:id,name'])
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        if ($enrollments->isEmpty()) {
            return;
        }

        $dates = collect(range(0, 9))
            ->map(fn (int $offset): CarbonImmutable => CarbonImmutable::today()->subDays($offset))
            ->filter(fn (CarbonImmutable $date): bool => ! in_array($date->dayOfWeekIso, [5, 6], true))
            ->take(6)
            ->values();

        $statuses = ['present', 'present', 'present', 'present', 'late', 'absent', 'excused'];

        foreach ($dates as $dateIndex => $date) {
            foreach ($enrollments as $index => $enrollment) {
                $status = $statuses[($index + $dateIndex) % count($statuses)];
                $minutesLate = $status === 'late' ? (($index % 4) + 1) * 5 : 0;

                StudentAttendance::query()->updateOrCreate(
                    [
                        'student_id' => $enrollment->student_id,
                        'attendance_date' => $date->toDateString(),
                    ],
                    [
                        'student_enrollment_id' => $enrollment->id,
                        'academic_year_id' => $enrollment->academic_year_id,
                        'academic_term_id' => $enrollment->academic_term_id,
                        'grade_id' => $enrollment->grade_id,
                        'section_id' => $enrollment->section_id,
                        'status' => $status,
                        'arrival_time' => in_array($status, ['present', 'late'], true) ? ($status === 'late' ? '08:15:00' : '08:00:00') : null,
                        'departure_time' => in_array($status, ['present', 'late'], true) ? '13:30:00' : null,
                        'minutes_late' => $minutesLate,
                        'excuse_reason' => $status === 'excused' ? 'عذر مقبول من ولي الأمر' : null,
                        'notes' => $status === 'absent' ? 'غياب تجريبي لمراجعة تقارير الحضور.' : null,
                    ]
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedPermissions(): void
    {
        $permissions = [
            [
                'name' => 'attendance.export',
                'group_name' => 'الحضور والدوام',
                'display_name' => 'تصدير الحضور',
                'description' => 'يسمح بتصدير سجلات الحضور والغياب إلى ملف Excel.',
                'sort_order' => 565,
            ],
            [
                'name' => 'attendance.import',
                'group_name' => 'الحضور والدوام',
                'display_name' => 'استيراد الحضور',
                'description' => 'يسمح باستيراد سجلات الحضور والغياب من ملف Excel.',
                'sort_order' => 566,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                $permission + ['guard_name' => 'web']
            );
        }

        $rolePermissions = [
            'super_admin' => ['attendance.export', 'attendance.import'],
            'system_admin' => ['attendance.export', 'attendance.import'],
            'school_admin' => ['attendance.export', 'attendance.import'],
            'academic_manager' => ['attendance.export', 'attendance.import'],
            'registrar' => ['attendance.export', 'attendance.import'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if (! $role instanceof Role) {
                continue;
            }

            $role->givePermissionTo($permissionNames);
        }
    }
}
