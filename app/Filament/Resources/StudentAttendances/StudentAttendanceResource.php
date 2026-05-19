<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentAttendances;

use App\Filament\Resources\StudentAttendances\Pages\ManageStudentAttendances;
use App\Models\AcademicYear;
use App\Models\SchoolSection;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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

class StudentAttendanceResource extends Resource
{
    protected static ?string $model = StudentAttendance::class;

    protected static ?string $slug = 'student-attendances';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'display_title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('سجل حضور', 'Attendance record');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('الحضور والغياب', 'Attendance');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('حضور الطلاب', 'Student attendance');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('الحضور والدوام', 'Attendance');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('attendance.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('attendance.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('attendance.update') ?? false;
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
                Section::make(self::label('بيانات الحضور', 'Attendance details'))
                    ->description(self::label(
                        'اختر تسجيل الطالب والتاريخ. سيتم تعبئة السنة والصف والشعبة تلقائيًا من تسجيل الطالب.',
                        'Select the student enrollment and date. Year, grade, and section are filled automatically from the enrollment.'
                    ))
                    ->schema([
                        DatePicker::make('attendance_date')
                            ->label(self::label('تاريخ الحضور', 'Attendance date'))
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Select::make('student_enrollment_id')
                            ->label(self::label('تسجيل الطالب', 'Student enrollment'))
                            ->options(fn (): array => self::enrollmentOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('present')
                            ->required()
                            ->native(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                    ]),

                Section::make(self::label('الوقت والتأخير', 'Time and lateness'))
                    ->description(self::label(
                        'استخدم هذه الحقول عند تسجيل الحضور التفصيلي أو حالات التأخير.',
                        'Use these fields for detailed attendance tracking or late arrivals.'
                    ))
                    ->schema([
                        TimePicker::make('arrival_time')
                            ->label(self::label('وقت الوصول', 'Arrival time'))
                            ->seconds(false),

                        TimePicker::make('departure_time')
                            ->label(self::label('وقت المغادرة', 'Departure time'))
                            ->seconds(false),

                        TextInput::make('minutes_late')
                            ->label(self::label('دقائق التأخير', 'Late minutes'))
                            ->numeric()
                            ->rules(['integer', 'min:0', 'max:600'])
                            ->default(0)
                            ->extraInputAttributes(self::ltrAttributes()),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                    ]),

                Section::make(self::label('الملاحظات والعذر', 'Notes and excuse'))
                    ->description(self::label(
                        'سجل سبب العذر أو أي ملاحظات مرتبطة بحالة الحضور.',
                        'Record the excuse reason or any notes related to this attendance status.'
                    ))
                    ->schema([
                        TextInput::make('excuse_reason')
                            ->label(self::label('سبب العذر', 'Excuse reason'))
                            ->maxLength(255),

                        Textarea::make('notes')
                            ->label(self::label('ملاحظات', 'Notes'))
                            ->rows(4)
                            ->maxLength(1000),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->with([
                        'student:id,student_number,first_name,last_name',
                        'studentEnrollment:id,enrollment_number',
                        'academicYear:id,name',
                        'grade:id,name',
                        'section:id,name',
                    ])
                    ->orderByDesc('attendance_date')
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('attendance_date')
                    ->label(self::label('التاريخ', 'Date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('student.student_number')
                    ->label(self::label('رقم الطالب', 'Student number'))
                    ->extraAttributes(self::ltrAttributes())
                    ->copyable()
                    ->searchable(),

                TextColumn::make('student.first_name')
                    ->label(self::label('الطالب', 'Student'))
                    ->state(fn (StudentAttendance $record): string => trim((string) (($record->student?->first_name ?? '') . ' ' . ($record->student?->last_name ?? ''))))
                    ->searchable(['students.first_name', 'students.last_name'])
                    ->weight('bold'),

                TextColumn::make('studentEnrollment.enrollment_number')
                    ->label(self::label('رقم التسجيل', 'Enrollment number'))
                    ->extraAttributes(self::ltrAttributes())
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('academicYear.name')
                    ->label(self::label('السنة', 'Year'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('grade.name')
                    ->label(self::label('الصف', 'Grade'))
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

                TextColumn::make('arrival_time')
                    ->label(self::label('الوصول', 'Arrival'))
                    ->extraAttributes(self::ltrAttributes())
                    ->toggleable(),

                TextColumn::make('minutes_late')
                    ->label(self::label('التأخير', 'Late'))
                    ->extraAttributes(self::ltrAttributes())
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->options(fn (): array => AcademicYear::query()->orderBy('sort_order')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('section_id')
                    ->label(self::label('الشعبة', 'Section'))
                    ->options(fn (): array => SchoolSection::query()->orderBy('sort_order')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions())
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalHeading(fn (StudentAttendance $record): string => self::label('تعديل سجل حضور: ', 'Edit attendance: ') . $record->display_title)
                    ->visible(fn (StudentAttendance $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث سجل الحضور بنجاح',
                        'Attendance record updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد سجلات حضور', 'No attendance records found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإضافة سجل حضور أو استيراد ملف Excel.',
                'Start by adding an attendance record or importing an Excel file.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentAttendances::route('/'),
        ];
    }

    public static function enrollmentOptions(): array
    {
        return StudentEnrollment::query()
            ->with(['student:id,student_number,first_name,last_name', 'academicYear:id,name', 'grade:id,name', 'section:id,name'])
            ->orderByDesc('id')
            ->limit(1000)
            ->get()
            ->mapWithKeys(fn (StudentEnrollment $enrollment): array => [
                $enrollment->id => trim(implode(' - ', array_filter([
                    $enrollment->student?->student_number,
                    trim((string) (($enrollment->student?->first_name ?? '') . ' ' . ($enrollment->student?->last_name ?? ''))),
                    $enrollment->academicYear?->name,
                    $enrollment->grade?->name,
                    $enrollment->section?->name,
                ]))),
            ])
            ->toArray();
    }

    public static function statusOptions(): array
    {
        return [
            'present' => self::label('حاضر', 'Present'),
            'absent' => self::label('غائب', 'Absent'),
            'late' => self::label('متأخر', 'Late'),
            'excused' => self::label('غياب بعذر', 'Excused'),
        ];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? $status;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'present' => 'success',
            'late' => 'warning',
            'excused' => 'info',
            'absent' => 'danger',
            default => 'gray',
        };
    }

    private static function ltrAttributes(): array
    {
        return [
            'dir' => 'ltr',
            'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;',
        ];
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
