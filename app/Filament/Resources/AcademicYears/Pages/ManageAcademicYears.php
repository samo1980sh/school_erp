<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageAcademicYears extends ManageRecords
{
    protected static string $resource = AcademicYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'en' ? 'Create academic year' : 'إضافة سنة دراسية')
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn (): bool => AcademicYearResource::canCreate())
                ->successNotificationTitle(app()->getLocale() === 'en'
                    ? 'Academic year created successfully'
                    : 'تم إنشاء السنة الدراسية بنجاح'),
        ];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en'
            ? 'Academic years'
            : 'السنوات الدراسية';
    }
}
