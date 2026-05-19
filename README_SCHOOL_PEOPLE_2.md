# SCHOOL-PEOPLE-2 — Guardians Foundation Package

This package adds the Guardians module to the School ERP project.

## Includes

- Guardian model
- guardians table
- guardian_student pivot table
- GuardianFoundationSeeder
- Filament GuardianResource
- Excel template download
- Excel import
- Excel export
- RBAC permission checks
- English module permission translations support
- Word user guide

## Permissions

The module requires:

- guardians.view
- guardians.create
- guardians.update
- guardians.export
- guardians.import

`GuardianFoundationSeeder` uses `updateOrCreate`, so it does not create duplicate permissions.

## Install

Unzip into the project root:

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=GuardianFoundationSeeder
php artisan optimize:clear
php tools/check-school-people-2.php
```

## Expected check results

- missing permissions: []
- duplicate permissions: []
- duplicate roles: []
- non_web_guard_count: 0
- duplicate guardian numbers: []
- duplicate national IDs: []
- orphan guardian links: 0
- guardians.total >= 50
- guardian_student.total_links >= 50
- Excel classes installed: true

## Git checkpoint

```powershell
git status

git add app/Models/Guardian.php
git add app/Filament/Resources/Guardians
git add app/Exports/GuardiansExport.php app/Exports/GuardiansTemplateExport.php
git add app/Imports/GuardiansImport.php
git add app/Support/Rbac/RbacPermissionMetadata.php
git add lang/en/rbac_module_permissions.php
git add database/migrations/2026_05_19_140000_create_guardians_table.php database/migrations/2026_05_19_140100_create_guardian_student_table.php
git add database/seeders/GuardianFoundationSeeder.php database/seeders/DatabaseSeeder.php
git add tools/check-school-people-2.php
git add docs/people/guardians-user-guide.docx
git add README_SCHOOL_PEOPLE_2.md

git commit -m "Add guardians foundation with Excel import export"
git push origin main
git status
```
