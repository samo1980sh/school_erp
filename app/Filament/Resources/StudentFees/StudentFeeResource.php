<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentFees;

use App\Filament\Resources\StudentFees\Pages\ManageStudentFees;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\StudentFee;
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
        return app()->getLocale() === 'en' ? 'Fee detail' : 'تفصيل رسم';
    }
public static function getPluralModelLabel(): string
{
    return app()->getLocale() === 'en' ? 'Fee Details' : 'تفاصيل الرسوم';
}

public static function getNavigationLabel(): string
{
    return app()->getLocale() === 'en' ? 'Fee Details' : 'تفاصيل الرسوم';
}

public static function getNavigationGroup(): ?string
{
    return app()->getLocale() === 'en' ? 'Finance & Fees' : 'المالية والرسوم';
}
    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['student:id,student_number,first_name,father_name,last_name', 'feeType:id,code,name', 'academicYear:id,name'])->orderByDesc('id'))->columns([
            TextColumn::make('fee_number')->label(self::label('رقم الرسم', 'Fee number'))->formatStateUsing(fn ($state): string => self::ltr($state))->html()->copyable()->searchable()->sortable(),
            TextColumn::make('student.student_number')->label(self::label('رقم الطالب', 'Student no.'))->formatStateUsing(fn ($state): string => self::ltr($state))->html()->copyable()->searchable(),
            TextColumn::make('student_name')->label(self::label('الطالب', 'Student'))->state(fn (StudentFee $record): string => trim(implode(' ', array_filter([$record->student?->first_name, $record->student?->father_name, $record->student?->last_name]))))->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")))->weight('bold'),
            TextColumn::make('feeType.name')->label(self::label('نوع الرسم', 'Fee type'))->sortable(),
            TextColumn::make('amount')->label(self::label('المبلغ', 'Amount'))->money('SYP')->sortable(),
            TextColumn::make('paid_amount')->label(self::label('المدفوع', 'Paid'))->money('SYP')->sortable(),
            TextColumn::make('balance_amount')->label(self::label('المتبقي', 'Balance'))->money('SYP')->sortable(),
            TextColumn::make('status')->label(self::label('الحالة', 'Status'))->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))->badge()->color(fn (?string $state): string => self::statusColor((string) $state)),
        ])->filters([
            SelectFilter::make('status')->label(self::label('الحالة', 'Status'))->options(self::statusOptions()),
            SelectFilter::make('academic_year_id')->label(self::label('السنة الدراسية', 'Academic year'))->options(fn (): array => AcademicYear::query()->orderBy('sort_order')->pluck('name', 'id')->toArray())->searchable()->preload(),
        ])->recordActions([
            EditAction::make()->label(self::label('تعديل', 'Edit'))->slideOver()->modalWidth(Width::SevenExtraLarge)->visible(fn (StudentFee $record): bool => static::canEdit($record)),
        ]);
    }
    public static function getPages(): array { return ['index' => ManageStudentFees::route('/')]; }
    public static function getRelations(): array { return []; }
    public static function statusOptions(): array { return ['unpaid' => self::label('غير مدفوع', 'Unpaid'), 'partial' => self::label('مدفوع جزئيًا', 'Partial'), 'paid' => self::label('مدفوع', 'Paid'), 'cancelled' => self::label('ملغى', 'Cancelled')]; }
    public static function statusLabel(string $status): string { return self::statusOptions()[$status] ?? $status; }
    public static function statusColor(string $status): string { return match ($status) { 'paid' => 'success', 'partial' => 'warning', 'cancelled' => 'gray', default => 'danger' }; }
    private static function ltr(mixed $value): string { $value = trim((string) $value); return $value === '' ? '—' : '<span dir="ltr" style="unicode-bidi: plaintext; text-align: left; display: inline-block;">' . e($value) . '</span>'; }
    private static function label(string $ar, string $en): string { return app()->getLocale() === 'en' ? $en : $ar; }
}
