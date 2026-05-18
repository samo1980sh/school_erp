<?php

namespace App\Filament\Resources\Permissions\Pages;

use App\Filament\Resources\Permissions\PermissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Spatie\Permission\PermissionRegistrar;

class ManagePermissions extends ManageRecords
{
    protected static string $resource = PermissionResource::class;

    public function getTitle(): string
    {
        return 'الصلاحيات';
    }

    public function getHeading(): string
    {
        return 'إدارة الصلاحيات';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة صلاحية')
                ->slideOver()
                ->modalWidth(Width::FiveExtraLarge)
                ->after(fn(): mixed => app(PermissionRegistrar::class)->forgetCachedPermissions())
                ->successNotificationTitle('تم إنشاء الصلاحية بنجاح'),
        ];
    }
}
