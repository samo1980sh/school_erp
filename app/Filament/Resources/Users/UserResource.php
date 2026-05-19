<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Support\Rbac\RbacRoleMetadata;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = null;

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
        return self::label('إدارة النظام', 'System Management');
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(self::label('حالة الحساب', 'Account status'))
                    ->description(self::label(
                        'ملخص سريع يوضح نوع الحساب وإمكانية دخوله إلى لوحة الإدارة.',
                        'A quick summary showing the account type and whether it can access the admin panel.'
                    ))
                    ->schema([
                        TextInput::make('account_status_preview')
                            ->label(self::label('نوع الحساب', 'Account type'))
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn(?User $record): ?string => $record
                                ? self::accountStatusLabel($record)
                                : null),

                        TextInput::make('admin_access_preview')
                            ->label(self::label('دخول لوحة الإدارة', 'Admin panel access'))
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn(?User $record): ?string => $record
                                ? self::panelAccessLabel($record)
                                : null),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->visible(fn(?User $record): bool => $record !== null),

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
                    ->description(self::label(
                        'ربط المستخدم بالأدوار المناسبة. حسابك الحالي وحسابات super_admin محمية من تعديل الأدوار من هذه الشاشة.',
                        'Assign the user to the appropriate roles. Your current account and super_admin accounts are protected from role changes on this screen.'
                    ))
                    ->schema([
                        Select::make('roles')
                            ->label(__('school.users.fields.roles'))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->relationship(name: 'roles', titleAttribute: 'name')
                            ->options(fn(): array => self::roleOptions())
                            ->disabled(fn(?User $record): bool => static::rolesFieldShouldBeDisabled($record))
                            ->helperText(self::label(
                                'الأدوار تظهر باسم مقروء مع الاسم التقني. الأدوار هي التي تحدد وصول المستخدم إلى لوحة الإدارة والصلاحيات المتاحة له.',
                                'Roles are displayed with readable and technical names. Roles control admin access and available permissions.'
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
                        'roles' => fn($rolesQuery) => $rolesQuery
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                    ])
                    ->withCount('roles')
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('school.users.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(User $record): ?string => self::userDescription($record)),

                TextColumn::make('email')
                    ->label(__('school.users.fields.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('account_status')
                    ->label(self::label('نوع الحساب', 'Account type'))
                    ->state(fn(User $record): string => self::accountStatusLabel($record))
                    ->badge()
                    ->color(fn(User $record): string => $record->hasRole('super_admin') ? 'danger' : 'gray'),

                TextColumn::make('admin_panel_access')
                    ->label(self::label('دخول الإدارة', 'Admin access'))
                    ->state(fn(User $record): string => self::panelAccessLabel($record))
                    ->badge()
                    ->color(fn(User $record): string => $record->can('admin_panel.access') ? 'success' : 'gray'),

                TextColumn::make('roles_count')
                    ->label(self::label('عدد الأدوار', 'Roles count'))
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('roles_summary')
                    ->label(__('school.users.fields.roles'))
                    ->state(fn(User $record): string => self::rolesSummary($record))
                    ->html()
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label(__('school.users.fields.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label(self::label('آخر تحديث', 'Updated at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->label(__('school.users.fields.roles'))
                    ->multiple()
                    ->options(fn(): array => self::roleOptions())
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $values = array_values(array_filter((array) ($data['values'] ?? [])));

                        if ($values === []) {
                            return $query;
                        }

                        return $query->whereHas('roles', function (Builder $rolesQuery) use ($values): void {
                            $rolesQuery->whereIn('roles.id', $values);
                        });
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('school.users.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn(User $record): bool => static::canManageSensitiveUser($record))
                    ->after(function (): void {
                        app(PermissionRegistrar::class)->forgetCachedPermissions();
                    })
                    ->successNotificationTitle(__('school.users.messages.updated')),

                Action::make('changePassword')
                    ->label(__('school.users.actions.change_password'))
                    ->icon('heroicon-o-key')
                    ->slideOver()
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->visible(fn(User $record): bool => static::canManageSensitiveUser($record))
                    ->form([
                        Section::make(self::label('تغيير كلمة المرور', 'Change password'))
                            ->description(self::label(
                                'استخدم كلمة مرور قوية. سيتم حفظها مشفرة داخل قاعدة البيانات.',
                                'Use a strong password. It will be stored encrypted in the database.'
                            ))
                            ->schema([
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
                            ->columns(1),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->forceFill([
                            'password' => Hash::make($data['password']),
                        ])->save();
                    })
                    ->successNotificationTitle(__('school.users.messages.password_changed')),
            ])
            ->emptyStateHeading(self::label('لا يوجد مستخدمون', 'No users found'))
            ->emptyStateDescription(self::label(
                'لم يتم العثور على مستخدمين مطابقين للبحث أو الفلاتر الحالية.',
                'No users match the current search or filters.'
            ));
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

    private static function rolesFieldShouldBeDisabled(?User $record): bool
    {
        if (! $record instanceof User) {
            return false;
        }

        if ($record->id === auth()->id()) {
            return true;
        }

        return $record->hasRole('super_admin');
    }

    private static function roleOptions(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn(Role $role): array => [
                $role->getKey() => RbacRoleMetadata::optionLabel($role),
            ])
            ->toArray();
    }

    private static function userDescription(User $record): ?string
    {
        $notes = [];

        if ($record->id === auth()->id()) {
            $notes[] = self::label('حسابك الحالي', 'Current account');
        }

        if ($record->hasRole('super_admin')) {
            $notes[] = __('school.users.messages.protected_super_admin');
        }

        return $notes === [] ? null : implode(' • ', $notes);
    }

    private static function accountStatusLabel(User $record): string
    {
        if ($record->hasRole('super_admin')) {
            return self::label('حساب النظام الرئيسي', 'Main system account');
        }

        if ($record->roles->isEmpty()) {
            return self::label('بدون أدوار', 'No roles assigned');
        }

        return self::label('حساب مستخدم', 'User account');
    }

    private static function panelAccessLabel(User $record): string
    {
        return $record->can('admin_panel.access')
            ? self::label('مسموح', 'Allowed')
            : self::label('غير مسموح', 'Not allowed');
    }

    private static function rolesSummary(User $record): string
    {
        if ($record->roles->isEmpty()) {
            return '<span class="text-gray-500">—</span>';
        }

        return $record->roles
            ->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ])
            ->map(function (Role $role): string {
                $displayName = RbacRoleMetadata::displayName($role);
                $technicalName = (string) $role->name;

                return sprintf(
                    '<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200" title="%s">%s <span class="ms-1 text-gray-400">(%s)</span></span>',
                    e(RbacRoleMetadata::description($role)),
                    e($displayName),
                    e($technicalName)
                );
            })
            ->implode(' ');
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
