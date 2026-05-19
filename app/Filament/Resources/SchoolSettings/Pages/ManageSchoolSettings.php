<?php

declare(strict_types=1);

namespace App\Filament\Resources\SchoolSettings\Pages;

use App\Filament\Resources\SchoolSettings\SchoolSettingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolSettings extends ManageRecords
{
    protected static string $resource = SchoolSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'en'
            ? 'School identity'
            : 'هوية المدرسة';
    }
}