<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
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
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'school.navigation.system_management';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return __('school.users.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('school.users.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('school.users.navigation');
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
        return auth()->user()?->can('users.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('users.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        if ($record instanceof User && $record->hasRole('super_admin') && ! static::currentUserIsSuperAdmin()) {
            return false;
        }

        return auth()->user()?->can('users.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    private static function currentUserIsSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    private static function canManageSensitiveUser(User $record): bool
    {
        if ($record->hasRole('super_admin') && ! static::currentUserIsSuperAdmin()) {
            return false;
        }

        return auth()->user()?->can('users.update') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('school.users.sections.basic.title'))
                    ->description(__('school.users.sections.basic.description'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('school.users.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('email')
                            ->label(__('school.users.fields.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label(__('school.users.fields.password'))
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->visible(fn(string $operation): bool => $operation === 'create')
                            ->confirmed()
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->minLength(8)
                            ->maxLength(255),

                        TextInput::make('password_confirmation')
                            ->label(__('school.users.fields.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->visible(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->minLength(8)
                            ->maxLength(255),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),

                Section::make(__('school.users.sections.roles.title'))
                    ->description(__('school.users.sections.roles.description'))
                    ->schema([
                        Select::make('roles')
                            ->label(__('school.users.fields.roles'))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->disabled(fn(?User $record): bool => $record?->id === auth()->id() || ($record?->hasRole('super_admin') ?? false))
                            ->relationship(titleAttribute: 'name')
                            ->helperText(__('school.users.messages.roles_help'))
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('school.users.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn(User $record): ?string => $record->hasRole('super_admin')
                        ? __('school.users.messages.protected_super_admin')
                        : null),

                TextColumn::make('email')
                    ->label(__('school.users.fields.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label(__('school.users.fields.roles'))
                    ->badge()
                    ->separator(',')
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label(__('school.users.fields.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('school.users.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn(User $record): bool => static::canManageSensitiveUser($record))
                    ->successNotificationTitle(__('school.users.messages.updated')),

                Action::make('changePassword')
                    ->label(__('school.users.actions.change_password'))
                    ->icon('heroicon-o-key')
                    ->slideOver()
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->visible(fn(User $record): bool => static::canManageSensitiveUser($record))
                    ->form([
                        TextInput::make('password')
                            ->label(__('school.users.fields.new_password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->confirmed()
                            ->minLength(8)
                            ->maxLength(255),

                        TextInput::make('password_confirmation')
                            ->label(__('school.users.fields.new_password_confirmation'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->dehydrated(false)
                            ->minLength(8)
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->forceFill([
                            'password' => Hash::make($data['password']),
                        ])->save();
                    })
                    ->successNotificationTitle(__('school.users.messages.password_changed')),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
