<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentPayments;

use App\Filament\Resources\StudentPayments\Pages\ManageStudentPayments;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Employee;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\StudentPayment;
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

class StudentPaymentResource extends Resource
{
    protected static ?string $model = StudentPayment::class;

    protected static ?string $slug = 'student-payments';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'display_title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('إيصال دفع', 'Payment receipt');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('إيصالات الدفع', 'Payment Receipts');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('إيصالات الدفع', 'Payment Receipts');
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
        return auth()->user()?->can('fees.payments') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('fees.payments') ?? false;
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
                Section::make(self::label('ربط الإيصال بالرسم', 'Receipt fee relation'))
                    ->description(self::label(
                        'يجب ربط كل إيصال دفع بمطالبة مالية محددة. سيقوم النظام بتحديث المدفوع والمتبقي في الرسم تلقائيًا.',
                        'Every payment receipt must be linked to a specific financial charge. The system will update paid and remaining amounts automatically.'
                    ))
                    ->schema([
                        TextInput::make('payment_number')
                            ->label(self::label('رقم الإيصال', 'Receipt number'))
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'student_payments', column: 'payment_number', ignoreRecord: true)
                            ->extraInputAttributes(self::ltrInputAttributes()),

                        Select::make('student_fee_id')
                            ->label(self::label('الرسم المرتبط', 'Linked fee'))
                            ->options(fn (): array => self::studentFeeOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->helperText(self::label(
                                'اختر الرسم الذي سيتم تسجيل الدفعة عليه. لا تسجل دفعة غير مرتبطة برسم.',
                                'Choose the fee that this payment will be applied to. Do not record an unlinked payment.'
                            )),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),

                Section::make(self::label('بيانات الدفع', 'Payment details'))
                    ->description(self::label(
                        'أدخل مبلغ الإيصال وطريقة الدفع وتاريخه. النظام يمنع حفظ دفعة تتجاوز المتبقي على الرسم.',
                        'Enter the receipt amount, method, and date. The system prevents saving a payment greater than the remaining fee balance.'
                    ))
                    ->schema([
                        TextInput::make('amount')
                            ->label(self::label('المبلغ', 'Amount'))
                            ->numeric()
                            ->rules(['numeric', 'min:0.01'])
                            ->required()
                            ->extraInputAttributes(self::ltrInputAttributes()),

                        DatePicker::make('paid_on')
                            ->label(self::label('تاريخ الدفع', 'Paid on'))
                            ->default(now())
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d'),

                        Select::make('payment_method')
                            ->label(self::label('طريقة الدفع', 'Payment method'))
                            ->options(self::methodOptions())
                            ->default('cash')
                            ->required()
                            ->native(false),

                        TextInput::make('reference_number')
                            ->label(self::label('رقم المرجع', 'Reference number'))
                            ->maxLength(255)
                            ->extraInputAttributes(self::ltrInputAttributes())
                            ->helperText(self::label(
                                'اختياري: رقم حوالة، رقم عملية، أو مرجع داخلي.',
                                'Optional: transfer number, transaction number, or internal reference.'
                            )),

                        Select::make('received_by_employee_id')
                            ->label(self::label('الموظف المستلم', 'Received by'))
                            ->options(fn (): array => self::employeeOptions())
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),

                Section::make(self::label('ملاحظات', 'Notes'))
                    ->description(self::label(
                        'أي تفاصيل إضافية خاصة بالإيصال أو طريقة الدفع.',
                        'Any additional details about the receipt or payment method.'
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
                    'academicYear:id,name',
                    'studentFee:id,fee_number,fee_type_id,status',
                    'studentFee.feeType:id,name,code',
                ])
                ->orderByDesc('paid_on')
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('payment_number')
                    ->label(self::label('رقم الإيصال', 'Receipt no.'))
                    ->formatStateUsing(fn ($state): string => trim((string) $state) !== '' ? (string) $state : '—')
                    ->badge()
                    ->color('success')
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left;']),

                TextColumn::make('studentFee.fee_number')
                    ->label(self::label('رقم الرسم', 'Fee no.'))
                    ->formatStateUsing(fn ($state): string => self::ltr($state))
                    ->html()
                    ->copyable()
                    ->searchable(),

                TextColumn::make('student.student_number')
                    ->label(self::label('رقم الطالب', 'Student no.'))
                    ->formatStateUsing(fn ($state): string => self::ltr($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student_name')
                    ->label(self::label('الطالب', 'Student'))
                    ->state(fn (StudentPayment $record): string => self::studentName($record->student))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('student', fn (Builder $studentQuery): Builder => $studentQuery
                            ->where('student_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('father_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")))
                    ->weight('bold')
                    ->description(fn (StudentPayment $record): ?string => $record->academicYear?->name),

                TextColumn::make('studentFee.feeType.name')
                    ->label(self::label('نوع الرسم', 'Fee type'))
                    ->toggleable()
                    ->description(fn (StudentPayment $record): ?string => $record->studentFee?->feeType?->code),

                TextColumn::make('amount')
                    ->label(self::label('المبلغ', 'Amount'))
                    ->money('SYP')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('paid_on')
                    ->label(self::label('تاريخ الدفع', 'Paid on'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(self::label('طريقة الدفع', 'Method'))
                    ->formatStateUsing(fn (?string $state): string => self::methodLabel((string) $state))
                    ->badge()
                    ->color('primary'),

                TextColumn::make('studentFee.status')
                    ->label(self::label('حالة الرسم', 'Fee status'))
                    ->formatStateUsing(fn (?string $state): string => self::feeStatusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::feeStatusColor((string) $state))
                    ->toggleable(),

                TextColumn::make('reference_number')
                    ->label(self::label('المرجع', 'Reference'))
                    ->formatStateUsing(fn ($state): string => self::ltr($state))
                    ->html()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('student_id')
                    ->label(self::label('الطالب', 'Student'))
                    ->options(fn (): array => self::studentOptions())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('academic_year_id')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->options(fn (): array => AcademicYear::query()
                        ->orderBy('sort_order')
                        ->orderByDesc('starts_on')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('fee_type_id')
                    ->label(self::label('نوع الرسم', 'Fee type'))
                    ->options(fn (): array => self::feeTypeOptions())
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereHas('studentFee', fn (Builder $feeQuery): Builder => $feeQuery
                            ->where('fee_type_id', $value));
                    }),

                SelectFilter::make('fee_status')
                    ->label(self::label('حالة الرسم', 'Fee status'))
                    ->options(self::feeStatusOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereHas('studentFee', fn (Builder $feeQuery): Builder => $feeQuery
                            ->where('status', $value));
                    }),

                SelectFilter::make('payment_method')
                    ->label(self::label('طريقة الدفع', 'Payment method'))
                    ->options(self::methodOptions()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (StudentPayment $record): bool => static::canEdit($record)),
            ])
            ->emptyStateHeading(self::label('لا توجد إيصالات دفع', 'No payment receipts found'))
            ->emptyStateDescription(self::label(
                'هذه الصفحة سجل محاسبي للتدقيق في الدفعات والإيصالات. استخدم أرصدة الطلاب كشاشة العمل اليومية للمتابعة والتقارير.',
                'This page is an accounting audit log for payments and receipts. Use Student Balances as the daily finance workspace for monitoring and reports.'
            ));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentPayments::route('/'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    private static function studentOptions(): array
    {
        return Student::query()
            ->orderBy('student_number')
            ->limit(3000)
            ->get()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => trim(implode(' - ', array_filter([
                    $student->student_number,
                    self::studentName($student),
                ]))),
            ])
            ->toArray();
    }

    private static function feeTypeOptions(): array
    {
        return FeeType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    private static function feeStatusOptions(): array
    {
        return [
            'unpaid' => self::label('غير مدفوع', 'Unpaid'),
            'partial' => self::label('مدفوع جزئيًا', 'Partial'),
            'paid' => self::label('مدفوع', 'Paid'),
            'cancelled' => self::label('ملغى', 'Cancelled'),
        ];
    }

    private static function feeStatusLabel(string $status): string
    {
        return self::feeStatusOptions()[$status] ?? $status;
    }

    private static function feeStatusColor(string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'partial' => 'warning',
            'cancelled' => 'gray',
            default => 'danger',
        };
    }

    public static function methodOptions(): array
    {
        return [
            'cash' => self::label('نقدًا', 'Cash'),
            'bank_transfer' => self::label('تحويل بنكي', 'Bank transfer'),
            'card' => self::label('بطاقة', 'Card'),
        ];
    }

    public static function methodLabel(string $method): string
    {
        return self::methodOptions()[$method] ?? $method;
    }

    private static function studentFeeOptions(): array
    {
        return StudentFee::query()
            ->with(['student:id,student_number,first_name,father_name,last_name', 'feeType:id,name'])
            ->orderByDesc('id')
            ->limit(2000)
            ->get()
            ->mapWithKeys(function (StudentFee $fee): array {
                $remaining = number_format((float) $fee->balance_amount, 0);

                return [
                    $fee->id => trim(implode(' - ', array_filter([
                        $fee->fee_number,
                        self::studentName($fee->student),
                        $fee->student?->student_number,
                        $fee->feeType?->name,
                        self::label("المتبقي: {$remaining} SYP", "Remaining: {$remaining} SYP"),
                    ]))),
                ];
            })
            ->toArray();
    }

    private static function employeeOptions(): array
    {
        return Employee::query()
            ->orderBy('employee_number')
            ->limit(1000)
            ->get()
            ->mapWithKeys(fn (Employee $employee): array => [
                $employee->id => trim(implode(' - ', array_filter([
                    $employee->employee_number,
                    $employee->full_name ?? $employee->name ?? null,
                    trim(implode(' ', array_filter([
                        $employee->first_name ?? null,
                        $employee->father_name ?? null,
                        $employee->last_name ?? null,
                    ]))),
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
