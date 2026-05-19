<?php

declare(strict_types=1);

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\ManagePermissions;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
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
                Section::make(self::label('تنظيم الصلاحية', 'Permission organization'))
                    ->description(self::label(
                        'هذه البيانات تجعل الصلاحيات مفهومة ومجمعة بشكل واضح للعميل داخل لوحة التحكم.',
                        'These fields make permissions clear, grouped, and understandable for the client.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn(): int => self::nextSortOrder())
                            ->required()
                            ->helperText(self::label(
                                'يستخدم لترتيب الصلاحيات داخل كل مجموعة. يُفضّل ترك فراغات مثل 10، 20، 30 لتسهيل الإضافة لاحقًا.',
                                'Used to order permissions inside each group. Leaving gaps like 10, 20, 30 makes future additions easier.'
                            )),

                        TextInput::make('group_name')
                            ->label(self::label('المجموعة', 'Group'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(self::label('مثال: الأدوار والصلاحيات', 'Example: Roles and permissions'))
                            ->helperText(self::label(
                                'اسم المجموعة التي ستظهر في جدول الصلاحيات والفلاتر.',
                                'The group name shown in the permissions table and filters.'
                            )),

                        TextInput::make('display_name')
                            ->label(self::label('الاسم المقروء', 'Readable name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(self::label('مثال: عرض الصلاحيات', 'Example: View permissions'))
                            ->helperText(self::label(
                                'اسم واضح ومفهوم يظهر للمستخدم بدل الاسم التقني.',
                                'A clear user-facing name shown instead of the technical name.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                    ]),

                Section::make(self::label('شرح الصلاحية', 'Permission explanation'))
                    ->description(self::label(
                        'الوصف مهم جدًا حتى يعرف العميل وظيفة كل صلاحية قبل ربطها بالأدوار.',
                        'The description helps the client understand what each permission does before assigning it to roles.'
                    ))
                    ->schema([
                        Textarea::make('description')
                            ->label(self::label('الوصف', 'Description'))
                            ->required()
                            ->rows(5)
                            ->maxLength(1000)
                            ->placeholder(self::label(
                                'اكتب شرحًا واضحًا لما تسمح به هذه الصلاحية داخل النظام.',
                                'Write a clear explanation of what this permission allows in the system.'
                            ))
                            ->helperText(self::label(
                                'مثال: يسمح بالدخول إلى صفحة المستخدمين واستعراض الحسابات الموجودة في النظام.',
                                'Example: Allows access to the users page and viewing existing system accounts.'
                            )),
                    ]),

                Section::make(self::label('البيانات التقنية', 'Technical data'))
                    ->description(self::label(
                        'هذه الحقول تقنية ويجب تعديلها بحذر لأنها مرتبطة بمنطق الصلاحيات داخل الكود.',
                        'These fields are technical and should be edited carefully because they are tied to authorization logic.'
                    ))
                    ->schema([
                        TextInput::make('name')
                            ->label(self::label('الاسم التقني', 'Technical name'))
                            ->required()
                            ->unique(table: 'permissions', column: 'name', ignoreRecord: true)
                            ->rules(['regex:/^[a-z0-9_.-]+$/'])
                            ->maxLength(255)
                            ->placeholder('permissions.view')
                            ->helperText(self::label(
                                'يفضّل استخدام صيغة واضحة مثل: users.view أو roles.update أو permissions.create.',
                                'Use a clear format like: users.view, roles.update, or permissions.create.'
                            )),

                        TextInput::make('guard_name')
                            ->label(self::label('الحارس', 'Guard'))
                            ->default('web')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255)
                            ->helperText(self::label(
                                'يجب أن يبقى web. لا نستخدم Shield ولا Multiple Guards في هذه المرحلة.',
                                'Must remain web. Shield and multiple guards are not used at this stage.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->collapsed(false),
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
                    ->label(self::label('الحارس', 'Guard'))
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(self::label('تاريخ الإنشاء', 'Created at'))
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
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn(): bool => auth()->user()?->can('permissions.update') ?? false)
                    ->after(function (): void {
                        app(PermissionRegistrar::class)->forgetCachedPermissions();
                    })
                    ->successNotificationTitle(self::label(
                        'تم تحديث الصلاحية بنجاح',
                        'Permission updated successfully'
                    )),
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

    private static function nextSortOrder(): int
    {
        return ((int) Permission::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
