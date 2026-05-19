<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Exports\StudentsExport;
use App\Exports\StudentsTemplateExport;
use App\Filament\Resources\Students\StudentResource;
use App\Imports\StudentsImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ManageStudents extends ManageRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create student' : 'إضافة طالب')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => auth()->user()?->can('students.create') ?? false)
                ->successNotificationTitle(app()->getLocale() === 'en'
                    ? 'Student created successfully'
                    : 'تم إنشاء الطالب بنجاح'),

            Action::make('downloadTemplate')
                ->label(app()->getLocale() === 'en' ? 'Download template' : 'تنزيل قالب Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('students.import') ?? false)
                ->action(fn () => Excel::download(
                    new StudentsTemplateExport(),
                    'students-import-template.xlsx'
                )),

            Action::make('importStudents')
                ->label(app()->getLocale() === 'en' ? 'Import Excel' : 'استيراد Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->can('students.import') ?? false)
                ->slideOver()
                ->modalWidth(Width::FourExtraLarge)
                ->form([
                    FileUpload::make('file')
                        ->label(app()->getLocale() === 'en' ? 'Excel file' : 'ملف Excel')
                        ->disk('local')
                        ->directory('imports/students')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->helperText(app()->getLocale() === 'en'
                            ? 'Use the approved template to avoid validation errors.'
                            : 'استخدم القالب المعتمد لتجنب أخطاء التحقق.'),
                ])
                ->action(function (array $data): void {
                    $file = $data['file'] ?? null;
                    $path = is_array($file) ? reset($file) : $file;

                    if (! is_string($path) || $path === '') {
                        Notification::make()
                            ->title(app()->getLocale() === 'en' ? 'No file uploaded' : 'لم يتم رفع ملف')
                            ->danger()
                            ->send();

                        return;
                    }

                    Excel::import(new StudentsImport(), Storage::disk('local')->path($path));

                    Notification::make()
                        ->title(app()->getLocale() === 'en'
                            ? 'Students imported successfully'
                            : 'تم استيراد الطلاب بنجاح')
                        ->success()
                        ->send();
                }),

            Action::make('exportStudents')
                ->label(app()->getLocale() === 'en' ? 'Export Excel' : 'تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('students.export') ?? false)
                ->action(fn () => Excel::download(
                    new StudentsExport(),
                    'students-' . now()->format('Y-m-d') . '.xlsx'
                )),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Students' : 'الطلاب';
    }
}
