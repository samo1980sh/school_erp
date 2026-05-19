# SCHOOL-CORE-3 - Educational Stages & Grades Package

## الهدف

هذه الحزمة تضيف التأسيس الأكاديمي الثاني لنظام school_erp:

- المراحل التعليمية
- الصفوف الدراسية
- Seed data واقعية
- واجهات Filament احترافية
- فحص صلاحيات وتكرارات وارتباطات
- توثيق Word للعميل

## الصلاحيات المطلوبة

الحزمة لا تنشئ صلاحيات جديدة. تعتمد على الصلاحيات الموجودة مسبقًا في RBAC:

```text
educational_stages.view
educational_stages.create
educational_stages.update
grades.view
grades.create
grades.update
```

## Excel support

لم يتم تضمين Excel في هذه الحزمة لأن المراحل التعليمية والصفوف الدراسية بيانات مرجعية محدودة العدد. سيتم تضمين Excel فقط في الأقسام الكبيرة أو ذات الإدخال الكثيف مثل الطلاب، أولياء الأمور، المعلمين، التسجيل، الحضور، الدرجات، والرسوم.

## خطوات التركيب

فك ضغط الحزمة داخل جذر المشروع:

```text
C:\laragon\www\school_erp
```

ثم نفذ:

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=EducationalStructureSeeder
php artisan optimize:clear
```

## الفحص

```powershell
php tools/check-school-core-3.php
```

يجب أن تكون القيم المهمة:

```json
"missing": [],
"duplicates": [],
"non_web_guard_count": 0,
"duplicate_codes": [],
"orphan_grades": 0,
"educational_stages.total": 4,
"grades.total": 17
```

## فحص الواجهة

افتح:

```text
/admin/educational-stages
/admin/grades
```

وتأكد من:

- ظهور القسم ضمن الإعدادات الأكاديمية.
- المودالات مريحة والـ Sections الرئيسية تحت بعضها.
- الترتيب يظهر أولًا.
- لا توجد أزرار حذف.
- الإضافة والتعديل تظهر حسب الصلاحيات.
- الفلاتر تعمل حسب الحالة والمرحلة.

## Git checkpoint

بعد نجاح الفحص:

```powershell
git status

git add app/Models/EducationalStage.php app/Models/Grade.php
git add app/Filament/Resources/EducationalStages app/Filament/Resources/Grades
git add database/migrations/2026_05_19_100000_create_educational_stages_table.php database/migrations/2026_05_19_100100_create_grades_table.php
git add database/seeders/EducationalStructureSeeder.php database/seeders/DatabaseSeeder.php
git add docs/educational-structure/educational-stages-grades-user-guide.docx
git add tools/check-school-core-3.php
git add README_SCHOOL_CORE_3.md

git commit -m "Add educational stages and grades foundation"

git push origin main

git status
```
