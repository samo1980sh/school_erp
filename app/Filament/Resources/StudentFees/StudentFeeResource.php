<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentFees;

use App\Filament\Resources\StudentFees\Pages\ManageStudentFees;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Grade;
use App\Models\SchoolSection;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
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

class StudentFeeResource extends Resource
{
    protected static ?string $model = StudentFee::class;

    protected static ?string $slug = 'student-fees';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'display_title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('تفصيل رسم', 'Fee detail');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('تفاصيل الرسوم', 'Fee Details');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('تفاصيل الرسوم', 'Fee Details');
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
                Section::make(self::label('ربط المطالبة المالية', 'Financial charge relation'))
                    ->description(self::label(
                        'هذا القسم ينشئ مطالبة مالية على طالب محدد. المدفوع والمتبقي لا يحرران يدويًا، بل يتم تحديثهما من إيصالات الدفع.',
                        'This section creates a financial charge for a specific student. Paid and remaining amounts are not edited manually; they are updated from payment receipts.'
                    ))
                    ->schema([
                        TextInput::make('fee_number')
                            ->label(self::label('رقم الرسم', 'Fee number'))
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'student_fees', column: 'fee_number', ignoreRecord: true)
                            ->extraInputAttributes(self::ltrInputAttributes()),

                        Select::make('fee_type_id')
                            ->label(self::label('نوع الرسم', 'Fee type'))
                            ->options(fn (): array => self::feeTypeOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('student_id')
                            ->label(self::label('الطالب', 'Student'))
                            ->options(fn (): array => self::studentOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        Select::make('student_enrollment_id')
                            ->label(self::label('تسجيل الطالب', 'Student enrollment'))
                            ->options(fn (): array => self::enrollmentOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText(self::label(
                                'يفضل ربط الرسم بسجل تسجيل الطالب عند توفره.',
                                'It is recommended to link the fee to the student enrollment when available.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),

                Section::make(self::label('السنة والصف والشعبة', 'Year, grade, and section'))
                    ->description(self::label(
                        'تستخدم هذه البيانات للفلاتر والتقارير وأرصدة الطلاب.',
                        'These values are used for filters, reports, and student balances.'
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
                            ->required()
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
                            ->native(false),

                        Select::make('section_id')
                            ->label(self::label('الشعبة', 'Section'))
                            ->options(fn (): array => SchoolSection::query()
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),

                Section::make(self::label('المبلغ والاستحقاق', 'Amount and due date'))
                    ->description(self::label(
                        'حدد المبلغ والخصم وتاريخ الاستحقاق. المدفوع والمتبقي يظهران للمراجعة فقط ويتم حسابهما من إيصالات الدفع.',
                        'Set the amount, discount, and due date. Paid and remaining amounts are shown for review only and are calculated from payment receipts.'
                    ))
                    ->schema([
                        TextInput::make('amount')
                            ->label(self::label('المبلغ', 'Amount'))
                            ->numeric()
                            ->rules(['numeric', 'min:0'])
                            ->required()
                            ->extraInputAttributes(self::ltrInputAttributes()),

                        TextInput::make('discount_amount')
                            ->label(self::label('الخصم', 'Discount'))
                            ->numeric()
                            ->rules(['numeric', 'min:0'])
                            ->default(0)
                            ->required()
                            ->extraInputAttributes(self::ltrInputAttributes()),

                        TextInput::make('paid_amount')
                            ->label(self::label('المدفوع', 'Paid'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(self::label(
                                'يتم تحديثه تلقائيًا من إيصالات الدفع.',
                                'Automatically updated from payment receipts.'
                            ))
                            ->extraInputAttributes(self::ltrInputAttributes()),

                        TextInput::make('balance_amount')
                            ->label(self::label('المتبقي', 'Remaining'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(self::label(
                                'يتم حسابه تلقائيًا من المبلغ والخصم والمدفوع.',
                                'Automatically calculated from amount, discount, and paid amount.'
                            ))
                            ->extraInputAttributes(self::ltrInputAttributes()),

                        DatePicker::make('due_on')
                            ->label(self::label('تاريخ الاستحقاق', 'Due date'))
                            ->native(false)
                            ->displayFormat('Y-m-d'),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('unpaid')
                            ->required()
                            ->native(false)
                            ->helperText(self::label(
                                'تتحدث الحالة تلقائيًا عند تسجيل إيصالات دفع، إلا إذا كان الرسم ملغى.',
                                'Status updates automatically when payment receipts are recorded, unless the fee is cancelled.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),

                Section::make(self::label('ملاحظات', 'Notes'))
                    ->description(self::label(
                        'أي ملاحظات داخلية مرتبطة بهذه المطالبة المالية.',
                        'Any internal notes related to this financial charge.'
                    ))
                    ->schema([
                        Textarea::make('notes')
                            ->label(self::label('ملاحظات', 'Notes'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'student:id,student_number,first_name,father_name,last_name',
                    'feeType:id,code,name',
                    'academicYear:id,name',
                ])
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('fee_number')
                    ->label(self::label('رقم الرسم', 'Fee number'))
                    ->formatStateUsing(fn ($state): string => self::ltr($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student.student_number')
                    ->label(self::label('رقم الطالب', 'Student no.'))
                    ->formatStateUsing(fn ($state): string => self::ltr($state))
                    ->html()
                    ->copyable()
                    ->searchable(),

                TextColumn::make('student_name')
                    ->label(self::label('الطالب', 'Student'))
                    ->state(fn (StudentFee $record): string => self::studentName($record->student))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('student', fn (Builder $studentQuery) => $studentQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('father_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%")))
                    ->weight('bold'),

                TextColumn::make('feeType.name')
                    ->label(self::label('نوع الرسم', 'Fee type'))
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(self::label('المبلغ', 'Amount'))
                    ->money('SYP')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(self::label('المدفوع', 'Paid'))
                    ->money('SYP')
                    ->sortable(),

                TextColumn::make('balance_amount')
                    ->label(self::label('المتبقي', 'Balance'))
                    ->money('SYP')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions()),

                SelectFilter::make('academic_year_id')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->options(fn (): array => AcademicYear::query()->orderBy('sort_order')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (StudentFee $record): bool => static::canEdit($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentFees::route('/'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function statusOptions(): array
    {
        return [
            'unpaid' => self::label('غير مدفوع', 'Unpaid'),
            'partial' => self::label('مدفوع جزئيًا', 'Partial'),
            'paid' => self::label('مدفوع', 'Paid'),
            'cancelled' => self::label('ملغى', 'Cancelled'),
        ];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? $status;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'partial' => 'warning',
            'cancelled' => 'gray',
            default => 'danger',
        };
    }

    private static function feeTypeOptions(): array
    {
        return FeeType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (FeeType $feeType): array => [
                $feeType->id => trim(sprintf('%s - %s', $feeType->name, $feeType->code)),
            ])
            ->toArray();
    }

    private static function studentOptions(): array
    {
        return Student::query()
            ->orderBy('student_number')
            ->limit(2000)
            ->get()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => trim(sprintf('%s - %s', $student->student_number, self::studentName($student))),
            ])
            ->toArray();
    }

    private static function enrollmentOptions(): array
    {
        return StudentEnrollment::query()
            ->with(['student:id,student_number,first_name,father_name,last_name', 'academicYear:id,name', 'grade:id,name', 'section:id,name'])
            ->orderByDesc('id')
            ->limit(2000)
            ->get()
            ->mapWithKeys(fn (StudentEnrollment $enrollment): array => [
                $enrollment->id => trim(implode(' - ', array_filter([
                    $enrollment->student?->student_number,
                    self::studentName($enrollment->student),
                    $enrollment->academicYear?->name,
                    $enrollment->grade?->name,
                    $enrollment->section?->name,
                ]))),
            ])
            ->toArray();
    }

    private static function studentName(?Student $student): string
    {
        if (! $student) {
            return '—';
        }

        return trim(implode(' ', array_filter([
            $student->first_name,
            $student->father_name,
            $student->last_name,
        ]))) ?: '—';
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
