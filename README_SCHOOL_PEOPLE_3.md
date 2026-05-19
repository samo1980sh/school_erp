# SCHOOL-PEOPLE-3 — Teachers Foundation Package

## الهدف

إضافة قسم المعلمين إلى نظام school_erp مع دعم Excel للاستيراد والتصدير، وفحص الصلاحيات والتكرارات قبل الاعتماد.

## المتطلبات

- Laravel 12
- Filament 5
- Spatie Laravel Permission
- Maatwebsite Excel مثبت مسبقًا من حزمة الطلاب

## الملفات المضافة

- `app/Models/Teacher.php`
- `app/Filament/Resources/Teachers/TeacherResource.php`
- `app/Filament/Resources/Teachers/Pages/ManageTeachers.php`
- `app/Exports/TeachersExport.php`
- `app/Exports/TeachersTemplateExport.php`
- `app/Imports/TeachersImport.php`
- `database/migrations/2026_05_19_150000_create_teachers_table.php`
- `database/seeders/TeacherFoundationSeeder.php`
- `tools/check-school-people-3.php`
- `docs/people/teachers-user-guide.docx`

## التركيب

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=TeacherFoundationSeeder
php artisan optimize:clear
```

## الفحص

```powershell
php tools/check-school-people-3.php
```

يجب أن تكون النتائج المهمة:

```json
"missing": [],
"duplicates": [],
"non_web_guard_count": 0,
"duplicate_teacher_numbers": [],
"duplicate_national_ids": [],
"maatwebsite_excel_installed": true
```

## واجهة الإدارة

افتح:

```text
/admin/teachers
```

يجب أن يظهر القسم ضمن:

```text
إدارة الأشخاص
- المعلمون
```

## الصلاحيات

تستخدم الحزمة:

- `teachers.view`
- `teachers.create`
- `teachers.update`
- `teachers.export`
- `teachers.import`

يتم إنشاء صلاحيات import/export فقط عند الحاجة باستخدام `updateOrCreate` لتجنب التكرار.

## Git checkpoint

```powershell
git status

git add app/Models/Teacher.php
git add app/Filament/Resources/Teachers
git add app/Exports/TeachersExport.php app/Exports/TeachersTemplateExport.php
git add app/Imports/TeachersImport.php
git add lang/en/rbac_module_permissions.php
git add database/migrations/2026_05_19_150000_create_teachers_table.php
git add database/seeders/TeacherFoundationSeeder.php database/seeders/DatabaseSeeder.php
git add tools/check-school-people-3.php
git add docs/people/teachers-user-guide.docx
git add README_SCHOOL_PEOPLE_3.md

git commit -m "Add teachers foundation with Excel import export"

git push origin main

git status
```
