# SCHOOL-FINANCE-1-POLISH-1

## Student Balances and Finance Navigation Polish

This package improves the finance module without changing the accounting data model.

## What it adds

- New read-only `Student Financial Balances` view.
- New Filament screen: `/admin/student-financial-balances`.
- Excel export for student balances.
- A migration that creates a SQL view aggregating student fee totals.
- A patch tool to rename finance sidebar labels:
  - Student Fees => Fee Details
  - Student Payments => Payment Receipts
- A validation tool for permissions, duplicates, guards, and balance rows.

## Install

Copy the package files into the project root, then run:

```powershell
cd C:\laragon\www\school_erp

php artisan optimize:clear
php artisan migrate
php tools/apply-school-finance-1-polish-1.php
php artisan optimize:clear
php tools/check-school-finance-1-polish-1.php
```

## Expected sidebar

Arabic:

- المالية والرسوم
  - أنواع الرسوم
  - أرصدة الطلاب
  - تفاصيل الرسوم
  - إيصالات الدفع

English:

- Finance & Fees
  - Fee types
  - Student balances
  - Fee details
  - Payment receipts

## Git checkpoint

```powershell
git status

git add app/Models/StudentFinancialBalance.php
git add app/Filament/Resources/StudentFinancialBalances
git add app/Exports/StudentFinancialBalancesExport.php
git add database/migrations/2026_05_19_200300_create_student_financial_balances_view.php
git add tools/apply-school-finance-1-polish-1.php tools/check-school-finance-1-polish-1.php
git add docs/finance/student-balances-user-guide.docx
git add README_SCHOOL_FINANCE_1_POLISH_1.md
git add app/Filament/Resources/FeeTypes app/Filament/Resources/StudentFees app/Filament/Resources/StudentPayments

git commit -m "Add student balances finance view"
git push origin main
git status
```
