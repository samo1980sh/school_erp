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

    protected static string|UnitEnum|null $navigationGroup = 'إدارة النظام';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return 'دور';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الأدوار';
    }

    public static function getNavigationLabel(): string
    {
        return 'الأدوار';
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
                Section::make('بيانات الدور')
                    ->description('إدارة اسم الدور والحارس المستخدم في نظام الصلاحيات.')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الدور')
                            ->required()
                            ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('guard_name')
                            ->label('الحارس')
                            ->default('web')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('صلاحيات الدور')
                    ->description('اختر الصلاحيات المرتبطة بهذا الدور.')
                    ->schema([
                        Select::make('permissions')
                            ->label('الصلاحيات')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->relationship(titleAttribute: 'name')
                            ->helperText('مثال: users.view / users.create / users.update'),
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
                    ->label('اسم الدور')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guard_name')
                    ->label('الحارس')
                    ->badge()
                    ->sortable(),

                TextColumn::make('permissions.name')
                    ->label('الصلاحيات')
                    ->badge()
                    ->separator(',')
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->visible(fn(): bool => auth()->user()?->can('roles.update') ?? false)
                    ->after(function (): void {
                        app(PermissionRegistrar::class)->forgetCachedPermissions();
                    })
                    ->successNotificationTitle('تم تحديث الدور بنجاح'),
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
