<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians;

use App\Filament\Resources\Guardians\Pages\ManageGuardians;
use App\Models\Guardian;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class GuardianResource extends Resource
{
    protected static ?string $model = Guardian::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('ولي أمر', 'Guardian');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('أولياء الأمور', 'Guardians');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('أولياء الأمور', 'Guardians');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('إدارة الأشخاص', 'People Management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('guardians.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('guardians.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('guardians.update') ?? false;
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
            ->columns(1)
            ->components([
                Section::make(self::label('بيانات ولي الأمر الأساسية', 'Basic guardian information'))
                    ->description(self::label(
                        'البيانات الشخصية الأساسية لولي الأمر كما ستظهر في السجلات والتقارير.',
                        'The guardian basic personal information as shown in records and reports.'
                    ))
                    ->schema([
                        TextInput::make('guardian_number')
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;'])
                            ->label(self::label('رقم ولي الأمر', 'Guardian number'))
                            ->required()
                            ->unique(table: 'guardians', column: 'guardian_number', ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText(self::label(
                                'رقم فريد لولي الأمر يستخدم في البحث والاستيراد والتصدير.',
                                'A unique guardian number used for search, import, and export.'
                            ))
                            ->autofocus(),

                        TextInput::make('first_name')
                            ->label(self::label('الاسم الأول', 'First name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('father_name')
                            ->label(self::label('اسم الأب', 'Father name'))
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->label(self::label('الكنية', 'Last name'))
                            ->maxLength(255),

                        Select::make('gender')
                            ->label(self::label('الجنس', 'Gender'))
                            ->options(self::genderOptions())
                            ->default('male')
                            ->required()
                            ->native(false),

                        Select::make('relation_type')
                            ->label(self::label('صلة القرابة الافتراضية', 'Default relationship'))
                            ->options(self::relationOptions())
                            ->default('father')
                            ->required()
                            ->native(false),

                        TextInput::make('national_id')
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;'])
                            ->label(self::label('الرقم الوطني', 'National ID'))
                            ->unique(table: 'guardians', column: 'national_id', ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('active')
                            ->required()
                            ->native(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ]),

                Section::make(self::label('التواصل والعمل', 'Contact and work'))
                    ->description(self::label(
                        'بيانات التواصل والعمل لاستخدامها في المراسلات والطوارئ والمتابعة المالية.',
                        'Contact and work details used for communication, emergencies, and financial follow-up.'
                    ))
                    ->schema([
                        TextInput::make('mobile')
                            ->label(self::label('الجوال', 'Mobile'))
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;'])
                            ->label(self::label('الهاتف', 'Phone'))
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(self::label('البريد الإلكتروني', 'Email'))
                            ->email()
                            ->maxLength(255),

                        TextInput::make('occupation')
                            ->label(self::label('المهنة', 'Occupation'))
                            ->maxLength(255),

                        TextInput::make('workplace')
                            ->label(self::label('مكان العمل', 'Workplace'))
                            ->maxLength(255),

                        TextInput::make('address')
                            ->label(self::label('العنوان', 'Address'))
                            ->maxLength(255)
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),

                Section::make(self::label('الطلاب المرتبطون والصلاحيات العائلية', 'Linked students and family permissions'))
                    ->description(self::label(
                        'اربط ولي الأمر بالطلاب، وحدد صلاحيات التواصل والطوارئ والمتابعة المالية.',
                        'Link the guardian to students and define communication, emergency, and financial flags.'
                    ))
                    ->schema([
                        Select::make('students')
                            ->label(self::label('الطلاب المرتبطون', 'Linked students'))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->relationship(name: 'students', titleAttribute: 'full_name')
                            ->options(fn (): array => self::studentOptions())
                            ->helperText(self::label(
                                'يمكن ربط ولي الأمر بأكثر من طالب، مثل الإخوة ضمن نفس العائلة.',
                                'A guardian can be linked to multiple students, such as siblings in the same family.'
                            ))
                            ->columnSpanFull(),

                        Toggle::make('is_emergency_contact')
                            ->label(self::label('جهة اتصال طارئة', 'Emergency contact'))
                            ->default(true),

                        Toggle::make('has_custody')
                            ->label(self::label('له حق الحضانة/المتابعة', 'Has custody/follow-up right'))
                            ->default(true),

                        Toggle::make('is_financial_responsible')
                            ->label(self::label('مسؤول ماليًا', 'Financially responsible'))
                            ->default(true),

                        Toggle::make('is_active')
                            ->label(self::label('نشط', 'Active'))
                            ->default(true),

                        Textarea::make('notes')
                            ->label(self::label('ملاحظات', 'Notes'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
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
                fn (Builder $query): Builder => $query
                    ->with(['students:id,student_number,full_name'])
                    ->withCount('students')
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('guardian_number')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;'])
                    ->label(self::label('رقم ولي الأمر', 'Guardian number'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label(self::label('ولي الأمر', 'Guardian'))
                    ->state(fn (Guardian $record): string => $record->display_name)
                    ->searchable(['first_name', 'father_name', 'last_name', 'full_name', 'guardian_number', 'national_id', 'mobile'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('full_name', $direction))
                    ->weight('bold')
                    ->description(fn (Guardian $record): ?string => $record->national_id),

                TextColumn::make('relation_type')
                    ->label(self::label('صلة القرابة', 'Relationship'))
                    ->formatStateUsing(fn (?string $state): string => self::relationLabel((string) $state))
                    ->badge()
                    ->color('primary'),

                TextColumn::make('mobile')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;'])
                    ->label(self::label('الجوال', 'Mobile'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('students_count')
                    ->label(self::label('عدد الطلاب', 'Students count'))
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('students_summary')
                    ->label(self::label('الطلاب المرتبطون', 'Linked students'))
                    ->state(fn (Guardian $record): string => self::studentsSummary($record))
                    ->html()
                    ->wrap(),

                TextColumn::make('is_financial_responsible')
                    ->label(self::label('مسؤول ماليًا', 'Financial'))
                    ->state(fn (Guardian $record): string => $record->is_financial_responsible ? self::label('نعم', 'Yes') : self::label('لا', 'No'))
                    ->badge()
                    ->color(fn (Guardian $record): string => $record->is_financial_responsible ? 'success' : 'gray')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(self::label('تاريخ الإنشاء', 'Created at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('relation_type')
                    ->label(self::label('صلة القرابة', 'Relationship'))
                    ->options(self::relationOptions())
                    ->native(false),

                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions())
                    ->native(false),

                TernaryFilter::make('is_emergency_contact')
                    ->label(self::label('جهة اتصال طارئة', 'Emergency contact')),

                TernaryFilter::make('is_financial_responsible')
                    ->label(self::label('مسؤول ماليًا', 'Financially responsible')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalHeading(fn (Guardian $record): string => self::label('تعديل ولي الأمر: ', 'Edit guardian: ') . $record->display_name)
                    ->visible(fn (Guardian $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label('تم تحديث ولي الأمر بنجاح', 'Guardian updated successfully')),
            ])
            ->emptyStateHeading(self::label('لا يوجد أولياء أمور', 'No guardians found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإضافة ولي أمر جديد أو استيراد أولياء الأمور من ملف Excel وفق القالب المعتمد.',
                'Start by creating a guardian or importing guardians from the approved Excel template.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGuardians::route('/'),
        ];
    }

    public static function studentOptions(): array
    {
        return Student::query()
            ->where('is_active', true)
            ->orderBy('student_number')
            ->get()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => trim(implode(' - ', array_filter([
                    $student->student_number,
                    $student->display_name,
                ]))),
            ])
            ->toArray();
    }

    public static function genderOptions(): array
    {
        return [
            'male' => self::label('ذكر', 'Male'),
            'female' => self::label('أنثى', 'Female'),
        ];
    }

    public static function relationOptions(): array
    {
        return [
            'father' => self::label('الأب', 'Father'),
            'mother' => self::label('الأم', 'Mother'),
            'grandfather' => self::label('الجد', 'Grandfather'),
            'grandmother' => self::label('الجدة', 'Grandmother'),
            'uncle' => self::label('العم/الخال', 'Uncle'),
            'aunt' => self::label('العمة/الخالة', 'Aunt'),
            'guardian' => self::label('وصي/آخر', 'Guardian/Other'),
        ];
    }

    public static function relationLabel(string $relation): string
    {
        return self::relationOptions()[$relation] ?? $relation;
    }

    public static function statusOptions(): array
    {
        return [
            'active' => self::label('نشط', 'Active'),
            'inactive' => self::label('غير نشط', 'Inactive'),
            'blocked' => self::label('محظور', 'Blocked'),
            'deceased' => self::label('متوفى', 'Deceased'),
        ];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? $status;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'blocked' => 'danger',
            'deceased' => 'gray',
            default => 'warning',
        };
    }

    private static function studentsSummary(Guardian $record): string
    {
        if ($record->students->isEmpty()) {
            return '<span class="text-gray-500">—</span>';
        }

        return $record->students
            ->take(3)
            ->map(fn (Student $student): string => sprintf(
                '<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200">%s</span>',
                e(trim($student->student_number . ' - ' . $student->display_name))
            ))
            ->implode(' ');
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
