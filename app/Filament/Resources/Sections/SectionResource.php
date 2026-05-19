<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sections;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Filament\Resources\Sections\Pages\ManageSections;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolSection;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SectionResource extends Resource
{
    protected static ?string $model = SchoolSection::class;

    protected static ?string $slug = 'sections';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('شعبة دراسية', 'Section');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('الشعب الدراسية', 'Sections');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('الشعب الدراسية', 'Sections');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('الهيكل الأكاديمي', 'Academic Structure');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('sections.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('sections.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('sections.update') ?? false;
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
                FormSection::make(self::label('تنظيم الشعبة', 'Section organization'))
                    ->description(self::label(
                        'اربط الشعبة بالسنة الدراسية والصف وحدد حالتها وترتيب ظهورها.',
                        'Assign the section to an academic year and grade, then set its status and display order.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required()
                            ->helperText(self::label(
                                'يظهر أولًا في النموذج حسب قاعدة المشروع. اترك فراغات بين الأرقام لتسهيل الإدراج لاحقًا.',
                                'Shown first by project rule. Leave gaps between numbers for easier future insertion.'
                            )),

                        Select::make('academic_year_id')
                            ->label(self::label('السنة الدراسية', 'Academic year'))
                            ->options(fn (): array => self::academicYearOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

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

                FormSection::make(self::label('بيانات الشعبة', 'Section details'))
                    ->description(self::label(
                        'حدد الصف الدراسي والقاعة والبيانات التشغيلية الخاصة بالشعبة.',
                        'Set the grade, classroom, and operational data for the section.'
                    ))
                    ->schema([
                        Select::make('grade_id')
                            ->label(self::label('الصف الدراسي', 'Grade'))
                            ->options(fn (): array => self::gradeOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('classroom_id')
                            ->label(self::label('القاعة', 'Classroom'))
                            ->options(fn (): array => self::classroomOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText(self::label(
                                'يمكن تركها فارغة مؤقتًا إذا لم يتم توزيع القاعات بعد.',
                                'Can be left empty temporarily if classrooms have not been assigned yet.'
                            )),

                        TextInput::make('name')
                            ->label(self::label('اسم الشعبة', 'Section name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(self::label('الشعبة أ', 'Section A'))
                            ->autofocus(),

                        TextInput::make('code')
                            ->label(self::label('رمز الشعبة', 'Section code'))
                            ->required()
                            ->unique(table: 'sections', column: 'code', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('AY-2025-2026-G01-A')
                            ->helperText(self::label(
                                'رمز فريد للشعبة داخل السنة الدراسية ويستخدم في الربط والتقارير.',
                                'A unique section code within the academic year used for linking and reports.'
                            )),

                        TextInput::make('capacity')
                            ->label(self::label('السعة', 'Capacity'))
                            ->numeric()
                            ->rules(['nullable', 'integer', 'min:1', 'max:1000']),

                        Select::make('gender_policy')
                            ->label(self::label('نوع الشعبة', 'Section type'))
                            ->options(self::genderPolicyOptions())
                            ->default('mixed')
                            ->required()
                            ->native(false),

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
                        'academicYear:id,name,code,sort_order,starts_on',
                        'grade.educationalStage:id,name',
                        'classroom:id,name,code',
                    ])
                    ->orderBy('academic_year_id')
                    ->orderBy('grade_id')
                    ->orderBy('sort_order')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(self::label('الشعبة', 'Section'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (SchoolSection $record): string => (string) $record->code),

                TextColumn::make('academicYear.name')
                    ->label(self::label('السنة', 'Year'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grade.name')
                    ->label(self::label('الصف', 'Grade'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (SchoolSection $record): ?string => $record->grade?->educationalStage?->name),

                TextColumn::make('classroom.name')
                    ->label(self::label('القاعة', 'Classroom'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('capacity')
                    ->label(self::label('السعة', 'Capacity'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('gender_policy')
                    ->label(self::label('النوع', 'Type'))
                    ->formatStateUsing(fn (?string $state): string => self::genderPolicyLabel((string) $state))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

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

                TextColumn::make('updated_at')
                    ->label(self::label('آخر تحديث', 'Updated at'))
                    ->dateTime('Y-m-d H:i')
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

                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions())
                    ->native(false),

                SelectFilter::make('gender_policy')
                    ->label(self::label('نوع الشعبة', 'Section type'))
                    ->options(self::genderPolicyOptions())
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (SchoolSection $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث الشعبة بنجاح',
                        'Section updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد شعب دراسية', 'No sections found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإنشاء شعبة أو شغّل ClassroomSectionSeeder لإضافة بيانات تجريبية.',
                'Create a section or run ClassroomSectionSeeder to add demo data.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSections::route('/'),
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

    public static function classroomOptions(): array
    {
        return Classroom::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->id => trim(sprintf('%s - %s', $classroom->name, $classroom->code)),
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

    public static function genderPolicyOptions(): array
    {
        return [
            'mixed' => self::label('مختلطة', 'Mixed'),
            'boys' => self::label('ذكور', 'Boys'),
            'girls' => self::label('إناث', 'Girls'),
        ];
    }

    public static function genderPolicyLabel(string $policy): string
    {
        return self::genderPolicyOptions()[$policy] ?? $policy;
    }

    private static function nextSortOrder(): int
    {
        return ((int) SchoolSection::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
