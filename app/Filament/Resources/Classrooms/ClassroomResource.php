<?php

declare(strict_types=1);

namespace App\Filament\Resources\Classrooms;

use App\Filament\Resources\Classrooms\Pages\ManageClassrooms;
use App\Models\Classroom;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ClassroomResource extends Resource
{
    protected static ?string $model = Classroom::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('قاعة دراسية', 'Classroom');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('القاعات الدراسية', 'Classrooms');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('القاعات الدراسية', 'Classrooms');
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
        return auth()->user()?->can('classrooms.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('classrooms.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('classrooms.update') ?? false;
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
                Section::make(self::label('تنظيم القاعة', 'Classroom organization'))
                    ->description(self::label(
                        'حدد ترتيب القاعة ونوعها وسعتها قبل ربطها بالشعب الدراسية.',
                        'Set the classroom order, type, and capacity before assigning it to sections.'
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

                        Select::make('type')
                            ->label(self::label('نوع القاعة', 'Room type'))
                            ->options(self::typeOptions())
                            ->default('classroom')
                            ->required()
                            ->native(false),

                        TextInput::make('capacity')
                            ->label(self::label('السعة', 'Capacity'))
                            ->numeric()
                            ->rules(['nullable', 'integer', 'min:1', 'max:1000'])
                            ->helperText(self::label(
                                'السعة الاستيعابية التقريبية للقاعة.',
                                'Approximate room capacity.'
                            )),

                        Toggle::make('is_active')
                            ->label(self::label('مفعلة', 'Active'))
                            ->default(true),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ]),

                Section::make(self::label('بيانات القاعة', 'Classroom details'))
                    ->description(self::label(
                        'البيانات الأساسية والموقع الداخلي للقاعة داخل المدرسة.',
                        'Basic details and internal location of the classroom inside the school.'
                    ))
                    ->schema([
                        TextInput::make('name')
                            ->label(self::label('اسم القاعة', 'Classroom name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(self::label('قاعة 101', 'Room 101'))
                            ->autofocus(),

                        TextInput::make('code')
                            ->label(self::label('رمز القاعة', 'Classroom code'))
                            ->required()
                            ->unique(table: 'classrooms', column: 'code', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('ROOM-101')
                            ->helperText(self::label(
                                'رمز فريد يستخدم في الربط والتقارير.',
                                'A unique code used for linking and reports.'
                            )),

                        TextInput::make('building')
                            ->label(self::label('المبنى', 'Building'))
                            ->maxLength(255),

                        TextInput::make('floor')
                            ->label(self::label('الطابق', 'Floor'))
                            ->maxLength(255),

                        TextInput::make('room_number')
                            ->label(self::label('رقم الغرفة', 'Room number'))
                            ->maxLength(255),

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
                    ->withCount('sections')
                    ->orderBy('sort_order')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(self::label('القاعة', 'Classroom'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Classroom $record): string => (string) $record->code),

                TextColumn::make('type')
                    ->label(self::label('النوع', 'Type'))
                    ->formatStateUsing(fn (?string $state): string => self::typeLabel((string) $state))
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('building')
                    ->label(self::label('المبنى', 'Building'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('floor')
                    ->label(self::label('الطابق', 'Floor'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('capacity')
                    ->label(self::label('السعة', 'Capacity'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('sections_count')
                    ->label(self::label('الشعب', 'Sections'))
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label(self::label('الحالة', 'Status'))
                    ->state(fn (Classroom $record): string => $record->is_active
                        ? self::label('مفعلة', 'Active')
                        : self::label('غير مفعلة', 'Inactive'))
                    ->badge()
                    ->color(fn (Classroom $record): string => $record->is_active ? 'success' : 'gray')
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
                SelectFilter::make('type')
                    ->label(self::label('نوع القاعة', 'Room type'))
                    ->options(self::typeOptions())
                    ->native(false),

                SelectFilter::make('is_active')
                    ->label(self::label('الحالة', 'Status'))
                    ->options([
                        '1' => self::label('مفعلة', 'Active'),
                        '0' => self::label('غير مفعلة', 'Inactive'),
                    ])
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (Classroom $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label(
                        'تم تحديث القاعة بنجاح',
                        'Classroom updated successfully'
                    )),
            ])
            ->emptyStateHeading(self::label('لا توجد قاعات دراسية', 'No classrooms found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإنشاء قاعة أو شغّل ClassroomSectionSeeder لإضافة بيانات تجريبية.',
                'Create a classroom or run ClassroomSectionSeeder to add demo data.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClassrooms::route('/'),
        ];
    }

    public static function typeOptions(): array
    {
        return [
            'classroom' => self::label('قاعة صفية', 'Classroom'),
            'lab' => self::label('مخبر', 'Laboratory'),
            'library' => self::label('مكتبة', 'Library'),
            'hall' => self::label('قاعة متعددة الاستخدام', 'Hall'),
            'office' => self::label('مكتب إداري', 'Office'),
            'other' => self::label('أخرى', 'Other'),
        ];
    }

    public static function typeLabel(string $type): string
    {
        return self::typeOptions()[$type] ?? $type;
    }

    private static function nextSortOrder(): int
    {
        return ((int) Classroom::query()->max('sort_order')) + 10;
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
