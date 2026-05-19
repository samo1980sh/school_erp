# SCHOOL-REGISTRATION-1: Student Enrollments Package

This package adds the student enrollment foundation for the Laravel 12 + Filament 5 School ERP project.

## Includes

- `student_enrollments` table
- `StudentEnrollment` model
- Filament resource and management page
- Excel template download
- Excel import
- Excel export
- Demo seeder
- Permission and duplicate check tool
- Word user guide

## Permissions

The package uses existing RBAC permissions and adds import/export permissions safely with `updateOrCreate`:

- `enrollments.view`
- `enrollments.create`
- `enrollments.update`
- `enrollments.export`
- `enrollments.import`

All permissions use the `web` guard.

## Install

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=StudentEnrollmentSeeder
php artisan optimize:clear
```

## Check

```powershell
php tools/check-school-registration-1.php
```

The important values must be clean:

- `permissions.missing = []`
- `permissions.duplicates = []`
- `roles.duplicates = []`
- `permissions.non_web_guard_count = 0`
- `roles.non_web_guard_count = 0`
- `student_enrollments.duplicate_enrollment_numbers = []`
- `student_enrollments.duplicate_student_year = []`
- `student_enrollments.orphan_enrollments.* = 0`

## Git

```powershell
git status

git add app/Models/StudentEnrollment.php
git add app/Filament/Resources/StudentEnrollments
git add app/Exports/StudentEnrollmentsExport.php app/Exports/StudentEnrollmentsTemplateExport.php
git add app/Imports/StudentEnrollmentsImport.php
git add database/migrations/2026_05_19_170000_create_student_enrollments_table.php
git add database/seeders/StudentEnrollmentSeeder.php database/seeders/DatabaseSeeder.php
git add tools/check-school-registration-1.php
git add docs/registration/student-enrollments-user-guide.docx
git add README_SCHOOL_REGISTRATION_1.md

git commit -m "Add student enrollments foundation with Excel import export"
git push origin main
git status
```
