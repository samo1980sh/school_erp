# SCHOOL-ASSESSMENT-1 - Exams & Student Marks

This package adds the assessment foundation for the Laravel 12 + Filament 5 School ERP project.

## Includes

- `exams` table and model.
- `student_marks` table and model.
- Filament resources for exams and student marks.
- Excel template download, import, and export for student marks.
- Demo seed data.
- Permission and duplicate check tool.
- Word user guide.

## Install

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=AssessmentFoundationSeeder
php artisan optimize:clear
php tools/check-school-assessment-1.php
```

## Required permissions

- `exams.view`
- `exams.create`
- `exams.update`
- `marks.view`
- `marks.create`
- `marks.update`
- `marks.reports`
- `marks.export`
- `marks.import`

The seeder uses `updateOrCreate` and always uses the `web` guard.

## Routes to check

- `/admin/exams`
- `/admin/student-marks`

## Git checkpoint

```powershell
git status

git add app/Models/Exam.php app/Models/StudentMark.php
git add app/Filament/Resources/Exams app/Filament/Resources/StudentMarks
git add app/Exports/StudentMarksExport.php app/Exports/StudentMarksTemplateExport.php
git add app/Imports/StudentMarksImport.php
git add database/migrations/2026_05_19_190000_create_exams_table.php database/migrations/2026_05_19_190100_create_student_marks_table.php
git add database/seeders/AssessmentFoundationSeeder.php database/seeders/DatabaseSeeder.php
git add tools/check-school-assessment-1.php
git add docs/assessment/exams-marks-user-guide.docx
git add README_SCHOOL_ASSESSMENT_1.md

git commit -m "Add exams and student marks foundation"
git push origin main
git status
```
