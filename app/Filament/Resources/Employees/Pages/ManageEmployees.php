<?php

declare(strict_types=1);

namespace App\Filament\Resources\Employees\Pages;

use App\Exports\EmployeesExport;
use App\Exports\EmployeesTemplateExport;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Imports\EmployeesImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageEmployees extends ManageRecords
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Employees' : 'الموظفون';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Add employee' : 'إضافة موظف')
                ->color('warning')
                ->slideOver()
                ->modalWidth('7xl')
                ->visible(fn (): bool => auth()->user()?->can('employees.create') ?? false),

            Action::make('downloadTemplate')
                ->label(app()->getLocale() === 'en' ? 'Download Excel template' : 'تنزيل قالب Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('employees.import') ?? false)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new EmployeesTemplateExport(),
                    'employees-import-template.xlsx'
                )),

            Action::make('importExcel')
                ->label(app()->getLocale() === 'en' ? 'Import Excel' : 'استيراد Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->slideOver()
                ->modalWidth('3xl')
                ->visible(fn (): bool => auth()->user()?->can('employees.import') ?? false)
                ->form([
                    FileUpload::make('file')
                        ->label(app()->getLocale() === 'en' ? 'Excel file' : 'ملف Excel')
                        ->disk('local')
                        ->directory('imports/employees')
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

                    Excel::import(new EmployeesImport(), Storage::disk('local')->path($path));

                    Notification::make()
                        ->title(app()->getLocale() === 'en' ? 'Employees imported successfully.' : 'تم استيراد الموظفين بنجاح.')
                        ->success()
                        ->send();
                }),

            Action::make('exportExcel')
                ->label(app()->getLocale() === 'en' ? 'Export Excel' : 'تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('employees.export') ?? false)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new EmployeesExport(),
                    'employees-export.xlsx'
                )),
        ];
    }
}
