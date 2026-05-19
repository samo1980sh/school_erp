<?php

declare(strict_types=1);

namespace App\Filament\Resources\GradeSubjects;

use App\Filament\Resources\GradeSubjects\Pages\ManageGradeSubjects;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeSubject;
use App\Models\Subject;
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

class GradeSubjectResource extends Resource
{
    protected static ?string $model = GradeSubject::class;

    protected static ?string $slug = 'grade-subjects';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 70;

    protected static ?string $recordTitleAttribute = 'display_title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('خطة مادة لصف', 'Grade subject plan');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('خطط مواد الصفوف', 'Grade subject plans');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('خطط مواد الصفوف', 'Grade subject plans');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('الهيكل الأكاديمي', 'Academic Settings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('subjects.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('subjects.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('subjects.update') ?? false;
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
                Section::make(self::label('ارتباط الخطة وترتيبها', 'Plan relation and order'))
                    ->description(self::label(
                        'اربط المادة بالسنة الدراسية والصف، وحدد ترتيب ظهورها في خطة الصف.',
                        'Link the subject to the academic year and grade, then set its display order in the grade plan.'
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
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ]),

                Section::make(self::label('إعدادات الخطة', 'Plan settings'))
                    ->description(self::label(
                        'حدد عدد الحصص الأسبوعية ومعامل المادة وطبيعتها ضمن خطة الصف.',
                        'Set weekly periods, subject coefficient, and the subject behavior inside the grade plan.'
                    ))
                    ->schema([
                        TextInput::make('weekly_periods')
                            ->label(self::label('الحصص الأسبوعية', 'Weekly periods'))
                            ->numeric()
                            ->rules(['nullable', 'integer', 'min:1', 'max:40'])
                            ->helperText(self::label(
                                'عدد الحصص الأسبوعية لهذه المادة ضمن الصف المحدد.',
                                'Number of weekly periods for this subject in the selected grade.'
                            )),

                        TextInput::make('coefficient')
                            ->label(self::label('معامل المادة', 'Subject coefficient'))
                            ->numeric()
                            ->rules(['numeric', 'min:0', 'max:99'])
                            ->default(1)
                            ->required()
                            ->helperText(self::label(
                                'يحدد وزن المادة عند احتساب المعدلات والنتائج. إذا كانت كل المواد متساوية، اترك القيمة 1.',
                                'Defines the subject weight when calculating averages and results. If all subjects have equal weight, keep it as 1.'
                            )),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('active')
                            ->required()
                            ->native(false),

                        Toggle::make('is_core')
                            ->label(self::label('مادة أساسية', 'Core subject'))
                            ->default(true),

                        Toggle::make('is_exam_subject')
                            ->label(self::label('تدخل في الاختبارات', 'Exam subject'))
                            ->default(true),

                        Textarea::make('notes')
                            ->label(self::label('ملاحظات', 'Notes'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->with([
                        'academicYear:id,name,code,sort_order,starts_on',
                        'grade.educationalStage:id,name',
                        'subject:id,name,code,category',
                    ])
                    ->orderBy('academic_year_id')
                    ->orderBy('grade_id')
                    ->orderBy('sort_order')
            )
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label(self::label('السنة', 'Year'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grade.name')
                    ->label(self::label('الصف', 'Grade'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (GradeSubject $record): ?string => $record->grade?->educationalStage?->name),

                TextColumn::make('subject.name')
                    ->label(self::label('المادة', 'Subject'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (GradeSubject $record): ?string => $record->subject?->code),

                TextColumn::make('weekly_periods')
                    ->label(self::label('الحصص', 'Periods'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('coefficient')
                    ->label(self::label('معامل المادة', 'Subject coefficient'))
                    ->alignCenter()
                    ->sortable()
                    ->tooltip(self::label(
                        'وزن المادة عند احتساب المعدلات والنتائج.',
                        'Subject weight when calculating averages and results.'
                    )),

                TextColumn::make('is_core')
                    ->label(self::label('أساسية', 'Core'))
                    ->state(fn (GradeSubject $record): string => $record->is_core
                        ? self::label('نعم', 'Yes')
                        : self::label('لا', 'No'))
                    ->badge()
                    ->color(fn (GradeSubject $record): string => $record->is_core ? 'success' : 'gray'),

                TextColumn::make('is_exam_subject')
                    ->label(self::label('اختبارات', 'Exams'))
                    ->state(fn (GradeSubject $record): string => $record->is_exam_subject
                        ? self::label('نعم', 'Yes')
                        : self::label('لا', 'No'))
                    ->badge()
                    ->color(fn (GradeSubject $record): string => $record->is_exam_subject ? 'primary' : 'gray'),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state))
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label(self::label('الترتيب', 'Order'))
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                SelectFilter::make('subject_id')
                    ->label(self::label('المادة الدراسية', 'Subject'))
                    ->options(fn (): array => self::subjectOptions())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions())
                    ->native(false),

                TernaryFilter::make('is_core')
                    ->label(self::label('مادة أساسية', 'Core subject')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalHeading(fn (GradeSubject $record): string => self::label(
                        'تعديل خطة مادة: ',
                        'Edit subject plan: '
                    ) . $record->display_title)
                    ->visible(fn (GradeSubject $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث خطة مادة الصف بنجاح',
                        'Grade subject plan updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد خطط مواد صفوف', 'No grade subject plans found'))
            ->emptyStateDescription(self::label(
                'ابدأ بربط المواد بالصفوف أو شغّل SubjectCurriculumSeeder لإضافة بيانات تجريبية.',
                'Start by assigning subjects to grades or run SubjectCurriculumSeeder to add demo data.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGradeSubjects::route('/'),
        ];
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

    public static function subjectOptions(): array
    {
        return Subject::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Subject $subject): array => [
                $subject->id => trim(sprintf('%s - %s', $subject->name, $subject->code)),
            ])
            ->toArray();
    }

    public static function statusOptions(): array
    {
        return [
            'active' => self::label('نشطة', 'Active'),
            'inactive' => self::label('غير نشطة', 'Inactive'),
            'archived' => self::label('مؤرشفة', 'Archived'),
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
            'archived' => 'gray',
            default => 'warning',
        };
    }

    private static function nextSortOrder(): int
    {
        return ((int) GradeSubject::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}