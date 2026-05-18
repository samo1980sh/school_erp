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

    protected static string|UnitEnum|null $navigationGroup = 'إدارة النظام';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return 'مستخدم';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المستخدمون';
    }

    public static function getNavigationLabel(): string
    {
        return 'المستخدمون';
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
                Section::make('بيانات المستخدم')
                    ->description('إدارة بيانات الدخول الأساسية للمستخدم داخل النظام.')
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('كلمة المرور')
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
                            ->label('تأكيد كلمة المرور')
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->visible(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->minLength(8)
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('الأدوار')
                    ->description('ربط المستخدم بالأدوار الإدارية داخل النظام.')
                    ->schema([
                        Select::make('roles')
                            ->label('أدوار المستخدم')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->disabled(fn(?User $record): bool => $record?->id === auth()->id())
                            ->relationship(titleAttribute: 'name')
                            ->helperText('لحماية النظام، لا يمكن تعديل أدوار حسابك الحالي من هذه الشاشة.'),
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
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('الأدوار')
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
                    ->visible(fn(): bool => auth()->user()?->can('users.update') ?? false)
                    ->successNotificationTitle('تم تحديث المستخدم بنجاح'),

                Action::make('changePassword')
                    ->label('تغيير كلمة المرور')
                    ->icon('heroicon-o-key')
                    ->slideOver()
                    ->modalWidth(Width::Large)
                    ->visible(fn(): bool => auth()->user()?->can('users.update') ?? false)
                    ->form([
                        TextInput::make('password')
                            ->label('كلمة المرور الجديدة')
                            ->password()
                            ->revealable()
                            ->required()
                            ->confirmed()
                            ->minLength(8)
                            ->maxLength(255),

                        TextInput::make('password_confirmation')
                            ->label('تأكيد كلمة المرور الجديدة')
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
                    ->successNotificationTitle('تم تغيير كلمة المرور بنجاح'),
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
