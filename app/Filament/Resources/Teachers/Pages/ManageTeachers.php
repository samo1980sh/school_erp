<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Pages;

use App\Exports\TeachersExport;
use App\Exports\TeachersTemplateExport;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Imports\TeachersImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageTeachers extends ManageRecords
{
    protected static string $resource = TeacherResource::class;

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Teachers' : 'المعلمون';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Add teacher' : 'إضافة معلم')
                ->color('warning')
                ->slideOver()
                ->modalWidth('7xl')
                ->visible(fn(): bool => auth()->user()?->can('teachers.create') ?? false),

            Action::make('downloadTemplate')
                ->label(app()->getLocale() === 'en' ? 'Download Excel template' : 'تنزيل قالب Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn(): bool => auth()->user()?->can('teachers.import') ?? false)
                ->action(fn(): BinaryFileResponse => Excel::download(
                    new TeachersTemplateExport(),
                    'teachers-import-template.xlsx'
                )),

            Action::make('importExcel')
                ->label(app()->getLocale() === 'en' ? 'Import Excel' : 'استيراد Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->slideOver()
                ->modalWidth('3xl')
                ->visible(fn(): bool => auth()->user()?->can('teachers.import') ?? false)
                ->form([
                    FileUpload::make('file')
                        ->label(app()->getLocale() === 'en' ? 'Excel file' : 'ملف Excel')
                        ->disk('local')
                        ->directory('imports/teachers')
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

                    Excel::import(new TeachersImport(), Storage::disk('local')->path($path));

                    Notification::make()
                        ->title(app()->getLocale() === 'en' ? 'Teachers imported successfully.' : 'تم استيراد المعلمين بنجاح.')
                        ->success()
                        ->send();
                }),

            Action::make('exportExcel')
                ->label(app()->getLocale() === 'en' ? 'Export Excel' : 'تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn(): bool => auth()->user()?->can('teachers.export') ?? false)
                ->action(fn(): BinaryFileResponse => Excel::download(
                    new TeachersExport(),
                    'teachers-export.xlsx'
                )),
        ];
    }
}
