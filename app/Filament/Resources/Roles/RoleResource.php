<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ManageRoles;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'school.navigation.system_management';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return __('school.roles.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('school.roles.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('school.roles.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('school.navigation.system_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('roles.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('roles.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        if ($record instanceof Role && $record->name === 'super_admin') {
            return false;
        }

        return auth()->user()?->can('roles.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('school.roles.sections.basic.title'))
                    ->description(__('school.roles.sections.basic.description'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('school.roles.fields.name'))
                            ->required()
                            ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('guard_name')
                            ->label(__('school.roles.fields.guard_name'))
                            ->default('web')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make(__('school.roles.sections.permissions.title'))
                    ->description(__('school.roles.sections.permissions.description'))
                    ->schema([
                        Select::make('permissions')
                            ->label(__('school.roles.fields.permissions'))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->relationship(titleAttribute: 'name')
                            ->helperText(__('school.roles.messages.permissions_help')),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('school.roles.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn(Role $record): ?string => $record->name === 'super_admin'
                        ? __('school.roles.messages.protected_super_admin')
                        : null),

                TextColumn::make('guard_name')
                    ->label(__('school.roles.fields.guard_name'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('permissions.name')
                    ->label(__('school.roles.fields.permissions'))
                    ->badge()
                    ->separator(',')
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label(__('school.roles.fields.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('school.roles.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->visible(fn(Role $record): bool => $record->name !== 'super_admin'
                        && (auth()->user()?->can('roles.update') ?? false))
                    ->after(function (): void {
                        app(PermissionRegistrar::class)->forgetCachedPermissions();
                    })
                    ->successNotificationTitle(__('school.roles.messages.updated')),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }
}
