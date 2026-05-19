<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcademicTerms\Pages;

use App\Filament\Resources\AcademicTerms\AcademicTermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageAcademicTerms extends ManageRecords
{
    protected static string $resource = AcademicTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create academic term' : 'إضافة فصل دراسي')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => AcademicTermResource::canCreate())
                ->successNotificationTitle(app()->getLocale() === 'en'
                    ? 'Academic term created successfully'
                    : 'تم إنشاء الفصل الدراسي بنجاح'),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en'
            ? 'Academic terms'
            : 'الفصول الدراسية';
    }
}
