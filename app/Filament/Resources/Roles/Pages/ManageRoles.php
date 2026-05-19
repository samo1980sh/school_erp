<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Spatie\Permission\PermissionRegistrar;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return __('school.roles.title');
    }

    public function getHeading(): string
    {
        return __('school.roles.heading');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('school.roles.actions.create'))
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn(): bool => auth()->user()?->can('roles.create') ?? false)
                ->after(function (): void {
                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                })
                ->successNotificationTitle(__('school.roles.messages.created')),
        ];
    }
}
