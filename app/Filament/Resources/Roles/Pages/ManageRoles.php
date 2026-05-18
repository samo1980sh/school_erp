<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return 'الأدوار';
    }

    public function getHeading(): string
    {
        return 'إدارة الأدوار';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة دور')
                ->slideOver()
                ->modalWidth(Width::FiveExtraLarge)
                ->successNotificationTitle('تم إنشاء الدور بنجاح'),
        ];
    }
}
