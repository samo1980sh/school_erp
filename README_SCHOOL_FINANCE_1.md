# SCHOOL-FINANCE-1 - Fees & Payments Package

## محتوى الحزمة

- أنواع الرسوم
- رسوم الطلاب
- مدفوعات الطلاب
- Excel Template / Import / Export
- Seeder تجريبي
- فحص صلاحيات وتكرارات
- توثيق Word

## التركيب

```powershell
cd C:\laragon\www\school_erp
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=FinanceFoundationSeeder
php artisan optimize:clear
php tools/check-school-finance-1.php
```

## الفحص المطلوب

يجب أن تكون:

- permissions.missing = []
- permissions.duplicates = []
- permissions.non_web_guard_count = 0
- duplicate_fee_numbers = []
- duplicate_payment_numbers = []
- duplicate_student_fee_assignments = []
- orphan_fees كلها 0
- orphan_payments كلها 0

## Git checkpoint

```powershell
git status

git add app/Models/FeeType.php app/Models/StudentFee.php app/Models/StudentPayment.php
git add app/Filament/Resources/FeeTypes app/Filament/Resources/StudentFees app/Filament/Resources/StudentPayments
git add app/Exports/FeeTypesExport.php app/Exports/FeeTypesTemplateExport.php app/Exports/StudentFeesExport.php app/Exports/StudentFeesTemplateExport.php app/Exports/StudentPaymentsExport.php app/Exports/StudentPaymentsTemplateExport.php
git add app/Imports/FeeTypesImport.php app/Imports/StudentFeesImport.php app/Imports/StudentPaymentsImport.php
git add database/migrations/2026_05_19_200000_create_fee_types_table.php database/migrations/2026_05_19_200100_create_student_fees_table.php database/migrations/2026_05_19_200200_create_student_payments_table.php
git add database/seeders/FinanceFoundationSeeder.php database/seeders/DatabaseSeeder.php
git add tools/check-school-finance-1.php
git add docs/finance/fees-payments-user-guide.docx
git add README_SCHOOL_FINANCE_1.md

git commit -m "Add fees and payments foundation with Excel import export"
git push origin main
git status
```
