<?php

declare(strict_types=1);

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\ManagePermissions;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|UnitEnum|null $navigationGroup = 'school.navigation.system_management';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return __('school.permissions.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('school.permissions.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('school.permissions.navigation');
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
        return auth()->user()?->can('permissions.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('permissions.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('permissions.update') ?? false;
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
                Section::make(__('school.permissions.sections.basic.title'))
                    ->description(__('school.permissions.sections.basic.description'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('school.permissions.fields.name'))
                            ->required()
                            ->unique(table: 'permissions', column: 'name', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder(__('school.permissions.messages.name_placeholder'))
                            ->helperText(__('school.permissions.messages.name_help'))
                            ->autofocus(),

                        TextInput::make('guard_name')
                            ->label(__('school.permissions.fields.guard_name'))
                            ->default('web')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255)
                            ->helperText(self::label(
                                'يجب أن يبقى web لأن لوحة Filament الحالية تعمل على نفس guard.',
                                'Must remain web because the current Filament panel uses the same guard.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query): Builder => $query
                    ->withCount('roles')
                    ->orderBy('group_name')
                    ->orderBy('sort_order')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('group_name')
                    ->label(self::label('المجموعة', 'Group'))
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label(self::label('اسم الصلاحية', 'Permission name'))
                    ->weight('bold')
                    ->searchable(['display_name', 'name', 'description'])
                    ->sortable()
                    ->wrap(),

                TextColumn::make('description')
                    ->label(self::label('الوصف', 'Description'))
                    ->searchable()
                    ->wrap()
                    ->limit(100)
                    ->tooltip(
                        fn(Permission $record): ?string => filled($record->description)
                            ? (string) $record->description
                            : null
                    ),

                TextColumn::make('name')
                    ->label(self::label('الاسم التقني', 'Technical name'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles_count')
                    ->label(self::label('الأدوار', 'Roles'))
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label(self::label('الترتيب', 'Order'))
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('guard_name')
                    ->label(__('school.permissions.fields.guard_name'))
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('school.permissions.fields.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group_name')
                    ->label(self::label('المجموعة', 'Group'))
                    ->options(fn(): array => Permission::query()
                        ->whereNotNull('group_name')
                        ->where('group_name', '<>', '')
                        ->distinct()
                        ->orderBy('group_name')
                        ->pluck('group_name', 'group_name')
                        ->toArray())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('school.permissions.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn(): bool => auth()->user()?->can('permissions.update') ?? false)
                    ->after(function (): void {
                        app(PermissionRegistrar::class)->forgetCachedPermissions();
                    })
                    ->successNotificationTitle(__('school.permissions.messages.updated')),
            ])
            ->emptyStateHeading(self::label('لا توجد صلاحيات', 'No permissions found'))
            ->emptyStateDescription(self::label(
                'لم يتم العثور على صلاحيات مطابقة للبحث أو التصفية الحالية.',
                'No permissions match the current search or filters.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePermissions::route('/'),
        ];
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
