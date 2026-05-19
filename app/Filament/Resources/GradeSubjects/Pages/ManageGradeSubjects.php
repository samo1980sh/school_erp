<?php

declare(strict_types=1);

namespace App\Filament\Resources\GradeSubjects\Pages;

use App\Filament\Resources\GradeSubjects\GradeSubjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageGradeSubjects extends ManageRecords
{
    protected static string $resource = GradeSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create grade subject plan' : 'إضافة خطة مادة لصف')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => auth()->user()?->can('subjects.create') ?? false)
                ->successNotificationTitle(app()->getLocale() === 'en'
                    ? 'Grade subject plan created successfully'
                    : 'تم إنشاء خطة مادة الصف بنجاح'),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Grade subject plans' : 'خطط مواد الصفوف';
    }
}
