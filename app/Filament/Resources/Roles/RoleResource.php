<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Support\Rbac\RbacPermissionMetadata;
use App\Support\Rbac\RbacRoleMetadata;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

    protected static ?string $recordTitleAttribute = 'display_name';

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
                Section::make(self::label('المعاينة الإنكليزية', 'English display preview'))
                    ->description(self::label(
                        'تظهر هذه المعاينة عند استخدام الواجهة الإنكليزية فقط، بدون تغيير القيم العربية المخزنة في قاعدة البيانات.',
                        'This preview is shown for the English interface without changing the Arabic values stored in the database.'
                    ))
                    ->schema([
                        TextInput::make('localized_display_name_preview')
                            ->label(self::label('الاسم المعروض', 'Displayed name'))
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?Role $record): ?string => $record
                                ? RbacRoleMetadata::displayName($record)
                                : null),

                        Textarea::make('localized_description_preview')
                            ->label(self::label('الوصف المعروض', 'Displayed description'))
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?Role $record): ?string => $record
                                ? RbacRoleMetadata::description($record)
                                : null),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->visible(fn (): bool => app()->getLocale() === 'en'),

                Section::make(self::label('بيانات الدور', 'Role details'))
                    ->description(self::label(
                        'عرّف اسم الدور وترتيبه ووصفه. الاسم التقني يبقى مرتبطًا بمنطق الصلاحيات داخل النظام.',
                        'Define the role name, order, and description. The technical name remains tied to authorization logic.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required()
                            ->helperText(self::label(
                                'يستخدم لترتيب الأدوار في الجداول والقوائم.',
                                'Used to order roles in tables and lists.'
                            )),

                        TextInput::make('display_name')
                            ->label(self::label('الاسم المقروء المخزن', 'Stored readable name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(self::label('مثال: مدير المدرسة', 'Example: School Administrator'))
                            ->helperText(self::label(
                                'القيمة العربية المخزنة في قاعدة البيانات. عند الواجهة الإنكليزية يتم عرض الترجمة من ملف lang/en/rbac_roles.php.',
                                'The Arabic value stored in the database. In the English interface, the translation is displayed from lang/en/rbac_roles.php.'
                            )),

                        TextInput::make('name')
                            ->label(__('school.roles.fields.name'))
                            ->required()
                            ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                            ->rules(['regex:/^[a-z0-9_.-]+$/'])
                            ->maxLength(255)
                            ->placeholder('school_admin')
                            ->helperText(self::label(
                                'اسم تقني مستخدم في الكود. لا تعدّله إلا عند الحاجة الفعلية.',
                                'Technical name used in code. Do not change it unless really needed.'
                            )),

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
                        'lg' => 2,
                    ]),

                Section::make(self::label('شرح الدور', 'Role explanation'))
                    ->description(self::label(
                        'الوصف يساعد العميل على فهم وظيفة الدور قبل ربطه بالمستخدمين.',
                        'The description helps the client understand the role before assigning it to users.'
                    ))
                    ->schema([
                        Textarea::make('description')
                            ->label(self::label('الوصف المخزن', 'Stored description'))
                            ->required()
                            ->rows(5)
                            ->maxLength(1000)
                            ->helperText(self::label(
                                'هذا النص هو القيمة المخزنة في قاعدة البيانات. عند الواجهة الإنكليزية يتم عرض الترجمة من ملف lang/en/rbac_roles.php.',
                                'This text is the value stored in the database. In the English interface, the translation is displayed from lang/en/rbac_roles.php.'
                            )),
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
                            ->options(fn (): array => RbacPermissionMetadata::groupedSelectOptions())
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
                fn (Builder $query): Builder => $query
                    ->with([
                        'permissions' => fn ($permissionsQuery) => $permissionsQuery
                            ->orderBy('group_name')
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                    ])
                    ->withCount('permissions')
                    ->orderBy('sort_order')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('display_name')
                    ->label(self::label('الدور', 'Role'))
                    ->state(fn (Role $record): string => RbacRoleMetadata::displayName($record))
                    ->searchable(['display_name', 'name', 'description'])
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Role $record): ?string => self::roleDescriptionLine($record))
                    ->wrap(),

                TextColumn::make('name')
                    ->label(self::label('الاسم التقني', 'Technical name'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(self::label('الوصف', 'Description'))
                    ->state(fn (Role $record): string => RbacRoleMetadata::description($record))
                    ->wrap()
                    ->limit(120)
                    ->tooltip(fn (Role $record): ?string => filled(RbacRoleMetadata::description($record))
                        ? RbacRoleMetadata::description($record)
                        : null)
                    ->toggleable(),

                TextColumn::make('permissions_count')
                    ->label(self::label('عدد الصلاحيات', 'Permissions count'))
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('permissions_overview')
                    ->label(self::label('ملخص الصلاحيات', 'Permissions overview'))
                    ->state(fn (Role $record): string => RbacPermissionMetadata::rolePermissionsOverviewHtml($record))
                    ->html()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('sort_order')
                    ->label(self::label('الترتيب', 'Order'))
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        fn (Role $record): bool => $record->name !== 'super_admin'
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

    private static function nextSortOrder(): int
    {
        return ((int) Role::query()->max('sort_order')) + 10;
    }

    private static function roleDescriptionLine(Role $record): ?string
    {
        $items = [];

        if ($record->name === 'super_admin') {
            $items[] = __('school.roles.messages.protected_super_admin');
        }

        $description = RbacRoleMetadata::description($record);

        if ($description !== '') {
            $items[] = $description;
        }

        return $items === [] ? null : implode(' • ', $items);
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}