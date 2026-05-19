# SCHOOL-PEOPLE-1 — Students Foundation Package

## الهدف

تأسيس قسم الطلاب ضمن نظام school_erp بعد إغلاق الهيكل الأكاديمي الأساسي.

القسم يحتوي على:

- جدول الطلاب.
- Model كامل مع علاقات السنة الدراسية والصف والشعبة.
- Seeder ببيانات تجريبية واقعية لا تقل عن 50 طالبًا.
- Filament Resource احترافي مع SlideOver مريح.
- Excel template download.
- Excel import.
- Excel export.
- فحص صلاحيات وتكرارات وعلاقات.
- توثيق Word للعميل.

## المتطلب الإضافي

هذه أول حزمة تستخدم Excel فعليًا، لذلك يجب تثبيت Laravel Excel قبل فتح القسم:

```powershell
composer require maatwebsite/excel:^3.1
```

## التركيب

فك ضغط الحزمة داخل جذر المشروع:

```text
C:\laragon\www\school_erp
```

ثم نفّذ:

```powershell
cd C:\laragon\www\school_erp

composer require maatwebsite/excel:^3.1

php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=StudentFoundationSeeder
php artisan optimize:clear
```

## الفحص

```powershell
php tools/check-school-people-1.php
```

يجب أن تكون القيم المهمة:

```json
"missing": [],
"duplicates": [],
"non_web_guard_count": 0,
"duplicate_student_numbers": [],
"duplicate_national_ids": [],
"orphan_students": {
  "missing_year": 0,
  "missing_grade": 0,
  "missing_section": 0
}
```

ويجب أن يكون:

```text
students.total >= 50
excel.maatwebsite_excel_installed = true
```

## الواجهة

افتح:

```text
/admin/students
```

وتأكد من:

- وجود قسم إدارة الأشخاص.
- ظهور الطلاب.
- إضافة طالب عبر SlideOver.
- تعديل طالب عبر SlideOver.
- تنزيل قالب Excel.
- استيراد Excel.
- تصدير Excel.
- عدم ظهور أزرار Excel لمن لا يملك الصلاحيات.

## الصلاحيات

القسم يستخدم:

- students.view
- students.create
- students.update
- students.export
- students.import

ملاحظة: `students.import` تتم إضافتها عبر StudentFoundationSeeder لأنها صلاحية جديدة مرتبطة بدعم Excel.

## Git checkpoint

بعد نجاح الفحص:

```powershell
git status

git add app/Models/Student.php

git add app/Filament/Resources/Students

git add app/Exports/StudentsExport.php app/Exports/StudentsTemplateExport.php

git add app/Imports/StudentsImport.php

git add database/migrations/2026_05_19_130000_create_students_table.php

git add database/seeders/StudentFoundationSeeder.php database/seeders/DatabaseSeeder.php

git add tools/check-school-people-1.php

git add docs/people/students-user-guide.docx

git add README_SCHOOL_PEOPLE_1.md

git commit -m "Add students foundation with Excel import export"

git push origin main

git status
```
