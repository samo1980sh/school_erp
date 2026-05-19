<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentPayments\Pages;

use App\Exports\StudentPaymentsExport;
use App\Exports\StudentPaymentsTemplateExport;
use App\Filament\Resources\StudentFinancialBalances\StudentFinancialBalanceResource;
use App\Filament\Resources\StudentPayments\StudentPaymentResource;
use App\Imports\StudentPaymentsImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageStudentPayments extends ManageRecords
{
    protected static string $resource = StudentPaymentResource::class;

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Payment Receipts' : 'إيصالات الدفع';
    }

    public function getSubheading(): ?string
    {
        return app()->getLocale() === 'en'
            ? 'Accounting audit log for actual payments and receipts linked to student fees. Student Balances remains the daily finance workspace.'
            : 'سجل محاسبي للتدقيق في الدفعات والإيصالات المرتبطة برسوم الطلاب. تبقى أرصدة الطلاب شاشة العمل اليومية للمتابعة والتقارير.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openBalances')
                ->label(app()->getLocale() === 'en' ? 'Open Student Balances' : 'فتح أرصدة الطلاب')
                ->icon('heroicon-o-chart-bar-square')
                ->color('primary')
                ->url(fn (): string => StudentFinancialBalanceResource::getUrl('index')),

            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Add receipt' : 'إضافة إيصال')
                ->color('warning')
                ->slideOver()
                ->modalWidth('7xl')
                ->visible(fn (): bool => auth()->user()?->can('fees.payments') ?? false),

            Action::make('downloadTemplate')
                ->label(app()->getLocale() === 'en' ? 'Download Excel template' : 'تنزيل قالب Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('fees.import') ?? false)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new StudentPaymentsTemplateExport(),
                    'student-payment-receipts-import-template.xlsx'
                )),

            Action::make('importExcel')
                ->label(app()->getLocale() === 'en' ? 'Import Excel' : 'استيراد Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->slideOver()
                ->modalWidth('3xl')
                ->visible(fn (): bool => auth()->user()?->can('fees.import') ?? false)
                ->form([
                    FileUpload::make('file')
                        ->label(app()->getLocale() === 'en' ? 'Excel file' : 'ملف Excel')
                        ->disk('local')
                        ->directory('imports/student-payments')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = (string) ($data['file'] ?? '');

                    if ($path === '' || ! Storage::disk('local')->exists($path)) {
                        Notification::make()
                            ->title(app()->getLocale() === 'en' ? 'Import file was not found.' : 'لم يتم العثور على ملف الاستيراد.')
                            ->danger()
                            ->send();

                        return;
                    }

                    Excel::import(new StudentPaymentsImport(), Storage::disk('local')->path($path));

                    Notification::make()
                        ->title(app()->getLocale() === 'en' ? 'Payment receipts imported successfully.' : 'تم استيراد إيصالات الدفع بنجاح.')
                        ->success()
                        ->send();
                }),

            Action::make('exportExcel')
                ->label(app()->getLocale() === 'en' ? 'Export Excel' : 'تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('fees.export') ?? false)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new StudentPaymentsExport(),
                    'student-payment-receipts-export.xlsx'
                )),
        ];
    }
}
