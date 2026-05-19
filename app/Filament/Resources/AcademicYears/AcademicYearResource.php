<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcademicYears;

use App\Filament\Resources\AcademicYears\Pages\ManageAcademicYears;
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

class AcademicYearResource extends Resource
{
    protected static ?string $model = AcademicYear::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('سنة دراسية', 'Academic year');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('السنوات الدراسية', 'Academic years');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('السنوات الدراسية', 'Academic years');
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
        return auth()->user()?->can('academic_years.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('academic_years.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('academic_years.update') ?? false;
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
            ->components([
                Section::make(self::label('تنظيم السنة الدراسية', 'Academic year organization'))
                    ->description(self::label(
                        'اضبط ترتيب السنة الدراسية وحالتها قبل ربطها بالفصول الدراسية والصفوف والتسجيل.',
                        'Set the academic year order and status before linking it to terms, grades, and enrollment.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required()
                            ->helperText(self::label(
                                'يظهر أولًا في النموذج حسب قاعدة المشروع. استخدم 10، 20، 30 لتسهيل الإدراج لاحقًا.',
                                'Shown first by project rule. Use 10, 20, 30 to make future insertion easier.'
                            )),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('planned')
                            ->required()
                            ->native(false),

                        Toggle::make('is_current')
                            ->label(self::label('السنة الحالية', 'Current year'))
                            ->helperText(self::label(
                                'عند تفعيلها سيتم إلغاء تفعيل السنة الحالية السابقة تلقائيًا.',
                                'When enabled, the previous current year will be unset automatically.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                    ]),

                Section::make(self::label('بيانات السنة', 'Year details'))
                    ->description(self::label(
                        'البيانات الأساسية التي تظهر في القوائم والتقارير والعمليات الأكاديمية.',
                        'Basic data shown in lists, reports, and academic workflows.'
                    ))
                    ->schema([
                        TextInput::make('name')
                            ->label(self::label('اسم السنة الدراسية', 'Academic year name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder('2025-2026')
                            ->autofocus(),

                        TextInput::make('code')
                            ->label(self::label('رمز السنة', 'Year code'))
                            ->required()
                            ->unique(table: 'academic_years', column: 'code', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('AY-2025-2026')
                            ->helperText(self::label(
                                'رمز تقني واضح ومميز لكل سنة دراسية.',
                                'A clear unique technical code for each academic year.'
                            )),

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
                    ->withCount('terms')
                    ->orderBy('sort_order')
                    ->orderByDesc('starts_on')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (AcademicYear $record): string => (string) $record->code),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state))
                    ->sortable(),

                TextColumn::make('is_current')
                    ->label(self::label('الحالية', 'Current'))
                    ->state(fn (AcademicYear $record): string => $record->is_current
                        ? self::label('نعم', 'Yes')
                        : self::label('لا', 'No'))
                    ->badge()
                    ->color(fn (AcademicYear $record): string => $record->is_current ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('starts_on')
                    ->label(self::label('البداية', 'Start'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('ends_on')
                    ->label(self::label('النهاية', 'End'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('terms_count')
                    ->label(self::label('الفصول', 'Terms'))
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
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
                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions())
                    ->native(false),

                SelectFilter::make('is_current')
                    ->label(self::label('السنة الحالية', 'Current year'))
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
                    ->visible(fn (AcademicYear $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث السنة الدراسية بنجاح',
                        'Academic year updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد سنوات دراسية', 'No academic years found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإنشاء سنة دراسية أو شغّل AcademicFoundationSeeder لإضافة بيانات تجريبية.',
                'Create an academic year or run AcademicFoundationSeeder to add demo data.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAcademicYears::route('/'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'planned' => self::label('مخططة', 'Planned'),
            'active' => self::label('نشطة', 'Active'),
            'closed' => self::label('مغلقة', 'Closed'),
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
            'closed' => 'gray',
            default => 'warning',
        };
    }

    private static function nextSortOrder(): int
    {
        return ((int) AcademicYear::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
