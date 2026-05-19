<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Support\Rbac\RbacPermissionMetadata;
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
use Illuminate\Database\Eloquent\Builder;
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
                Section::make(self::label('بيانات الدور', 'Role details'))
                    ->description(self::label(
                        'عرّف اسم الدور والحارس المستخدم. في هذه المرحلة كل الأدوار تعمل على guard واحد فقط وهو web.',
                        'Define the role name and guard. At this stage all roles use only the web guard.'
                    ))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('school.roles.fields.name'))
                            ->required()
                            ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                            ->rules(['regex:/^[a-z0-9_.-]+$/'])
                            ->maxLength(255)
                            ->placeholder('school_admin')
                            ->helperText(self::label(
                                'استخدم اسمًا تقنيًا واضحًا مثل: school_admin أو academic_manager.',
                                'Use a clear technical name such as school_admin or academic_manager.'
                            ))
                            ->autofocus(),

                        TextInput::make('guard_name')
                            ->label(__('school.roles.fields.guard_name'))
                            ->default('web')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255)
                            ->helperText(self::label(
                                'يجب أن يبقى web. لا نستخدم Shield ولا Multiple Guards الآن.',
                                'Must remain web. Shield and multiple guards are not used now.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),

                Section::make(self::label('صلاحيات الدور', 'Role permissions'))
                    ->description(self::label(
                        'اختر الصلاحيات المناسبة لهذا الدور. الصلاحيات مرتبة ضمن مجموعات واضحة مع إظهار الاسم المقروء والاسم التقني.',
                        'Choose the permissions for this role. Permissions are grouped clearly and show both readable and technical names.'
                    ))
                    ->schema([
                        Select::make('permissions')
                            ->label(__('school.roles.fields.permissions'))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->relationship(name: 'permissions', titleAttribute: 'display_name')
                            ->options(fn(): array => RbacPermissionMetadata::groupedSelectOptions())
                            ->helperText(self::label(
                                'يمكنك البحث باسم الصلاحية المقروء أو الاسم التقني أو الوصف. لا تمنح الدور أكثر مما يحتاج فعليًا.',
                                'You can search by readable name, technical name, or description. Do not grant more permissions than the role actually needs.'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query): Builder => $query
                    ->with([
                        'permissions' => fn($permissionsQuery) => $permissionsQuery
                            ->orderBy('group_name')
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                    ])
                    ->withCount('permissions')
                    ->orderBy('id')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('school.roles.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn(Role $record): ?string => $record->name === 'super_admin'
                            ? __('school.roles.messages.protected_super_admin')
                            : null
                    ),

                TextColumn::make('permissions_count')
                    ->label(self::label('عدد الصلاحيات', 'Permissions count'))
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('permissions_overview')
                    ->label(self::label('ملخص الصلاحيات', 'Permissions overview'))
                    ->state(fn(Role $record): string => RbacPermissionMetadata::rolePermissionsOverviewHtml($record))
                    ->html()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('guard_name')
                    ->label(__('school.roles.fields.guard_name'))
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('school.roles.fields.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('school.roles.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(
                        fn(Role $record): bool => $record->name !== 'super_admin'
                            && (auth()->user()?->can('roles.update') ?? false)
                    )
                    ->after(function (): void {
                        app(PermissionRegistrar::class)->forgetCachedPermissions();
                    })
                    ->successNotificationTitle(__('school.roles.messages.updated')),
            ])
            ->emptyStateHeading(self::label('لا توجد أدوار', 'No roles found'))
            ->emptyStateDescription(self::label(
                'لم يتم العثور على أدوار مطابقة للبحث الحالي.',
                'No roles match the current search.'
            ));
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

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
