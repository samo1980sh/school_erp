<?php

declare(strict_types=1);

namespace App\Filament\Resources\Grades\Pages;

use App\Filament\Resources\Grades\GradeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageGrades extends ManageRecords
{
    protected static string $resource = GradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create grade' : 'إضافة صف')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => auth()->user()?->can('grades.create') ?? false)
                ->successNotificationTitle(app()->getLocale() === 'en'
                    ? 'Grade created successfully'
                    : 'تم إنشاء الصف الدراسي بنجاح'),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en'
            ? 'Grades'
            : 'الصفوف الدراسية';
    }
}
