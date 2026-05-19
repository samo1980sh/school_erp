# SCHOOL-ATTENDANCE-1 — Student Attendance Package

This package adds the student attendance foundation to the school ERP.

## Includes

- StudentAttendance model
- student_attendances migration
- StudentAttendanceSeeder with demo attendance records
- Filament StudentAttendanceResource
- Excel template download
- Excel import
- Excel export
- Permission and duplicate check tool
- User guide Word document

## Required existing modules

Run this package after:

- School identity
- Academic years and terms
- Educational stages and grades
- Classrooms and sections
- Students
- Student enrollments
- maatwebsite/excel installed

## Install

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=StudentAttendanceSeeder
php artisan optimize:clear
```

## Check

```powershell
php tools/check-school-attendance-1.php
```

The report should show:

- permissions.missing = []
- permissions.duplicates = []
- roles.duplicates = []
- non_web_guard_count = 0
- duplicate_student_date = []
- orphan_attendance values all 0
- attendance.total >= 50
- excel.maatwebsite_excel_installed = true

## Filament

Open:

```text
/admin/student-attendances
```

Expected navigation:

```text
الحضور والدوام
- حضور الطلاب
```

Header button colors:

- Add attendance = warning
- Download Excel template = gray
- Import Excel = warning
- Export Excel = success

## Permissions

Existing permissions used:

- attendance.view
- attendance.create
- attendance.update
- attendance.reports

New permissions added safely with updateOrCreate:

- attendance.export
- attendance.import
```
