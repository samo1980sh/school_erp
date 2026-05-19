<?php

declare(strict_types=1);

namespace App\Filament\Resources\Grades;

use App\Filament\Resources\Grades\Pages\ManageGrades;
use App\Models\EducationalStage;
use App\Models\Grade;
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

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('صف دراسي', 'Grade');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('الصفوف الدراسية', 'Grades');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('الصفوف الدراسية', 'Grades');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('الإعدادات الأكاديمية', 'Academic Settings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('grades.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('grades.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('grades.update') ?? false;
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
                Section::make(self::label('ارتباط الصف وترتيبه', 'Grade relation and order'))
                    ->description(self::label(
                        'اربط الصف بالمرحلة التعليمية الصحيحة وحدد ترتيبه. هذا الارتباط سيُستخدم لاحقًا في الشعب والتسجيل والمواد والتقارير.',
                        'Link the grade to the correct educational stage and set its order. This relation will later be used in sections, enrollment, subjects, and reports.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required(),

                        Select::make('educational_stage_id')
                            ->label(self::label('المرحلة التعليمية', 'Educational stage'))
                            ->options(fn (): array => self::stageOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),

                        Toggle::make('is_active')
                            ->label(self::label('مفعل', 'Active'))
                            ->default(true),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),

                Section::make(self::label('بيانات الصف الدراسي', 'Grade details'))
                    ->description(self::label(
                        'عرّف اسم الصف ورمزه ورقمه التعليمي. الرمز التقني يساعد في الاستيراد والتقارير والتكاملات المستقبلية.',
                        'Define the grade name, code, and numeric level. The technical code helps with imports, reports, and future integrations.'
                    ))
                    ->schema([
                        TextInput::make('name')
                            ->label(self::label('اسم الصف', 'Grade name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('code')
                            ->label(self::label('رمز الصف', 'Grade code'))
                            ->required()
                            ->unique(table: 'grades', column: 'code', ignoreRecord: true)
                            ->rules(['regex:/^[A-Z0-9_-]+$/'])
                            ->maxLength(255)
                            ->placeholder('GRADE-01'),

                        TextInput::make('grade_number')
                            ->label(self::label('رقم الصف', 'Grade number'))
                            ->numeric()
                            ->rules(['nullable', 'integer', 'min:1', 'max:12'])
                            ->helperText(self::label(
                                'يمكن تركه فارغًا لصفوف الروضة أو الحالات غير الرقمية.',
                                'Can be left empty for kindergarten or non-numeric grades.'
                            )),

                        Textarea::make('description')
                            ->label(self::label('الوصف', 'Description'))
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
                    ->with('educationalStage')
                    ->orderBy('educational_stage_id')
                    ->orderBy('sort_order')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('sort_order')
                    ->label(self::label('الترتيب', 'Order'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('name')
                    ->label(self::label('اسم الصف', 'Grade name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Grade $record): ?string => $record->description),

                TextColumn::make('code')
                    ->label(self::label('الرمز', 'Code'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('educationalStage.name')
                    ->label(self::label('المرحلة التعليمية', 'Educational stage'))
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grade_number')
                    ->label(self::label('رقم الصف', 'Grade number'))
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('is_active')
                    ->label(self::label('الحالة', 'Status'))
                    ->state(fn (Grade $record): string => $record->is_active
                        ? self::label('مفعل', 'Active')
                        : self::label('غير مفعل', 'Inactive'))
                    ->badge()
                    ->color(fn (Grade $record): string => $record->is_active ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label(self::label('آخر تحديث', 'Updated at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('educational_stage_id')
                    ->label(self::label('المرحلة التعليمية', 'Educational stage'))
                    ->options(fn (): array => self::stageOptions())
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label(self::label('الحالة', 'Status'))
                    ->placeholder(self::label('الكل', 'All'))
                    ->trueLabel(self::label('المفعلة فقط', 'Active only'))
                    ->falseLabel(self::label('غير المفعلة فقط', 'Inactive only')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (Grade $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث الصف الدراسي بنجاح',
                        'Grade updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد صفوف دراسية', 'No grades found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإضافة الصفوف وربطها بالمراحل التعليمية أو شغّل EducationalStructureSeeder.',
                'Start by adding grades and linking them to stages or run EducationalStructureSeeder.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGrades::route('/'),
        ];
    }

    private static function stageOptions(): array
    {
        return EducationalStage::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    private static function nextSortOrder(): int
    {
        return ((int) Grade::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
