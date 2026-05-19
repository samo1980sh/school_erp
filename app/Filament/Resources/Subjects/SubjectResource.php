<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects;

use App\Filament\Resources\Subjects\Pages\ManageSubjects;
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

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('مادة دراسية', 'Subject');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('المواد الدراسية', 'Subjects');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('المواد الدراسية', 'Subjects');
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
                Section::make(self::label('تنظيم المادة', 'Subject organization'))
                    ->description(self::label(
                        'حدد ترتيب المادة وتصنيفها وحالتها. هذه البيانات تساعد في الجداول والفلاتر وخطط الصفوف.',
                        'Set the subject order, category, and status. These fields help tables, filters, and grade curriculum plans.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required()
                            ->helperText(self::label(
                                'يظهر أولًا حسب قاعدة المشروع. اترك فراغات بين الأرقام لتسهيل الإدراج لاحقًا.',
                                'Shown first by project rule. Leave gaps between numbers for easier future insertion.'
                            )),

                        Select::make('category')
                            ->label(self::label('تصنيف المادة', 'Subject category'))
                            ->options(self::categoryOptions())
                            ->default('core')
                            ->required()
                            ->native(false),

                        Toggle::make('is_active')
                            ->label(self::label('مفعلة', 'Active'))
                            ->default(true),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),

                Section::make(self::label('بيانات المادة', 'Subject details'))
                    ->description(self::label(
                        'عرّف المادة الدراسية ورمزها وعدد الحصص الافتراضي. الرمز يستخدم في التقارير والاستيراد المستقبلي.',
                        'Define the subject name, code, and default weekly periods. The code is used in reports and future imports.'
                    ))
                    ->schema([
                        TextInput::make('name')
                            ->label(self::label('اسم المادة', 'Subject name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('code')
                            ->label(self::label('رمز المادة', 'Subject code'))
                            ->required()
                            ->unique(table: 'subjects', column: 'code', ignoreRecord: true)
                            ->rules(['regex:/^[A-Z0-9_-]+$/'])
                            ->maxLength(255)
                            ->placeholder('MATH'),

                        TextInput::make('default_weekly_periods')
                            ->label(self::label('الحصص الأسبوعية الافتراضية', 'Default weekly periods'))
                            ->numeric()
                            ->rules(['nullable', 'integer', 'min:1', 'max:40']),

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
                    ->withCount('gradeSubjects')
                    ->orderBy('category')
                    ->orderBy('sort_order')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('sort_order')
                    ->label(self::label('الترتيب', 'Order'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('name')
                    ->label(self::label('اسم المادة', 'Subject name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Subject $record): ?string => $record->description),

                TextColumn::make('code')
                    ->label(self::label('الرمز', 'Code'))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label(self::label('التصنيف', 'Category'))
                    ->formatStateUsing(fn (?string $state): string => self::categoryLabel((string) $state))
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('default_weekly_periods')
                    ->label(self::label('حصص افتراضية', 'Default periods'))
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('grade_subjects_count')
                    ->label(self::label('خطط الصفوف', 'Grade plans'))
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label(self::label('الحالة', 'Status'))
                    ->state(fn (Subject $record): string => $record->is_active
                        ? self::label('مفعلة', 'Active')
                        : self::label('غير مفعلة', 'Inactive'))
                    ->badge()
                    ->color(fn (Subject $record): string => $record->is_active ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label(self::label('آخر تحديث', 'Updated at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label(self::label('التصنيف', 'Category'))
                    ->options(self::categoryOptions())
                    ->native(false),

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
                    ->visible(fn (Subject $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث المادة الدراسية بنجاح',
                        'Subject updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد مواد دراسية', 'No subjects found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإضافة المواد الدراسية أو شغّل SubjectCurriculumSeeder لإضافة بيانات تجريبية.',
                'Start by adding subjects or run SubjectCurriculumSeeder to add demo data.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSubjects::route('/'),
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            'core' => self::label('أساسية', 'Core'),
            'scientific' => self::label('علمية', 'Scientific'),
            'humanities' => self::label('إنسانية', 'Humanities'),
            'language' => self::label('لغات', 'Languages'),
            'skills' => self::label('مهارات', 'Skills'),
            'activity' => self::label('نشاطات', 'Activities'),
        ];
    }

    public static function categoryLabel(string $category): string
    {
        return self::categoryOptions()[$category] ?? $category;
    }

    private static function nextSortOrder(): int
    {
        return ((int) Subject::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
