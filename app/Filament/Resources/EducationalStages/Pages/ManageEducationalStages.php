<?php

declare(strict_types=1);

namespace App\Filament\Resources\EducationalStages\Pages;

use App\Filament\Resources\EducationalStages\EducationalStageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageEducationalStages extends ManageRecords
{
    protected static string $resource = EducationalStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create stage' : 'إضافة مرحلة')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => auth()->user()?->can('educational_stages.create') ?? false)
                ->successNotificationTitle(app()->getLocale() === 'en'
                    ? 'Educational stage created successfully'
                    : 'تم إنشاء المرحلة التعليمية بنجاح'),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en'
            ? 'Educational stages'
            : 'المراحل التعليمية';
    }
}
