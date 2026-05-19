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
        return __('school.users.title');
    }

    public function getHeading(): string
    {
        return __('school.users.heading');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('school.users.actions.create'))
                ->slideOver()
                ->modalWidth(Width::SevenExtraLarge)
                ->visible(fn(): bool => auth()->user()?->can('users.create') ?? false),
        ];
    }
}
