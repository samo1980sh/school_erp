<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Pages;

use App\Exports\GuardiansExport;
use App\Exports\GuardiansTemplateExport;
use App\Filament\Resources\Guardians\GuardianResource;
use App\Imports\GuardiansImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ManageGuardians extends ManageRecords
{
    protected static string $resource = GuardianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create guardian' : 'إضافة ولي أمر')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => auth()->user()?->can('guardians.create') ?? false)
                ->successNotificationTitle(app()->getLocale() === 'en' ? 'Guardian created successfully' : 'تم إنشاء ولي الأمر بنجاح'),

            Action::make('downloadTemplate')
                ->label(app()->getLocale() === 'en' ? 'Download template' : 'تنزيل قالب Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('guardians.import') ?? false)
                ->action(fn () => Excel::download(
                    new GuardiansTemplateExport(),
                    'guardians-import-template.xlsx'
                )),

            Action::make('importGuardians')
                ->label(app()->getLocale() === 'en' ? 'Import Excel' : 'استيراد Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->can('guardians.import') ?? false)
                ->slideOver()
                ->modalWidth(Width::FourExtraLarge)
                ->form([
                    FileUpload::make('file')
                        ->label(app()->getLocale() === 'en' ? 'Excel file' : 'ملف Excel')
                        ->disk('local')
                        ->directory('imports/guardians')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->helperText(app()->getLocale() === 'en'
                            ? 'Use the approved template to avoid validation errors. Linked students are entered by student number separated by commas.'
                            : 'استخدم القالب المعتمد لتجنب أخطاء التحقق. يتم إدخال أرقام الطلاب المرتبطين مفصولة بفواصل.'),
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

                    Excel::import(new GuardiansImport(), Storage::disk('local')->path($path));

                    Notification::make()
                        ->title(app()->getLocale() === 'en' ? 'Guardians imported successfully' : 'تم استيراد أولياء الأمور بنجاح')
                        ->success()
                        ->send();
                }),

            Action::make('exportGuardians')
                ->label(app()->getLocale() === 'en' ? 'Export Excel' : 'تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('guardians.export') ?? false)
                ->action(fn () => Excel::download(
                    new GuardiansExport(),
                    'guardians-' . now()->format('Y-m-d') . '.xlsx'
                )),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Guardians' : 'أولياء الأمور';
    }
}
