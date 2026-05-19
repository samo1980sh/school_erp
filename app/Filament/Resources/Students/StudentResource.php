<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\ManageStudents;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolSection;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $slug = 'students';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('طالب', 'Student');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('الطلاب', 'Students');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('الطلاب', 'Students');
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
        return auth()->user()?->can('students.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('students.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('students.update') ?? false;
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
                Section::make(self::label('بيانات الطالب الأساسية', 'Basic student information'))
                    ->description(self::label(
                        'البيانات الشخصية الأساسية للطالب كما ستظهر في السجلات والتقارير.',
                        'The student basic personal information as shown in records and reports.'
                    ))
                    ->schema([
                        TextInput::make('first_name')
                            ->label(self::label('الاسم الأول', 'First name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('father_name')
                            ->label(self::label('اسم الأب', 'Father name'))
                            ->maxLength(255),

                        TextInput::make('mother_name')
                            ->label(self::label('اسم الأم', 'Mother name'))
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

                        DatePicker::make('birth_date')
                            ->label(self::label('تاريخ الميلاد', 'Birth date'))
                            ->native(false),

                        TextInput::make('place_of_birth')
                            ->label(self::label('مكان الولادة', 'Place of birth'))
                            ->maxLength(255),

                        TextInput::make('national_id')
                            ->label(self::label('الرقم الوطني', 'National ID'))
                            ->unique(table: 'students', column: 'national_id', ignoreRecord: true)
                            ->maxLength(255),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ]),

                Section::make(self::label('القيد الدراسي الحالي', 'Current academic placement'))
                    ->description(self::label(
                        'هذه بيانات ربط تشغيلية مؤقتة تساعد الإدارة على تصنيف الطالب حتى يتم بناء قسم التسجيل والانتساب لاحقًا.',
                        'This temporary operational link helps administration classify the student until the enrollment module is built later.'
                    ))
                    ->schema([
                        TextInput::make('student_number')
                            ->label(self::label('الرقم المدرسي', 'Student number'))
                            ->required()
                            ->unique(table: 'students', column: 'student_number', ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText(self::label(
                                'رقم فريد للطالب داخل المدرسة ويستخدم في البحث والاستيراد والتصدير.',
                                'A unique school student number used for search, import, and export.'
                            )),

                        DatePicker::make('enrollment_date')
                            ->label(self::label('تاريخ التسجيل', 'Enrollment date'))
                            ->native(false),

                        Select::make('current_academic_year_id')
                            ->label(self::label('السنة الدراسية', 'Academic year'))
                            ->options(fn (): array => self::academicYearOptions())
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('current_grade_id')
                            ->label(self::label('الصف الدراسي', 'Grade'))
                            ->options(fn (): array => self::gradeOptions())
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('current_section_id')
                            ->label(self::label('الشعبة الحالية', 'Current section'))
                            ->options(fn (): array => self::sectionOptions())
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('active')
                            ->required()
                            ->native(false),

                        Toggle::make('is_active')
                            ->label(self::label('نشط', 'Active'))
                            ->default(true),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),

                Section::make(self::label('التواصل والملاحظات', 'Contact and notes'))
                    ->description(self::label(
                        'معلومات تواصل أولية وملاحظات صحية وإدارية مختصرة تخص الطالب.',
                        'Basic contact information and brief health or administrative notes for the student.'
                    ))
                    ->schema([
                        TextInput::make('phone')
                            ->label(self::label('الهاتف', 'Phone'))
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(self::label('البريد الإلكتروني', 'Email'))
                            ->email()
                            ->maxLength(255),

                        TextInput::make('blood_type')
                            ->label(self::label('زمرة الدم', 'Blood type'))
                            ->maxLength(10),

                        TextInput::make('address')
                            ->label(self::label('العنوان', 'Address'))
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('medical_notes')
                            ->label(self::label('ملاحظات صحية', 'Medical notes'))
                            ->rows(4)
                            ->maxLength(1000),

                        Textarea::make('notes')
                            ->label(self::label('ملاحظات إدارية', 'Administrative notes'))
                            ->rows(4)
                            ->maxLength(1000),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->with([
                        'academicYear:id,name,code',
                        'grade.educationalStage:id,name',
                        'section:id,name,code',
                    ])
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('student_number')
                    ->label(self::label('الرقم المدرسي', 'Student number'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label(self::label('الطالب', 'Student'))
                    ->state(fn (Student $record): string => $record->display_name)
                    ->searchable(['first_name', 'father_name', 'mother_name', 'last_name', 'full_name', 'student_number', 'national_id'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('full_name', $direction))
                    ->weight('bold')
                    ->description(fn (Student $record): ?string => $record->national_id),

                TextColumn::make('gender')
                    ->label(self::label('الجنس', 'Gender'))
                    ->formatStateUsing(fn (?string $state): string => self::genderLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'female' ? 'warning' : 'primary'),

                TextColumn::make('academicYear.name')
                    ->label(self::label('السنة', 'Year'))
                    ->toggleable(),

                TextColumn::make('grade.name')
                    ->label(self::label('الصف', 'Grade'))
                    ->description(fn (Student $record): ?string => $record->grade?->educationalStage?->name)
                    ->toggleable(),

                TextColumn::make('section.name')
                    ->label(self::label('الشعبة', 'Section'))
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state))
                    ->sortable(),

                TextColumn::make('phone')
                    ->label(self::label('الهاتف', 'Phone'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(self::label('تاريخ الإنشاء', 'Created at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('current_academic_year_id')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->options(fn (): array => self::academicYearOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('current_grade_id')
                    ->label(self::label('الصف الدراسي', 'Grade'))
                    ->options(fn (): array => self::gradeOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('current_section_id')
                    ->label(self::label('الشعبة', 'Section'))
                    ->options(fn (): array => self::sectionOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('gender')
                    ->label(self::label('الجنس', 'Gender'))
                    ->options(self::genderOptions())
                    ->native(false),

                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions())
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label(self::label('نشط', 'Active')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalHeading(fn (Student $record): string => self::label('تعديل الطالب: ', 'Edit student: ') . $record->display_name)
                    ->visible(fn (Student $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label('تم تحديث الطالب بنجاح', 'Student updated successfully')),
            ])
            ->emptyStateHeading(self::label('لا يوجد طلاب', 'No students found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإضافة طالب جديد أو استيراد الطلاب من ملف Excel وفق القالب المعتمد.',
                'Start by creating a student or importing students from the approved Excel template.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudents::route('/'),
        ];
    }

    public static function academicYearOptions(): array
    {
        return AcademicYear::query()
            ->orderBy('sort_order')
            ->orderByDesc('starts_on')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function gradeOptions(): array
    {
        return Grade::query()
            ->with('educationalStage:id,name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Grade $grade): array => [
                $grade->id => trim(implode(' - ', array_filter([
                    $grade->educationalStage?->name,
                    $grade->name,
                ]))),
            ])
            ->toArray();
    }

    public static function sectionOptions(): array
    {
        return SchoolSection::query()
            ->with(['academicYear:id,name', 'grade:id,name'])
            ->where('status', 'active')
            ->orderBy('academic_year_id')
            ->orderBy('grade_id')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (SchoolSection $section): array => [
                $section->id => trim(implode(' - ', array_filter([
                    $section->academicYear?->name,
                    $section->grade?->name,
                    $section->name,
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

    public static function genderLabel(string $gender): string
    {
        return self::genderOptions()[$gender] ?? $gender;
    }

    public static function statusOptions(): array
    {
        return [
            'active' => self::label('نشط', 'Active'),
            'transferred' => self::label('منقول', 'Transferred'),
            'withdrawn' => self::label('منسحب', 'Withdrawn'),
            'graduated' => self::label('متخرج', 'Graduated'),
            'suspended' => self::label('موقوف', 'Suspended'),
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
            'transferred' => 'info',
            'graduated' => 'primary',
            'suspended' => 'danger',
            default => 'warning',
        };
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
