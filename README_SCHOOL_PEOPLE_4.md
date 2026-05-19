# SCHOOL-PEOPLE-4 - Employees Foundation Package

This package adds the Employees module to the Laravel 12 + Filament 5 school ERP project.

## Contents

- `employees` table migration
- `Employee` model
- `EmployeeFoundationSeeder`
- Filament Employee Resource and Manage page
- Excel template download, import, and export
- Permission / duplicate / guard check tool
- Word user guide

## Permissions

The package uses these permissions:

- `employees.view`
- `employees.create`
- `employees.update`
- `employees.export`
- `employees.import`

The seeder uses `updateOrCreate`, so permissions are not duplicated.

## Installation

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=EmployeeFoundationSeeder
php artisan optimize:clear
```

## Check

```powershell
php tools/check-school-people-4.php
```

The important values should be:

```json
"missing": [],
"duplicates": [],
"non_web_guard_count": 0,
"duplicate_employee_numbers": [],
"duplicate_national_ids": [],
"maatwebsite_excel_installed": true
```

## Git checkpoint

```powershell
git status

git add app/Models/Employee.php
git add app/Filament/Resources/Employees
git add app/Exports/EmployeesExport.php app/Exports/EmployeesTemplateExport.php
git add app/Imports/EmployeesImport.php
git add database/migrations/2026_05_19_160000_create_employees_table.php
git add database/seeders/EmployeeFoundationSeeder.php database/seeders/DatabaseSeeder.php
git add tools/check-school-people-4.php
git add docs/people/employees-user-guide.docx
git add README_SCHOOL_PEOPLE_4.md

git commit -m "Add employees foundation with Excel import export"
git push origin main

git status
```
