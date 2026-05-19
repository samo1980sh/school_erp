<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sections\Pages;

use App\Filament\Resources\Sections\SectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageSections extends ManageRecords
{
    protected static string $resource = SectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create section' : 'إضافة شعبة')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => SectionResource::canCreate())
                ->successNotificationTitle(app()->getLocale() === 'en'
                    ? 'Section created successfully'
                    : 'تم إنشاء الشعبة بنجاح'),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Sections' : 'الشعب الدراسية';
    }
}
