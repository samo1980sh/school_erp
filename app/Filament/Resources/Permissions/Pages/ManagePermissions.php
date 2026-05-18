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
        return __('school.permissions.title');
    }

    public function getHeading(): string
    {
        return __('school.permissions.heading');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('school.permissions.actions.create'))
                ->slideOver()
                ->modalWidth(Width::FiveExtraLarge)
                ->visible(fn(): bool => auth()->user()?->can('permissions.create') ?? false)
                ->after(function (): void {
                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                })
                ->successNotificationTitle(__('school.permissions.messages.created')),
        ];
    }
}
