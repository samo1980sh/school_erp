<?php

declare(strict_types=1);

namespace App\Filament\Resources\EducationalStages;

use App\Filament\Resources\EducationalStages\Pages\ManageEducationalStages;
use App\Models\EducationalStage;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EducationalStageResource extends Resource
{
    protected static ?string $model = EducationalStage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('مرحلة تعليمية', 'Educational stage');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('المراحل التعليمية', 'Educational stages');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('المراحل التعليمية', 'Educational stages');
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
        return auth()->user()?->can('educational_stages.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('educational_stages.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('educational_stages.update') ?? false;
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
                Section::make(self::label('ترتيب المرحلة وحالتها', 'Stage order and status'))
                    ->description(self::label(
                        'حدد ترتيب ظهور المرحلة وحالتها. ترتيب المرحلة يؤثر لاحقًا على ترتيب الصفوف والشعب والتقارير.',
                        'Set the stage display order and status. The order will later affect grades, sections, and reports.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required()
                            ->helperText(self::label(
                                'اترك فراغات بين الأرقام مثل 10، 20، 30 لتسهيل الإضافة لاحقًا.',
                                'Leave gaps such as 10, 20, 30 to make future additions easier.'
                            )),

                        Toggle::make('is_active')
                            ->label(self::label('مفعلة', 'Active'))
                            ->default(true)
                            ->helperText(self::label(
                                'عند تعطيل المرحلة لن تظهر عادة في قوائم الاختيار المستقبلية.',
                                'Inactive stages will usually be hidden from future selection lists.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),

                Section::make(self::label('بيانات المرحلة التعليمية', 'Educational stage details'))
                    ->description(self::label(
                        'بيانات تعريفية مختصرة للمرحلة التعليمية، بدون حقول عربية/إنكليزية منفصلة.',
                        'Basic identification details for the educational stage, without duplicated Arabic/English data fields.'
                    ))
                    ->schema([
                        TextInput::make('name')
                            ->label(self::label('اسم المرحلة', 'Stage name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('code')
                            ->label(self::label('رمز المرحلة', 'Stage code'))
                            ->required()
                            ->unique(table: 'educational_stages', column: 'code', ignoreRecord: true)
                            ->rules(['regex:/^[A-Z0-9_-]+$/'])
                            ->maxLength(255)
                            ->placeholder('BASIC-CYCLE-1')
                            ->helperText(self::label(
                                'استخدم رمزًا تقنيًا واضحًا بالأحرف الإنكليزية الكبيرة والأرقام والشرطة فقط.',
                                'Use a clear technical code with uppercase English letters, numbers, underscores, or dashes only.'
                            )),

                        Textarea::make('description')
                            ->label(self::label('الوصف', 'Description'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->withCount('grades')
                    ->orderBy('sort_order')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('sort_order')
                    ->label(self::label('الترتيب', 'Order'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('name')
                    ->label(self::label('اسم المرحلة', 'Stage name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (EducationalStage $record): ?string => $record->description),

                TextColumn::make('code')
                    ->label(self::label('الرمز', 'Code'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grades_count')
                    ->label(self::label('عدد الصفوف', 'Grades count'))
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label(self::label('الحالة', 'Status'))
                    ->state(fn (EducationalStage $record): string => $record->is_active
                        ? self::label('مفعلة', 'Active')
                        : self::label('غير مفعلة', 'Inactive'))
                    ->badge()
                    ->color(fn (EducationalStage $record): string => $record->is_active ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label(self::label('آخر تحديث', 'Updated at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                    ->visible(fn (EducationalStage $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث المرحلة التعليمية بنجاح',
                        'Educational stage updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد مراحل تعليمية', 'No educational stages found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإضافة المراحل التعليمية الأساسية للمدرسة أو شغّل EducationalStructureSeeder.',
                'Start by adding the school educational stages or run EducationalStructureSeeder.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEducationalStages::route('/'),
        ];
    }

    private static function nextSortOrder(): int
    {
        return ((int) EducationalStage::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
