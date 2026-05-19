<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeTypes\Pages;

use App\Exports\FeeTypesExport;
use App\Exports\FeeTypesTemplateExport;
use App\Filament\Resources\FeeTypes\FeeTypeResource;
use App\Imports\FeeTypesImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageFeeTypes extends ManageRecords
{
    protected static string $resource = FeeTypeResource::class;

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Fee types' : 'أنواع الرسوم';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(app()->getLocale() === 'en' ? 'Add fee type' : 'إضافة نوع رسم')->color('warning')->slideOver()->modalWidth('7xl')->visible(fn (): bool => auth()->user()?->can('fees.create') ?? false),
            Action::make('downloadTemplate')->label(app()->getLocale() === 'en' ? 'Download Excel template' : 'تنزيل قالب Excel')->icon('heroicon-o-document-arrow-down')->color('gray')->visible(fn (): bool => auth()->user()?->can('fees.import') ?? false)->action(fn (): BinaryFileResponse => Excel::download(new FeeTypesTemplateExport(), 'fee-types-import-template.xlsx')),
            Action::make('importExcel')->label(app()->getLocale() === 'en' ? 'Import Excel' : 'استيراد Excel')->icon('heroicon-o-arrow-up-tray')->color('warning')->slideOver()->modalWidth('3xl')->visible(fn (): bool => auth()->user()?->can('fees.import') ?? false)->form([
                FileUpload::make('file')->label(app()->getLocale() === 'en' ? 'Excel file' : 'ملف Excel')->disk('local')->directory('imports/fee-types')->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-excel','text/csv'])->required(),
            ])->action(function (array $data): void {
                $path = (string) ($data['file'] ?? '');
                if ($path === '' || ! Storage::disk('local')->exists($path)) {
                    Notification::make()->title(app()->getLocale() === 'en' ? 'Import file was not found.' : 'لم يتم العثور على ملف الاستيراد.')->danger()->send();
                    return;
                }
                Excel::import(new FeeTypesImport(), Storage::disk('local')->path($path));
                Notification::make()->title(app()->getLocale() === 'en' ? 'Fee types imported successfully.' : 'تم استيراد أنواع الرسوم بنجاح.')->success()->send();
            }),
            Action::make('exportExcel')->label(app()->getLocale() === 'en' ? 'Export Excel' : 'تصدير Excel')->icon('heroicon-o-arrow-down-tray')->color('success')->visible(fn (): bool => auth()->user()?->can('fees.export') ?? false)->action(fn (): BinaryFileResponse => Excel::download(new FeeTypesExport(), 'fee-types-export.xlsx')),
        ];
    }
}
