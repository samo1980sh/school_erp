# SCHOOL-CORE-2: Academic Years & Terms Package

This package adds the academic foundation for the School ERP project:

- Academic Years
- Academic Terms
- Filament resources and pages
- Demo seed data
- Permission checks
- Documentation file

## Important decisions

- Laravel 12 + Filament 5.
- Spatie Laravel Permission only.
- No Shield.
- No multiple guards.
- All permissions use the `web` guard.
- Delete actions are disabled in this foundation stage.

## Required permissions

The package expects these permissions to already exist from `RbacProfessionalSeeder`:

- `academic_years.view`
- `academic_years.create`
- `academic_years.update`
- `academic_terms.view`
- `academic_terms.create`
- `academic_terms.update`

Each Filament Resource checks its own permissions through `canViewAny`, `canCreate`, and `canEdit`.

## Installation

Extract this package into the project root:

```powershell
cd C:\laragon\www\school_erp
```

Then run:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=AcademicFoundationSeeder
php artisan optimize:clear
```

## Local verification

Copy the check file to the project root if needed, or run it from the extracted location after paths are correct:

```powershell
php tools/check-school-core-2.php
```

Expected summary:

- `permissions.missing` should be empty.
- `academic_years.total` should be 17.
- `academic_terms.total` should be 51.
- `current_academic_years` should be 1.
- `current_terms_in_current_year` should be 1 or 0 depending on today's date and seeded calendar range.

## UI checks

Open:

```text
/admin/academic-years
/admin/academic-terms
```

Check:

- The navigation group is `الإعدادات الأكاديمية / Academic Setup`.
- Create/Edit opens in a wide SlideOver.
- `sort_order` is the first field in the forms.
- Filters work by status/current/year.
- Arabic and English UI labels work.
- Users without the required permissions cannot see or modify the sections.

## Git checkpoint

```powershell
git status
git add app/Models/AcademicYear.php app/Models/AcademicTerm.php app/Filament/Resources/AcademicYears app/Filament/Resources/AcademicTerms database/migrations/2026_05_19_090000_create_academic_years_table.php database/migrations/2026_05_19_090100_create_academic_terms_table.php database/seeders/AcademicFoundationSeeder.php database/seeders/DatabaseSeeder.php docs/academic-foundation/academic-years-terms-user-guide.docx
git commit -m "Add academic years and terms foundation"
git push origin main
git status
```
