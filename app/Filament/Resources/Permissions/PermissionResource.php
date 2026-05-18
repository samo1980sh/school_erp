<?php

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
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة النظام';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return 'صلاحية';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الصلاحيات';
    }

    public static function getNavigationLabel(): string
    {
        return 'الصلاحيات';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الصلاحية')
                    ->description('إدارة أسماء الصلاحيات فقط. ربط الصلاحيات بالأدوار سيتم في مرحلة مستقلة.')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الصلاحية')
                            ->required()
                            ->unique(table: 'permissions', column: 'name', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('مثال: users.view')
                            ->helperText('استخدم نمطًا واضحًا مثل: users.view / users.create / users.update.'),

                        TextInput::make('guard_name')
                            ->label('الحارس')
                            ->default('web')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الصلاحية')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guard_name')
                    ->label('الحارس')
                    ->badge()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('مرتبطة بالأدوار')
                    ->badge()
                    ->separator(',')
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->after(fn(): mixed => app(PermissionRegistrar::class)->forgetCachedPermissions())
                    ->successNotificationTitle('تم تحديث الصلاحية بنجاح'),
            ])
            ->toolbarActions([
                //
            ]);
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
}
