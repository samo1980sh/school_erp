<?php

declare(strict_types=1);

namespace App\Filament\Resources\Classrooms\Pages;

use App\Filament\Resources\Classrooms\ClassroomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageClassrooms extends ManageRecords
{
    protected static string $resource = ClassroomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create classroom' : 'إضافة قاعة')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => ClassroomResource::canCreate())
                ->successNotificationTitle(app()->getLocale() === 'en'
                    ? 'Classroom created successfully'
                    : 'تم إنشاء القاعة بنجاح'),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Classrooms' : 'القاعات الدراسية';
    }
}
