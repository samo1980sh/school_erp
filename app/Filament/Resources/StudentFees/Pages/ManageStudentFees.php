<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentFees\Pages;

use App\Exports\StudentFeesExport;
use App\Exports\StudentFeesTemplateExport;
use App\Filament\Resources\StudentFees\StudentFeeResource;
use App\Filament\Resources\StudentFinancialBalances\StudentFinancialBalanceResource;
use App\Imports\StudentFeesImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageStudentFees extends ManageRecords
{
    protected static string $resource = StudentFeeResource::class;

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Fee Details' : 'تفاصيل الرسوم';
    }

    public function getSubheading(): ?string
    {
        return app()->getLocale() === 'en'
            ? 'Audit log for student financial charges. Student Balances remains the daily finance workspace.'
            : 'سجل تفصيلي للمطالبات المالية على الطلاب. تبقى أرصدة الطلاب شاشة العمل اليومية للمتابعة والتقارير.';
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
                ->label(app()->getLocale() === 'en' ? 'Add fee detail' : 'إضافة تفصيل رسم')
                ->color('warning')
                ->slideOver()
                ->modalWidth('7xl')
                ->visible(fn (): bool => auth()->user()?->can('fees.create') ?? false),

            Action::make('downloadTemplate')
                ->label(app()->getLocale() === 'en' ? 'Download Excel template' : 'تنزيل قالب Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('fees.import') ?? false)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new StudentFeesTemplateExport(),
                    'student-fees-import-template.xlsx'
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
                        ->directory('imports/student-fees')
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

                    Excel::import(new StudentFeesImport(), Storage::disk('local')->path($path));

                    Notification::make()
                        ->title(app()->getLocale() === 'en' ? 'Fee details imported successfully.' : 'تم استيراد تفاصيل الرسوم بنجاح.')
                        ->success()
                        ->send();
                }),

            Action::make('exportExcel')
                ->label(app()->getLocale() === 'en' ? 'Export Excel' : 'تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('fees.export') ?? false)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new StudentFeesExport(),
                    'student-fee-details-export.xlsx'
                )),
        ];
    }
}
