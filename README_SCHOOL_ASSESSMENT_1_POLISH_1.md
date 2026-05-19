# SCHOOL-ASSESSMENT-1-POLISH-1

## الهدف

هذه الحزمة الصغيرة تعالج ملاحظتين بعد تركيب قسم الاختبارات والدرجات:

1. إضافة Excel إلى قسم الاختبارات:
   - تنزيل قالب Excel
   - استيراد Excel
   - تصدير Excel
2. تقوية البيانات التجريبية لقسم درجات الطلاب بحيث يتم إنشاء درجات كافية بناءً على تسجيلات الطلاب الحالية.

## الملفات المضافة أو المعدلة

- `app/Exports/ExamsExport.php`
- `app/Exports/ExamsTemplateExport.php`
- `app/Imports/ExamsImport.php`
- `app/Filament/Resources/Exams/Pages/ManageExams.php`
- `database/seeders/AssessmentFoundationSeeder.php`
- `tools/check-school-assessment-1.php`

## التركيب

فك ضغط الحزمة داخل جذر المشروع:

```text
C:\laragon\www\school_erp
```

ثم نفّذ:

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan db:seed --class=AssessmentFoundationSeeder
php artisan optimize:clear
php tools/check-school-assessment-1.php
```

## النتيجة المطلوبة من الفحص

```json
"missing": [],
"duplicates": [],
"non_web_guard_count": 0,
"duplicate_codes": [],
"duplicate_exam_student": [],
"student_marks": {
  "total": 50 أو أكثر
}
```

## الواجهة

افتح:

```text
/admin/exams
/admin/student-marks
```

وتأكد أن قسم الاختبارات يحتوي أزرار Excel بنفس ألوان بقية الأقسام:

- إضافة اختبار = أصفر
- تنزيل قالب Excel = رمادي
- استيراد Excel = أصفر
- تصدير Excel = أخضر

## Git

بعد نجاح الفحص:

```powershell
git status

git add app/Exports/ExamsExport.php app/Exports/ExamsTemplateExport.php

git add app/Imports/ExamsImport.php

git add app/Filament/Resources/Exams/Pages/ManageExams.php

git add database/seeders/AssessmentFoundationSeeder.php

git add tools/check-school-assessment-1.php

git add README_SCHOOL_ASSESSMENT_1_POLISH_1.md

git commit -m "Add exams Excel actions and strengthen marks demo data"

git push origin main

git status
```
