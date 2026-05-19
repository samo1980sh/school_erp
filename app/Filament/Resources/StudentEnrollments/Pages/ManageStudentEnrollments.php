<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentEnrollments\Pages;

use App\Exports\StudentEnrollmentsExport;
use App\Exports\StudentEnrollmentsTemplateExport;
use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use App\Imports\StudentEnrollmentsImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageStudentEnrollments extends ManageRecords
{
    protected static string $resource = StudentEnrollmentResource::class;

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Student enrollments' : 'تسجيل الطلاب';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Add enrollment' : 'إضافة تسجيل')
                ->color('warning')
                ->slideOver()
                ->modalWidth('7xl')
                ->visible(fn (): bool => auth()->user()?->can('enrollments.create') ?? false),

            Action::make('downloadTemplate')
                ->label(app()->getLocale() === 'en' ? 'Download Excel template' : 'تنزيل قالب Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('enrollments.import') ?? false)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new StudentEnrollmentsTemplateExport(),
                    'student-enrollments-import-template.xlsx'
                )),

            Action::make('importExcel')
                ->label(app()->getLocale() === 'en' ? 'Import Excel' : 'استيراد Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->slideOver()
                ->modalWidth('3xl')
                ->visible(fn (): bool => auth()->user()?->can('enrollments.import') ?? false)
                ->form([
                    FileUpload::make('file')
                        ->label(app()->getLocale() === 'en' ? 'Excel file' : 'ملف Excel')
                        ->disk('local')
                        ->directory('imports/student-enrollments')
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

                    Excel::import(new StudentEnrollmentsImport(), Storage::disk('local')->path($path));

                    Notification::make()
                        ->title(app()->getLocale() === 'en' ? 'Student enrollments imported successfully.' : 'تم استيراد تسجيلات الطلاب بنجاح.')
                        ->success()
                        ->send();
                }),

            Action::make('exportExcel')
                ->label(app()->getLocale() === 'en' ? 'Export Excel' : 'تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('enrollments.export') ?? false)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new StudentEnrollmentsExport(),
                    'student-enrollments-export.xlsx'
                )),
        ];
    }
}
