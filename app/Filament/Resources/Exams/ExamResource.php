<?php

declare(strict_types=1);

namespace App\Filament\Resources\Exams;

use App\Filament\Resources\Exams\Pages\ManageExams;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Subject;
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

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 80;

    protected static ?string $recordTitleAttribute = 'display_title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('اختبار', 'Exam');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('الاختبارات', 'Exams');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('الاختبارات', 'Exams');
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
        return auth()->user()?->can('exams.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('exams.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('exams.update') ?? false;
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
                Section::make(self::label('ارتباط الاختبار', 'Exam relation'))
                    ->description(self::label(
                        'اربط الاختبار بالسنة والفصل والصف والمادة الدراسية.',
                        'Link the exam to the academic year, term, grade, and subject.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required(),

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
                            ->required()
                            ->native(false),

                        Select::make('grade_id')
                            ->label(self::label('الصف الدراسي', 'Grade'))
                            ->options(fn (): array => self::gradeOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('subject_id')
                            ->label(self::label('المادة الدراسية', 'Subject'))
                            ->options(fn (): array => self::subjectOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 5]),

                Section::make(self::label('بيانات الاختبار', 'Exam details'))
                    ->description(self::label(
                        'حدد اسم الاختبار وكوده ونوعه وتاريخه وقيم الدرجات.',
                        'Define the exam name, code, type, date, and grading values.'
                    ))
                    ->schema([
                        TextInput::make('name')
                            ->label(self::label('اسم الاختبار', 'Exam name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label(self::label('كود الاختبار', 'Exam code'))
                            ->required()
                            ->unique(table: 'exams', column: 'code', ignoreRecord: true)
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;']),

                        Select::make('exam_type')
                            ->label(self::label('نوع الاختبار', 'Exam type'))
                            ->options(self::examTypeOptions())
                            ->default('monthly')
                            ->required()
                            ->native(false),

                        DatePicker::make('exam_date')
                            ->label(self::label('تاريخ الاختبار', 'Exam date'))
                            ->native(false),

                        TextInput::make('max_mark')
                            ->label(self::label('الدرجة العظمى', 'Maximum mark'))
                            ->numeric()
                            ->rules(['numeric', 'min:1'])
                            ->default(100)
                            ->required(),

                        TextInput::make('passing_mark')
                            ->label(self::label('درجة النجاح', 'Passing mark'))
                            ->numeric()
                            ->rules(['numeric', 'min:0'])
                            ->default(50)
                            ->required(),

                        TextInput::make('weight_percent')
                            ->label(self::label('وزن الاختبار %', 'Exam weight %'))
                            ->numeric()
                            ->rules(['numeric', 'min:0', 'max:100'])
                            ->default(100)
                            ->helperText(self::label(
                                'يستخدم لاحقًا في احتساب النتائج الموزونة داخل الفصل الدراسي.',
                                'Used later for weighted term result calculations.'
                            )),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('planned')
                            ->required()
                            ->native(false),

                        Textarea::make('notes')
                            ->label(self::label('ملاحظات', 'Notes'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 4]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['academicYear:id,name', 'academicTerm:id,name', 'grade:id,name', 'subject:id,name,code'])
                ->withCount('marks')
                ->orderByDesc('exam_date')
                ->orderBy('sort_order'))
            ->columns([
                TextColumn::make('code')
                    ->label(self::label('الكود', 'Code'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;']),

                TextColumn::make('name')
                    ->label(self::label('الاختبار', 'Exam'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Exam $record): ?string => $record->subject?->name),

                TextColumn::make('grade.name')
                    ->label(self::label('الصف', 'Grade'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('academicTerm.name')
                    ->label(self::label('الفصل', 'Term'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('exam_date')
                    ->label(self::label('التاريخ', 'Date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('max_mark')
                    ->label(self::label('العظمى', 'Max'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('marks_count')
                    ->label(self::label('الدرجات', 'Marks'))
                    ->badge()
                    ->color('success')
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
                SelectFilter::make('academic_year_id')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->options(fn (): array => self::academicYearOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('academic_term_id')
                    ->label(self::label('الفصل الدراسي', 'Academic term'))
                    ->options(fn (): array => self::academicTermOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('grade_id')
                    ->label(self::label('الصف الدراسي', 'Grade'))
                    ->options(fn (): array => self::gradeOptions())
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
                    ->modalHeading(fn (Exam $record): string => self::label('تعديل اختبار: ', 'Edit exam: ') . $record->display_title)
                    ->visible(fn (Exam $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label('تم تحديث الاختبار بنجاح', 'Exam updated successfully')),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return ['index' => ManageExams::route('/')];
    }

    public static function academicYearOptions(): array
    {
        return AcademicYear::query()->orderBy('sort_order')->orderByDesc('starts_on')->pluck('name', 'id')->toArray();
    }

    public static function academicTermOptions(): array
    {
        return AcademicTerm::query()->with('academicYear:id,name')->orderBy('academic_year_id')->orderBy('sort_order')->get()
            ->mapWithKeys(fn (AcademicTerm $term): array => [$term->id => trim(($term->academicYear?->name ? $term->academicYear->name . ' - ' : '') . $term->name)])
            ->toArray();
    }

    public static function gradeOptions(): array
    {
        return Grade::query()->with('educationalStage:id,name')->where('is_active', true)->orderBy('sort_order')->get()
            ->mapWithKeys(fn (Grade $grade): array => [$grade->id => trim(implode(' - ', array_filter([$grade->educationalStage?->name, $grade->name])))])
            ->toArray();
    }

    public static function subjectOptions(): array
    {
        return Subject::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->mapWithKeys(fn (Subject $subject): array => [$subject->id => trim($subject->name . ' - ' . $subject->code)])
            ->toArray();
    }

    public static function examTypeOptions(): array
    {
        return ['monthly' => self::label('شهري', 'Monthly'), 'midterm' => self::label('منتصف الفصل', 'Midterm'), 'final' => self::label('نهائي', 'Final'), 'quiz' => self::label('قصير', 'Quiz')];
    }

    public static function statusOptions(): array
    {
        return ['planned' => self::label('مخطط', 'Planned'), 'published' => self::label('منشور', 'Published'), 'closed' => self::label('مغلق', 'Closed'), 'cancelled' => self::label('ملغى', 'Cancelled')];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? $status;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) { 'published' => 'success', 'closed' => 'gray', 'cancelled' => 'danger', default => 'warning' };
    }

    private static function nextSortOrder(): int
    {
        return ((int) Exam::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
