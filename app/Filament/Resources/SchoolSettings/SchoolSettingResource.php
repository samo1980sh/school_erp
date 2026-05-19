<?php

declare(strict_types=1);

namespace App\Filament\Resources\SchoolSettings;

use App\Filament\Resources\SchoolSettings\Pages\ManageSchoolSettings;
use App\Models\SchoolSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SchoolSettingResource extends Resource
{
    protected static ?string $model = SchoolSetting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'school_name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('هوية المدرسة', 'School identity');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('هوية المدرسة', 'School identity');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('هوية المدرسة', 'School identity');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('إعدادات المدرسة', 'School Settings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('school_identity.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('school_identity.update') ?? false;
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
                Section::make(self::label('البيانات الأساسية', 'Basic information'))
                    ->description(self::label(
                        'البيانات الرسمية التي تظهر في النظام والتقارير والمراسلات.',
                        'Official information used across the system, reports, and communication.'
                    ))
                    ->schema([
                        TextInput::make('school_name')
                            ->label(self::label('اسم المدرسة', 'School name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('legal_name')
                            ->label(self::label('الاسم القانوني', 'Legal name'))
                            ->maxLength(255),

                        TextInput::make('short_name')
                            ->label(self::label('الاسم المختصر', 'Short name'))
                            ->maxLength(255),

                        TextInput::make('school_code')
                            ->label(self::label('رمز المدرسة', 'School code'))
                            ->unique(table: 'school_settings', column: 'school_code', ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('license_number')
                            ->label(self::label('رقم الترخيص', 'License number'))
                            ->maxLength(255),

                        TextInput::make('principal_name')
                            ->label(self::label('اسم المدير', 'Principal name'))
                            ->maxLength(255),

                        TextInput::make('established_year')
                            ->label(self::label('سنة التأسيس', 'Established year'))
                            ->numeric()
                            ->rules(['nullable', 'integer', 'min:1800'])
                            ->maxValue(((int) now()->format('Y')) + 1),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),

                Section::make(self::label('معلومات التواصل', 'Contact information'))
                    ->description(self::label(
                        'بيانات التواصل والعنوان المستخدمة في المراسلات والتقارير.',
                        'Contact and address details used in communication and reports.'
                    ))
                    ->schema([
                        TextInput::make('email')
                            ->label(self::label('البريد الإلكتروني', 'Email'))
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label(self::label('الهاتف', 'Phone'))
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('mobile')
                            ->label(self::label('الجوال', 'Mobile'))
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('website')
                            ->label(self::label('الموقع الإلكتروني', 'Website'))
                            ->url()
                            ->maxLength(255),

                        TextInput::make('country')
                            ->label(self::label('الدولة', 'Country'))
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label(self::label('المدينة', 'City'))
                            ->maxLength(255),

                        TextInput::make('address')
                            ->label(self::label('العنوان', 'Address'))
                            ->maxLength(255)
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),

                        TextInput::make('postal_code')
                            ->label(self::label('الرمز البريدي', 'Postal code'))
                            ->maxLength(255),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),

                Section::make(self::label('الشعار والهوية البصرية', 'Logo and visual identity'))
                    ->description(self::label(
                        'الشعار والأيقونة الخاصة بالمدرسة. سيتم استخدامها لاحقًا في التقارير والواجهات.',
                        'School logo and favicon. They will later be used in reports and interfaces.'
                    ))
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label(self::label('شعار المدرسة', 'School logo'))
                            ->disk('public')
                            ->directory('school/settings')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText(self::label(
                                'يفضل رفع صورة PNG أو JPG واضحة بحجم مناسب.',
                                'Upload a clear PNG or JPG image with a suitable size.'
                            )),

                        FileUpload::make('favicon_path')
                            ->label(self::label('أيقونة الموقع', 'Favicon'))
                            ->disk('public')
                            ->directory('school/settings')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(1024)
                            ->helperText(self::label(
                                'يفضل رفع صورة مربعة صغيرة.',
                                'Upload a small square image.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),

                Section::make(self::label('الإعدادات العامة', 'General settings'))
                    ->description(self::label(
                        'إعدادات أساسية تستخدم لاحقًا في النظام والتقارير والحسابات.',
                        'Basic settings used later across the system, reports, and accounting.'
                    ))
                    ->schema([
                        Select::make('default_locale')
                            ->label(self::label('اللغة الافتراضية', 'Default language'))
                            ->options([
                                'ar' => self::label('العربية', 'Arabic'),
                                'en' => self::label('الإنكليزية', 'English'),
                            ])
                            ->required()
                            ->native(false),

                        Select::make('timezone')
                            ->label(self::label('المنطقة الزمنية', 'Timezone'))
                            ->options([
                                'Asia/Damascus' => 'Asia/Damascus',
                                'Asia/Riyadh' => 'Asia/Riyadh',
                                'Europe/Istanbul' => 'Europe/Istanbul',
                                'UTC' => 'UTC',
                            ])
                            ->required()
                            ->searchable()
                            ->native(false),

                        Select::make('currency_code')
                            ->label(self::label('العملة', 'Currency'))
                            ->options([
                                'SYP' => self::label('ليرة سورية - SYP', 'Syrian Pound - SYP'),
                                'USD' => self::label('دولار أمريكي - USD', 'US Dollar - USD'),
                                'EUR' => self::label('يورو - EUR', 'Euro - EUR'),
                            ])
                            ->required()
                            ->searchable()
                            ->native(false),

                        Toggle::make('is_active')
                            ->label(self::label('مفعلة', 'Active'))
                            ->default(true)
                            ->helperText(self::label(
                                'يجب أن تبقى هوية مدرسة واحدة مفعلة في هذه المرحلة.',
                                'One school identity should remain active at this stage.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query): Builder => $query
                    ->orderByDesc('is_active')
                    ->orderBy('id')
            )
            ->columns([
                TextColumn::make('school_name')
                    ->label(self::label('اسم المدرسة', 'School name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(SchoolSetting $record): ?string => $record->legal_name),

                TextColumn::make('school_code')
                    ->label(self::label('رمز المدرسة', 'School code'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('principal_name')
                    ->label(self::label('المدير', 'Principal'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('city')
                    ->label(self::label('المدينة', 'City'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('default_locale')
                    ->label(self::label('اللغة', 'Language'))
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn(?string $state): string => $state === 'en'
                        ? self::label('إنكليزي', 'English')
                        : self::label('عربي', 'Arabic')),

                TextColumn::make('currency_code')
                    ->label(self::label('العملة', 'Currency'))
                    ->badge()
                    ->color('success'),

                TextColumn::make('is_active')
                    ->label(self::label('الحالة', 'Status'))
                    ->state(fn(SchoolSetting $record): string => $record->is_active
                        ? self::label('مفعلة', 'Active')
                        : self::label('غير مفعلة', 'Inactive'))
                    ->badge()
                    ->color(fn(SchoolSetting $record): string => $record->is_active ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label(self::label('آخر تحديث', 'Updated at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn(SchoolSetting $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث هوية المدرسة بنجاح',
                        'School identity updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد إعدادات مدرسة', 'No school settings found'))
            ->emptyStateDescription(self::label(
                'يجب تشغيل SchoolSettingSeeder لإنشاء سجل هوية المدرسة الافتراضي.',
                'Run SchoolSettingSeeder to create the default school identity record.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSchoolSettings::route('/'),
        ];
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
