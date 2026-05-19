<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeTypes;

use App\Filament\Resources\FeeTypes\Pages\ManageFeeTypes;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Grade;
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

class FeeTypeResource extends Resource
{
    protected static ?string $model = FeeType::class;

    protected static ?string $slug = 'fee-types';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'display_title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('نوع رسم', 'Fee type');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('أنواع الرسوم', 'Fee Types');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('أنواع الرسوم', 'Fee Types');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('المالية والرسوم', 'Finance & Fees');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('fees.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('fees.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('fees.update') ?? false;
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
                Section::make(self::label('تعريف نوع الرسم', 'Fee type definition'))
                    ->description(self::label(
                        'عرّف نوع الرسم الذي سيتم استخدامه لاحقًا لإنشاء مطالبات مالية على الطلاب مثل القسط أو الكتب أو الرحلات.',
                        'Define a fee type that will later be used to create student financial charges such as tuition, books, or trips.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required(),

                        TextInput::make('code')
                            ->label(self::label('الكود', 'Code'))
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'fee_types', column: 'code', ignoreRecord: true)
                            ->extraInputAttributes(self::ltrInputAttributes())
                            ->helperText(self::label(
                                'كود ثابت للرسم مثل TUITION-2026 أو TRIP-2026-01.',
                                'A stable fee code such as TUITION-2026 or TRIP-2026-01.'
                            )),

                        TextInput::make('name')
                            ->label(self::label('اسم الرسم', 'Fee name'))
                            ->required()
                            ->maxLength(255)
                            ->helperText(self::label(
                                'مثال: القسط الدراسي، رسوم الكتب، رحلة مدرسية، النقل المدرسي.',
                                'Example: Tuition, books fee, school trip, school transport.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),

                Section::make(self::label('الإعدادات المالية والأكاديمية', 'Financial and academic settings'))
                    ->description(self::label(
                        'حدد السنة والصف إن كان الرسم مرتبطًا بهما، والمبلغ الافتراضي وتاريخ الاستحقاق.',
                        'Select the academic year and grade when applicable, plus the default amount and due date.'
                    ))
                    ->schema([
                        Select::make('academic_year_id')
                            ->label(self::label('السنة الدراسية', 'Academic year'))
                            ->options(fn (): array => AcademicYear::query()
                                ->orderBy('sort_order')
                                ->orderByDesc('starts_on')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('grade_id')
                            ->label(self::label('الصف الدراسي', 'Grade'))
                            ->options(fn (): array => Grade::query()
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText(self::label(
                                'اتركه فارغًا إذا كان الرسم عامًا ولا يخص صفًا محددًا.',
                                'Leave empty if this fee is general and not tied to one grade.'
                            )),

                        TextInput::make('amount')
                            ->label(self::label('المبلغ الافتراضي', 'Default amount'))
                            ->numeric()
                            ->rules(['numeric', 'min:0'])
                            ->required()
                            ->extraInputAttributes(self::ltrInputAttributes()),

                        DatePicker::make('due_on')
                            ->label(self::label('تاريخ الاستحقاق الافتراضي', 'Default due date'))
                            ->native(false)
                            ->displayFormat('Y-m-d'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ]),

                Section::make(self::label('الحالة والملاحظات', 'Status and notes'))
                    ->description(self::label(
                        'استخدم الحالة لتعطيل نوع رسم لم يعد مستخدمًا بدون حذف السجلات المرتبطة به.',
                        'Use status to disable a fee type that is no longer used without deleting related records.'
                    ))
                    ->schema([
                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('active')
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['academicYear:id,name', 'grade:id,name'])
                ->orderBy('sort_order')
                ->orderBy('name'))
            ->columns([
                TextColumn::make('code')
                    ->label(self::label('الكود', 'Code'))
                    ->formatStateUsing(fn ($state): string => self::ltr($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label(self::label('اسم الرسم', 'Fee name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (FeeType $record): ?string => $record->grade?->name),

                TextColumn::make('academicYear.name')
                    ->label(self::label('السنة', 'Year'))
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(self::label('المبلغ', 'Amount'))
                    ->money('SYP')
                    ->sortable(),

                TextColumn::make('due_on')
                    ->label(self::label('الاستحقاق', 'Due'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state)),
            ])
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->options(fn (): array => AcademicYear::query()->orderBy('sort_order')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (FeeType $record): bool => static::canEdit($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFeeTypes::route('/'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function statusOptions(): array
    {
        return [
            'active' => self::label('نشط', 'Active'),
            'inactive' => self::label('غير نشط', 'Inactive'),
            'archived' => self::label('مؤرشف', 'Archived'),
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
        return ((int) FeeType::query()->max('sort_order')) + 10;
    }

    private static function ltr(mixed $value): string
    {
        $value = trim((string) $value);

        return $value === ''
            ? '—'
            : '<span dir="ltr" style="unicode-bidi: plaintext; text-align: left; display: inline-block;">' . e($value) . '</span>';
    }

    private static function ltrInputAttributes(): array
    {
        return [
            'dir' => 'ltr',
            'style' => 'unicode-bidi: plaintext; text-align: left;',
        ];
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
