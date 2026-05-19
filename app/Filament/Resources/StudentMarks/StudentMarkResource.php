<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentMarks;

use App\Filament\Resources\StudentMarks\Pages\ManageStudentMarks;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\SchoolSection;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentMark;
use BackedEnum;
use Filament\Actions\EditAction;
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

class StudentMarkResource extends Resource
{
    protected static ?string $model = StudentMark::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'display_title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('درجة طالب', 'Student mark');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('درجات الطلاب', 'Student marks');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('درجات الطلاب', 'Student marks');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('التقييم والدرجات', 'Assessment and Marks');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marks.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('marks.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('marks.update') ?? false;
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
                Section::make(self::label('ارتباط الدرجة', 'Mark relation'))
                    ->description(self::label(
                        'حدد الاختبار والطالب. سيتم حفظ الدرجة ضمن السنة والفصل والصف والشعبة المرتبطة بالاختبار والتسجيل.',
                        'Choose the exam and student. The mark will be stored with the related year, term, grade, and section.'
                    ))
                    ->schema([
                        Select::make('exam_id')
                            ->label(self::label('الاختبار', 'Exam'))
                            ->options(fn (): array => self::examOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('student_id')
                            ->label(self::label('الطالب', 'Student'))
                            ->options(fn (): array => self::studentOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('student_enrollment_id')
                            ->label(self::label('تسجيل الطالب', 'Student enrollment'))
                            ->options(fn (): array => self::enrollmentOptions())
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns(['default' => 1, 'md' => 3]),

                Section::make(self::label('بيانات الدرجة', 'Mark details'))
                    ->description(self::label(
                        'أدخل الدرجة وحالتها. في حالات الغياب أو الإعفاء يمكن ترك الدرجة فارغة.',
                        'Enter the mark and status. For absent or exempt students, the mark can be left empty.'
                    ))
                    ->schema([
                        TextInput::make('mark')
                            ->label(self::label('الدرجة', 'Mark'))
                            ->numeric()
                            ->rules(['nullable', 'numeric', 'min:0']),

                        TextInput::make('max_mark')
                            ->label(self::label('الدرجة العظمى', 'Maximum mark'))
                            ->numeric()
                            ->rules(['numeric', 'min:1'])
                            ->default(100)
                            ->required(),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('draft')
                            ->required()
                            ->native(false),

                        Textarea::make('notes')
                            ->label(self::label('ملاحظات', 'Notes'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 3]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'exam:id,name,code,exam_type',
                    'student:id,student_number,first_name,father_name,last_name',
                    'academicYear:id,name',
                    'academicTerm:id,name',
                    'grade:id,name',
                    'section:id,name',
                    'subject:id,name,code',
                ])
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('exam.code')
                    ->label(self::label('كود الاختبار', 'Exam code'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;']),

                TextColumn::make('student.student_number')
                    ->label(self::label('رقم الطالب', 'Student number'))
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;']),

                TextColumn::make('student_name')
                    ->label(self::label('الطالب', 'Student'))
                    ->state(fn (StudentMark $record): string => trim(implode(' ', array_filter([
                        $record->student?->first_name,
                        $record->student?->father_name,
                        $record->student?->last_name,
                    ]))) ?: '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('student', function (Builder $studentQuery) use ($search): void {
                            $studentQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('father_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('student_number', 'like', "%{$search}%");
                        });
                    })
                    ->weight('bold'),

                TextColumn::make('subject.name')
                    ->label(self::label('المادة', 'Subject'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grade.name')
                    ->label(self::label('الصف', 'Grade'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('section.name')
                    ->label(self::label('الشعبة', 'Section'))
                    ->toggleable(),

                TextColumn::make('mark')
                    ->label(self::label('الدرجة', 'Mark'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('max_mark')
                    ->label(self::label('العظمى', 'Max'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('exam_id')
                    ->label(self::label('الاختبار', 'Exam'))
                    ->options(fn (): array => self::examOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('grade_id')
                    ->label(self::label('الصف', 'Grade'))
                    ->options(fn (): array => Grade::query()->orderBy('sort_order')->pluck('name', 'id')->toArray())
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
                    ->modalHeading(fn (StudentMark $record): string => self::label('تعديل درجة: ', 'Edit mark: ') . $record->display_title)
                    ->visible(fn (StudentMark $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label('تم تحديث الدرجة بنجاح', 'Mark updated successfully')),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return ['index' => ManageStudentMarks::route('/')];
    }

    public static function examOptions(): array
    {
        return Exam::query()->with(['grade:id,name', 'subject:id,name'])->orderByDesc('exam_date')->limit(500)->get()
            ->mapWithKeys(fn (Exam $exam): array => [$exam->id => trim(implode(' - ', array_filter([$exam->code, $exam->subject?->name, $exam->grade?->name, $exam->name])))])
            ->toArray();
    }

    public static function studentOptions(): array
    {
        return Student::query()->orderBy('student_number')->limit(1000)->get()
            ->mapWithKeys(fn (Student $student): array => [$student->id => trim(implode(' - ', array_filter([$student->student_number, $student->first_name, $student->father_name, $student->last_name])))])
            ->toArray();
    }

    public static function enrollmentOptions(): array
    {
        return StudentEnrollment::query()->with(['student:id,student_number,first_name,last_name', 'academicYear:id,name', 'grade:id,name', 'section:id,name'])->orderByDesc('id')->limit(1000)->get()
            ->mapWithKeys(fn (StudentEnrollment $enrollment): array => [$enrollment->id => trim(implode(' - ', array_filter([
                $enrollment->student?->student_number,
                $enrollment->student?->first_name,
                $enrollment->student?->last_name,
                $enrollment->academicYear?->name,
                $enrollment->grade?->name,
                $enrollment->section?->name,
            ])))])
            ->toArray();
    }

    public static function statusOptions(): array
    {
        return ['draft' => self::label('مسودة', 'Draft'), 'final' => self::label('نهائية', 'Final'), 'absent' => self::label('غائب', 'Absent'), 'exempt' => self::label('معفى', 'Exempt')];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? $status;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) { 'final' => 'success', 'absent' => 'danger', 'exempt' => 'gray', default => 'warning' };
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
