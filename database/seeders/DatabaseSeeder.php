<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Foundation:
     * - Spatie Laravel Permission only.
     * - No Shield.
     * - No multiple guards.
     * - All roles and permissions use the web guard.
     */
    public function run(): void
    {
        $this->call([
            RbacProfessionalSeeder::class,
            RoleMetadataSeeder::class,
            SchoolSettingSeeder::class,
            AcademicFoundationSeeder::class,
            EducationalStructureSeeder::class,
            ClassroomSectionSeeder::class,
            SubjectCurriculumSeeder::class,
            StudentFoundationSeeder::class,
            GuardianFoundationSeeder::class,
            TeacherFoundationSeeder::class,
            EmployeeFoundationSeeder::class,
            StudentEnrollmentSeeder::class,
            StudentAttendanceSeeder::class,
            AssessmentFoundationSeeder::class,
        ]);
    }
}
