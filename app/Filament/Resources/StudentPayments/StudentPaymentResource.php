<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentPayments;

use App\Filament\Resources\StudentPayments\Pages\ManageStudentPayments;
use App\Models\StudentFee;
use App\Models\StudentPayment;
use BackedEnum;
use Filament\Actions\EditAction;
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
        return app()->getLocale() === 'en' ? 'Payment receipt' : 'إيصال دفع';
    }
public static function getPluralModelLabel(): string
{
    return app()->getLocale() === 'en' ? 'Payment Receipts' : 'إيصالات الدفع';
}

public static function getNavigationLabel(): string
{
    return app()->getLocale() === 'en' ? 'Payment Receipts' : 'إيصالات الدفع';
}

public static function getNavigationGroup(): ?string
{
    return app()->getLocale() === 'en' ? 'Finance & Fees' : 'المالية والرسوم';
}
    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['student:id,student_number,first_name,father_name,last_name', 'studentFee:id,fee_number'])->orderByDesc('paid_on'))->columns([
            TextColumn::make('payment_number')->label(self::label('رقم الدفعة', 'Payment no.'))->formatStateUsing(fn ($state): string => self::ltr($state))->html()->copyable()->searchable()->sortable(),
            TextColumn::make('studentFee.fee_number')->label(self::label('رقم الرسم', 'Fee no.'))->formatStateUsing(fn ($state): string => self::ltr($state))->html()->copyable()->searchable(),
            TextColumn::make('student.student_number')->label(self::label('رقم الطالب', 'Student no.'))->formatStateUsing(fn ($state): string => self::ltr($state))->html()->copyable()->searchable(),
            TextColumn::make('student_name')->label(self::label('الطالب', 'Student'))->state(fn (StudentPayment $record): string => trim(implode(' ', array_filter([$record->student?->first_name, $record->student?->father_name, $record->student?->last_name]))))->weight('bold'),
            TextColumn::make('amount')->label(self::label('المبلغ', 'Amount'))->money('SYP')->sortable(),
            TextColumn::make('paid_on')->label(self::label('تاريخ الدفع', 'Paid on'))->date('Y-m-d')->sortable(),
            TextColumn::make('payment_method')->label(self::label('طريقة الدفع', 'Method'))->formatStateUsing(fn (?string $state): string => self::methodLabel((string) $state))->badge()->color('primary'),
            TextColumn::make('reference_number')->label(self::label('المرجع', 'Reference'))->formatStateUsing(fn ($state): string => self::ltr($state))->html()->copyable()->toggleable(),
        ])->filters([
            SelectFilter::make('payment_method')->label(self::label('طريقة الدفع', 'Payment method'))->options(self::methodOptions()),
        ])->recordActions([
            EditAction::make()->label(self::label('تعديل', 'Edit'))->slideOver()->modalWidth(Width::SevenExtraLarge)->visible(fn (StudentPayment $record): bool => static::canEdit($record)),
        ]);
    }
    public static function getPages(): array { return ['index' => ManageStudentPayments::route('/')]; }
    public static function getRelations(): array { return []; }
    public static function methodOptions(): array { return ['cash' => self::label('نقدًا', 'Cash'), 'bank_transfer' => self::label('تحويل بنكي', 'Bank transfer'), 'card' => self::label('بطاقة', 'Card')]; }
    public static function methodLabel(string $method): string { return self::methodOptions()[$method] ?? $method; }
    private static function ltr(mixed $value): string { $value = trim((string) $value); return $value === '' ? '—' : '<span dir="ltr" style="unicode-bidi: plaintext; text-align: left; display: inline-block;">' . e($value) . '</span>'; }
    private static function label(string $ar, string $en): string { return app()->getLocale() === 'en' ? $en : $ar; }
}
