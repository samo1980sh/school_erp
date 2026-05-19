<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageSubjects extends ManageRecords
{
    protected static string $resource = SubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create subject' : 'إضافة مادة دراسية')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => auth()->user()?->can('subjects.create') ?? false)
                ->successNotificationTitle(app()->getLocale() === 'en'
                    ? 'Subject created successfully'
                    : 'تم إنشاء المادة الدراسية بنجاح'),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Subjects' : 'المواد الدراسية';
    }
}
