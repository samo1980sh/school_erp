# SCHOOL-ACADEMIC-1 — Subjects & Grade Subject Plans Package

هذه الحزمة تضيف قسم المواد الدراسية وخطط مواد الصفوف إلى مشروع `school_erp`.

## المحتوى

- `app/Models/Subject.php`
- `app/Models/GradeSubject.php`
- `database/migrations/2026_05_19_120000_create_subjects_table.php`
- `database/migrations/2026_05_19_120100_create_grade_subjects_table.php`
- `database/seeders/SubjectCurriculumSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `app/Filament/Resources/Subjects`
- `app/Filament/Resources/GradeSubjects`
- `tools/check-school-academic-1.php`
- `docs/academic-structure/subjects-curriculum-user-guide.docx`

## الصلاحيات المستخدمة

لا تضيف الحزمة صلاحيات جديدة. تستخدم صلاحيات موجودة مسبقًا ضمن RBAC:

- `subjects.view`
- `subjects.create`
- `subjects.update`

## التركيب

فك ضغط الحزمة داخل جذر المشروع:

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=SubjectCurriculumSeeder
php artisan optimize:clear
```

## الفحص

```powershell
php tools/check-school-academic-1.php
```

يجب أن تكون النتائج المهمة:

```json
"missing": [],
"duplicates": [],
"non_web_guard_count": 0,
"duplicate_codes": [],
"duplicate_assignments": [],
"orphan_grade_subjects": {
  "missing_year": 0,
  "missing_grade": 0,
  "missing_subject": 0
}
```

كما يجب أن يكون عدد المواد 15 أو أكثر، وعدد خطط مواد الصفوف 50 أو أكثر بعد تشغيل السيدر.

## الواجهة

افتح:

- `/admin/subjects`
- `/admin/grade-subjects`

وتأكد من ظهور القسم ضمن:

- `الهيكل الأكاديمي`
- `Academic Settings`

## Git checkpoint

```powershell
git status

git add app/Models/Subject.php app/Models/GradeSubject.php
git add app/Filament/Resources/Subjects app/Filament/Resources/GradeSubjects
git add database/migrations/2026_05_19_120000_create_subjects_table.php database/migrations/2026_05_19_120100_create_grade_subjects_table.php
git add database/seeders/SubjectCurriculumSeeder.php database/seeders/DatabaseSeeder.php
git add tools/check-school-academic-1.php
git add docs/academic-structure/subjects-curriculum-user-guide.docx
git add README_SCHOOL_ACADEMIC_1.md

git commit -m "Add subjects and grade subject plans foundation"
git push origin main
git status
```

## ملاحظة Excel

لم تتم إضافة Excel Import/Export في هذه الحزمة لأنها حزمة إعداد أكاديمي متوسط الحجم وليست بيانات تشغيل يومية ضخمة. سيتم اعتماد Excel Import/Export للأقسام الكبيرة مثل الطلاب، أولياء الأمور، المعلمين، التسجيل، الحضور، الدرجات، والرسوم عند بنائها.
