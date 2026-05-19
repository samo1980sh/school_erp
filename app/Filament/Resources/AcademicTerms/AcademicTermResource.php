<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcademicTerms;

use App\Filament\Resources\AcademicTerms\Pages\ManageAcademicTerms;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AcademicTermResource extends Resource
{
    protected static ?string $model = AcademicTerm::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('فصل دراسي', 'Academic term');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('الفصول الدراسية', 'Academic terms');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('الفصول الدراسية', 'Academic terms');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('الإعدادات الأكاديمية', 'Academic Setup');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('academic_terms.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('academic_terms.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('academic_terms.update') ?? false;
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
                Section::make(self::label('تنظيم الفصل الدراسي', 'Academic term organization'))
                    ->description(self::label(
                        'حدد ترتيب الفصل وحالته ضمن السنة الدراسية المرتبط بها.',
                        'Set the term order and status within its academic year.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required()
                            ->helperText(self::label(
                                'يظهر أولًا في النموذج حسب قاعدة المشروع. غالبًا: 10 للفصل الأول، 20 للثاني، 30 للصيفي.',
                                'Shown first by project rule. Usually: 10 for first term, 20 for second, 30 for summer.'
                            )),

                        Select::make('academic_year_id')
                            ->label(self::label('السنة الدراسية', 'Academic year'))
                            ->relationship(name: 'academicYear', titleAttribute: 'name')
                            ->getOptionLabelFromRecordUsing(fn (AcademicYear $record): string => (string) $record->display_name)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(AcademicYearResource::statusOptions())
                            ->default('planned')
                            ->required()
                            ->native(false),

                        Toggle::make('is_current')
                            ->label(self::label('الفصل الحالي', 'Current term'))
                            ->helperText(self::label(
                                'عند تفعيله سيتم إلغاء الفصل الحالي السابق ضمن نفس السنة الدراسية فقط.',
                                'When enabled, the previous current term in the same academic year will be unset.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'xl' => 4,
                    ]),

                Section::make(self::label('بيانات الفصل', 'Term details'))
                    ->description(self::label(
                        'البيانات الأساسية للفصل الدراسي وتواريخه.',
                        'Basic academic term data and dates.'
                    ))
                    ->schema([
                        TextInput::make('name')
                            ->label(self::label('اسم الفصل', 'Term name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(self::label('الفصل الدراسي الأول', 'First term'))
                            ->autofocus(),

                        TextInput::make('code')
                            ->label(self::label('رمز الفصل', 'Term code'))
                            ->required()
                            ->unique(table: 'academic_terms', column: 'code', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('AY-2025-2026-T1'),

                        DatePicker::make('starts_on')
                            ->label(self::label('تاريخ البداية', 'Start date'))
                            ->required()
                            ->native(false),

                        DatePicker::make('ends_on')
                            ->label(self::label('تاريخ النهاية', 'End date'))
                            ->required()
                            ->native(false)
                            ->rules(['after_or_equal:starts_on']),

                        Textarea::make('notes')
                            ->label(self::label('ملاحظات', 'Notes'))
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
                    ->with('academicYear')
                    ->orderBy('academic_year_id')
                    ->orderBy('sort_order')
                    ->orderBy('starts_on')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(self::label('الفصل الدراسي', 'Academic term'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (AcademicTerm $record): string => (string) $record->code),

                TextColumn::make('academicYear.name')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => AcademicYearResource::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => AcademicYearResource::statusColor((string) $state))
                    ->sortable(),

                TextColumn::make('is_current')
                    ->label(self::label('الحالي', 'Current'))
                    ->state(fn (AcademicTerm $record): string => $record->is_current
                        ? self::label('نعم', 'Yes')
                        : self::label('لا', 'No'))
                    ->badge()
                    ->color(fn (AcademicTerm $record): string => $record->is_current ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('starts_on')
                    ->label(self::label('البداية', 'Start'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('ends_on')
                    ->label(self::label('النهاية', 'End'))
                    ->date('Y-m-d')
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
                    ->options(fn (): array => AcademicYear::query()
                        ->orderBy('sort_order')
                        ->orderByDesc('starts_on')
                        ->get()
                        ->mapWithKeys(fn (AcademicYear $year): array => [$year->id => (string) $year->display_name])
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(AcademicYearResource::statusOptions())
                    ->native(false),

                SelectFilter::make('is_current')
                    ->label(self::label('الفصل الحالي', 'Current term'))
                    ->options([
                        '1' => self::label('نعم', 'Yes'),
                        '0' => self::label('لا', 'No'),
                    ])
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (AcademicTerm $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث الفصل الدراسي بنجاح',
                        'Academic term updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد فصول دراسية', 'No academic terms found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإنشاء فصل دراسي أو شغّل AcademicFoundationSeeder لإضافة بيانات تجريبية.',
                'Create an academic term or run AcademicFoundationSeeder to add demo data.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAcademicTerms::route('/'),
        ];
    }

    private static function nextSortOrder(): int
    {
        return ((int) AcademicTerm::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
