<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages\ManageTeachers;
use App\Models\Teacher;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
use UnitEnum;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $slug = 'teachers';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('معلم', 'Teacher');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('المعلمون', 'Teachers');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('المعلمون', 'Teachers');
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
        return auth()->user()?->can('teachers.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('teachers.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('teachers.update') ?? false;
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
                Section::make(self::label('البيانات الشخصية', 'Personal information'))
                    ->description(self::label(
                        'أدخل البيانات الأساسية التي تميز ملف المعلم داخل النظام.',
                        'Enter the core information that identifies the teacher profile in the system.'
                    ))
                    ->schema([
                        TextInput::make('teacher_number')
                            ->label(self::label('رقم المعلم', 'Teacher number'))
                            ->required()
                            ->unique(table: 'teachers', column: 'teacher_number', ignoreRecord: true)
                            ->maxLength(50)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;'])
                            ->helperText(self::label(
                                'رقم داخلي فريد لا يتكرر، ويفضل استخدام صيغة مثل TCH-2026-0001.',
                                'A unique internal number. A format like TCH-2026-0001 is recommended.'
                            )),

                        TextInput::make('full_name')
                            ->label(self::label('اسم المعلم', 'Teacher name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        Select::make('gender')
                            ->label(self::label('الجنس', 'Gender'))
                            ->options(self::genderOptions())
                            ->default('male')
                            ->required()
                            ->native(false),

                        TextInput::make('national_id')
                            ->label(self::label('الرقم الوطني', 'National ID'))
                            ->unique(table: 'teachers', column: 'national_id', ignoreRecord: true)
                            ->maxLength(50)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;']),

                        DatePicker::make('birth_date')
                            ->label(self::label('تاريخ الميلاد', 'Birth date'))
                            ->native(false)
                            ->displayFormat('Y-m-d'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),

                Section::make(self::label('معلومات التواصل', 'Contact information'))
                    ->description(self::label(
                        'بيانات التواصل مع المعلم. تعرض الأرقام باتجاه LTR حتى لا تنعكس في الواجهة العربية.',
                        'Teacher contact details. Numeric fields are displayed LTR to avoid reversed numbers in RTL layouts.'
                    ))
                    ->schema([
                        TextInput::make('email')
                            ->label(self::label('البريد الإلكتروني', 'Email'))
                            ->email()
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;']),

                        TextInput::make('phone')
                            ->label(self::label('الهاتف', 'Phone'))
                            ->tel()
                            ->maxLength(50)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;']),

                        TextInput::make('mobile')
                            ->label(self::label('الجوال', 'Mobile'))
                            ->tel()
                            ->maxLength(50)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;']),

                        TextInput::make('address')
                            ->label(self::label('العنوان', 'Address'))
                            ->maxLength(255)
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 3,
                            ]),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),

                Section::make(self::label('البيانات الوظيفية والأكاديمية', 'Employment and academic information'))
                    ->description(self::label(
                        'حدد تخصص المعلم ومؤهله ونوع عمله وحالة ملفه.',
                        'Set the teacher specialization, qualification, employment type, and profile status.'
                    ))
                    ->schema([
                        TextInput::make('specialization')
                            ->label(self::label('التخصص', 'Specialization'))
                            ->maxLength(255),

                        TextInput::make('qualification')
                            ->label(self::label('المؤهل العلمي', 'Qualification'))
                            ->maxLength(255),

                        TextInput::make('job_title')
                            ->label(self::label('المسمى الوظيفي', 'Job title'))
                            ->maxLength(255),

                        Select::make('employment_type')
                            ->label(self::label('نوع التعاقد', 'Employment type'))
                            ->options(self::employmentTypeOptions())
                            ->default('full_time')
                            ->required()
                            ->native(false),

                        DatePicker::make('hire_date')
                            ->label(self::label('تاريخ التعيين', 'Hire date'))
                            ->native(false)
                            ->displayFormat('Y-m-d'),

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
                        'xl' => 3,
                    ]),

                Section::make(self::label('ملاحظات', 'Notes'))
                    ->description(self::label(
                        'ملاحظات داخلية اختيارية لا تظهر للطلاب أو أولياء الأمور.',
                        'Optional internal notes not shown to students or guardians.'
                    ))
                    ->schema([
                        Textarea::make('notes')
                            ->label(self::label('ملاحظات', 'Notes'))
                            ->rows(4)
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->orderBy('full_name')
                    ->orderBy('teacher_number')
            )
            ->columns([
                TextColumn::make('teacher_number')
                    ->label(self::label('رقم المعلم', 'Teacher no.'))
                    ->formatStateUsing(fn (mixed $state): string => self::ltrText($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label(self::label('اسم المعلم', 'Teacher name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Teacher $record): ?string => $record->job_title),

                TextColumn::make('specialization')
                    ->label(self::label('التخصص', 'Specialization'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('mobile')
                    ->label(self::label('الجوال', 'Mobile'))
                    ->formatStateUsing(fn (mixed $state): string => self::ltrText($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label(self::label('الهاتف', 'Phone'))
                    ->formatStateUsing(fn (mixed $state): string => self::ltrText($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('national_id')
                    ->label(self::label('الرقم الوطني', 'National ID'))
                    ->formatStateUsing(fn (mixed $state): string => self::ltrText($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('employment_type')
                    ->label(self::label('نوع التعاقد', 'Employment type'))
                    ->formatStateUsing(fn (?string $state): string => self::employmentTypeLabel((string) $state))
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state))
                    ->sortable(),

                TextColumn::make('hire_date')
                    ->label(self::label('تاريخ التعيين', 'Hire date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions())
                    ->native(false),

                SelectFilter::make('employment_type')
                    ->label(self::label('نوع التعاقد', 'Employment type'))
                    ->options(self::employmentTypeOptions())
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (Teacher $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث بيانات المعلم بنجاح',
                        'Teacher updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا يوجد معلمون', 'No teachers found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإضافة معلم جديد أو استخدم قالب Excel لاستيراد بيانات المعلمين.',
                'Start by adding a new teacher or use the Excel template to import teacher data.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTeachers::route('/'),
        ];
    }

    public static function genderOptions(): array
    {
        return [
            'male' => self::label('ذكر', 'Male'),
            'female' => self::label('أنثى', 'Female'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active' => self::label('نشط', 'Active'),
            'on_leave' => self::label('إجازة', 'On leave'),
            'inactive' => self::label('غير نشط', 'Inactive'),
            'archived' => self::label('مؤرشف', 'Archived'),
        ];
    }

    public static function employmentTypeOptions(): array
    {
        return [
            'full_time' => self::label('دوام كامل', 'Full time'),
            'part_time' => self::label('دوام جزئي', 'Part time'),
            'visiting' => self::label('زائر/متعاون', 'Visiting'),
        ];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? $status;
    }

    public static function employmentTypeLabel(string $type): string
    {
        return self::employmentTypeOptions()[$type] ?? $type;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'on_leave' => 'warning',
            'archived' => 'gray',
            default => 'danger',
        };
    }

    private static function ltrText(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '—';
        }

        return '<span dir="ltr" style="unicode-bidi: plaintext; text-align: left; display: inline-block;">' . e($value) . '</span>';
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
