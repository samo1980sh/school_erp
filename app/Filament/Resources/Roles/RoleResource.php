<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ManageRoles;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الدور')
                    ->description('إدارة أسماء الأدوار الأساسية داخل النظام. الصلاحيات التفصيلية ستضاف في مرحلة مستقلة.')
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
                    ->successNotificationTitle('تم تحديث الدور بنجاح'),
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
            'index' => ManageRoles::route('/'),
        ];
    }
}
