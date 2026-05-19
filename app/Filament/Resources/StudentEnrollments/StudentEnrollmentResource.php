<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentEnrollments;

use App\Filament\Resources\StudentEnrollments\Pages\ManageStudentEnrollments;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolSection;
use App\Models\Student;
use App\Models\StudentEnrollment;
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
use Illuminate\Support\HtmlString;
use UnitEnum;

class StudentEnrollmentResource extends Resource
{
    protected static ?string $model = StudentEnrollment::class;

    protected static ?string $slug = 'student-enrollments';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'display_title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('تسجيل طالب', 'Student enrollment');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('تسجيل الطلاب', 'Student enrollments');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('تسجيل الطلاب', 'Student enrollments');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('التسجيل والانتساب', 'Registration and Enrollment');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('enrollments.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('enrollments.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('enrollments.update') ?? false;
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
                Section::make(self::label('بيانات التسجيل الأساسية', 'Basic enrollment information'))
                    ->description(self::label(
                        'حدد الطالب والسنة الدراسية والصف والشعبة التي سيتم ربط الطالب بها.',
                        'Select the student, academic year, grade, and section for this enrollment record.'
                    ))
                    ->schema([
                        TextInput::make('enrollment_number')
                            ->label(self::label('رقم التسجيل', 'Enrollment number'))
                            ->required()
                            ->unique(table: 'student_enrollments', column: 'enrollment_number', ignoreRecord: true)
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left;'])
                            ->placeholder('ENR-2025-0001')
                            ->helperText(self::label(
                                'رقم فريد لعملية تسجيل الطالب في سنة دراسية محددة.',
                                'A unique number for the student enrollment in a specific academic year.'
                            )),

                        Select::make('student_id')
                            ->label(self::label('الطالب', 'Student'))
                            ->options(fn (): array => self::studentOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('academic_year_id')
                            ->label(self::label('السنة الدراسية', 'Academic year'))
                            ->options(fn (): array => self::academicYearOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('academic_term_id')
                            ->label(self::label('الفصل الدراسي', 'Academic term'))
                            ->options(fn (): array => self::academicTermOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText(self::label(
                                'يمكن تركه فارغًا عند تسجيل الطالب على مستوى السنة الدراسية فقط.',
                                'Can be left empty when enrolling the student at academic-year level only.'
                            )),

                        Select::make('grade_id')
                            ->label(self::label('الصف الدراسي', 'Grade'))
                            ->options(fn (): array => self::gradeOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('section_id')
                            ->label(self::label('الشعبة', 'Section'))
                            ->options(fn (): array => self::sectionOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),

                Section::make(self::label('حالة التسجيل', 'Enrollment status'))
                    ->description(self::label(
                        'حدد تاريخ التسجيل ونوعه وحالة الطالب الحالية ضمن هذه السنة الدراسية.',
                        'Set the enrollment date, type, and the student status for this academic year.'
                    ))
                    ->schema([
                        DatePicker::make('enrollment_date')
                            ->label(self::label('تاريخ التسجيل', 'Enrollment date'))
                            ->default(now())
                            ->required()
                            ->native(false),

                        Select::make('enrollment_type')
                            ->label(self::label('نوع التسجيل', 'Enrollment type'))
                            ->options(self::enrollmentTypeOptions())
                            ->default('new')
                            ->required()
                            ->native(false),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('enrolled')
                            ->required()
                            ->native(false),

                        Toggle::make('is_current')
                            ->label(self::label('تسجيل حالي', 'Current enrollment'))
                            ->default(true)
                            ->helperText(self::label(
                                'فعّلها للسجل الذي يمثل الوضع الدراسي الحالي للطالب.',
                                'Enable this for the record that represents the student current academic placement.'
                            )),

                        TextInput::make('previous_school')
                            ->label(self::label('المدرسة السابقة', 'Previous school'))
                            ->maxLength(255)
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),

                        Textarea::make('notes')
                            ->label(self::label('ملاحظات', 'Notes'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
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
                        'student',
                        'academicYear:id,name,code,starts_on,sort_order',
                        'academicTerm:id,name,code,sort_order',
                        'grade.educationalStage:id,name',
                        'section:id,name,code,grade_id,academic_year_id',
                    ])
                    ->orderByDesc('is_current')
                    ->orderByDesc('enrollment_date')
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('enrollment_number')
                    ->label(self::label('رقم التسجيل', 'Enrollment no.'))
                    ->formatStateUsing(fn (mixed $state): HtmlString => self::ltrText($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student_name')
                    ->label(self::label('الطالب', 'Student'))
                    ->state(fn (StudentEnrollment $record): string => StudentEnrollment::studentDisplayName($record->student))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('student', function (Builder $studentQuery) use ($search): void {
                            $studentQuery
                                ->where('student_number', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('father_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->weight('bold')
                    ->description(fn (StudentEnrollment $record): HtmlString => self::ltrText($record->student?->student_number)),

                TextColumn::make('academicYear.name')
                    ->label(self::label('السنة', 'Year'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('grade.name')
                    ->label(self::label('الصف', 'Grade'))
                    ->sortable()
                    ->description(fn (StudentEnrollment $record): ?string => $record->grade?->educationalStage?->name),

                TextColumn::make('section.name')
                    ->label(self::label('الشعبة', 'Section'))
                    ->description(fn (StudentEnrollment $record): ?string => $record->section?->code),

                TextColumn::make('enrollment_date')
                    ->label(self::label('تاريخ التسجيل', 'Enrollment date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state))
                    ->sortable(),

                TextColumn::make('is_current')
                    ->label(self::label('حالي', 'Current'))
                    ->state(fn (StudentEnrollment $record): string => $record->is_current ? self::label('نعم', 'Yes') : self::label('لا', 'No'))
                    ->badge()
                    ->color(fn (StudentEnrollment $record): string => $record->is_current ? 'success' : 'gray')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->options(fn (): array => self::academicYearOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('grade_id')
                    ->label(self::label('الصف الدراسي', 'Grade'))
                    ->options(fn (): array => self::gradeOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('section_id')
                    ->label(self::label('الشعبة', 'Section'))
                    ->options(fn (): array => self::sectionOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions())
                    ->native(false),

                TernaryFilter::make('is_current')
                    ->label(self::label('تسجيل حالي', 'Current enrollment')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalHeading(fn (StudentEnrollment $record): string => self::label(
                        'تعديل تسجيل طالب: ',
                        'Edit student enrollment: '
                    ) . $record->display_title)
                    ->visible(fn (StudentEnrollment $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث تسجيل الطالب بنجاح',
                        'Student enrollment updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد سجلات تسجيل', 'No enrollment records found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإضافة تسجيل طالب أو استخدم قالب Excel للاستيراد الجماعي.',
                'Start by adding an enrollment record or use the Excel template for bulk import.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentEnrollments::route('/'),
        ];
    }

    public static function studentOptions(): array
    {
        return Student::query()
            ->orderBy('student_number')
            ->limit(1000)
            ->get()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => trim(implode(' - ', array_filter([
                    StudentEnrollment::studentDisplayName($student),
                    (string) ($student->student_number ?? ''),
                ]))),
            ])
            ->toArray();
    }

    public static function academicYearOptions(): array
    {
        return AcademicYear::query()
            ->orderBy('sort_order')
            ->orderByDesc('starts_on')
            ->get()
            ->mapWithKeys(fn (AcademicYear $year): array => [
                $year->id => (string) ($year->display_name ?? $year->name),
            ])
            ->toArray();
    }

    public static function academicTermOptions(): array
    {
        return AcademicTerm::query()
            ->with('academicYear:id,name')
            ->orderBy('academic_year_id')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (AcademicTerm $term): array => [
                $term->id => trim(implode(' - ', array_filter([
                    $term->academicYear?->name,
                    $term->name,
                ]))),
            ])
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
            ->with([
                'academicYear:id,name',
                'grade:id,name',
            ])
            ->where('is_active', true)
            ->orderBy('academic_year_id')
            ->orderBy('grade_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (SchoolSection $section): array => [
                $section->id => trim(implode(' - ', array_filter([
                    $section->academicYear?->name,
                    $section->grade?->name,
                    $section->name,
                    $section->code,
                ]))),
            ])
            ->toArray();
    }

    public static function enrollmentTypeOptions(): array
    {
        return [
            'new' => self::label('طالب جديد', 'New student'),
            'returning' => self::label('طالب مستمر', 'Returning student'),
            'transfer' => self::label('منقول من مدرسة أخرى', 'Transfer student'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'enrolled' => self::label('مسجل', 'Enrolled'),
            'transferred' => self::label('منقول', 'Transferred'),
            'withdrawn' => self::label('منسحب', 'Withdrawn'),
            'suspended' => self::label('موقوف', 'Suspended'),
            'graduated' => self::label('متخرج', 'Graduated'),
        ];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? $status;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'enrolled' => 'success',
            'graduated' => 'primary',
            'transferred' => 'warning',
            'withdrawn', 'suspended' => 'danger',
            default => 'gray',
        };
    }

    private static function ltrText(mixed $value): HtmlString
    {
        $value = trim((string) $value);

        if ($value === '') {
            return new HtmlString('—');
        }

        return new HtmlString('<span dir="ltr" style="unicode-bidi: plaintext; text-align: left; display: inline-block;">' . e($value) . '</span>');
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
