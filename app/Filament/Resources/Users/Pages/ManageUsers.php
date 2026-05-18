<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'المستخدمون';
    }

    public function getHeading(): string
    {
        return 'إدارة المستخدمين';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة مستخدم')
                ->slideOver()
                ->modalWidth(Width::FiveExtraLarge),
        ];
    }
}
