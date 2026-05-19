<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentFinancialBalances;

use App\Filament\Resources\StudentFinancialBalances\Pages\ManageStudentFinancialBalances;
use App\Models\AcademicYear;
use App\Models\StudentFinancialBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class StudentFinancialBalanceResource extends Resource
{
    protected static ?string $model = StudentFinancialBalance::class;

    protected static ?string $slug = 'student-financial-balances';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'student_name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('رصيد طالب', 'Student balance');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('أرصدة الطلاب', 'Student balances');
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'en' ? 'Student Balances' : 'أرصدة الطلاب';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'en' ? 'Finance & Fees' : 'المالية والرسوم';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('fees.reports') || auth()->user()?->can('fees.view') || false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->orderBy('academic_year_name')
                    ->orderBy('student_name')
            )
            ->columns([
                TextColumn::make('student_number')
                    ->label(self::label('رقم الطالب', 'Student number'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(self::ltrAttributes()),

                TextColumn::make('student_name')
                    ->label(self::label('الطالب', 'Student'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (StudentFinancialBalance $record): ?string => $record->academic_year_name),

                TextColumn::make('fees_count')
                    ->label(self::label('عدد الرسوم', 'Fees'))
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('total_fees')
                    ->label(self::label('إجمالي الرسوم', 'Total fees'))
                    ->money('SYP')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('total_paid')
                    ->label(self::label('إجمالي المدفوع', 'Total paid'))
                    ->money('SYP')
                    ->alignEnd()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('total_remaining')
                    ->label(self::label('المتبقي', 'Remaining'))
                    ->money('SYP')
                    ->alignEnd()
                    ->color(fn (StudentFinancialBalance $record): string => (float) $record->total_remaining > 0 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('last_payment_date')
                    ->label(self::label('آخر دفعة', 'Last payment'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('balance_status')
                    ->label(self::label('الحالة', 'Status'))
                    ->state(fn (StudentFinancialBalance $record): string => self::balanceStatusLabel($record->balance_status))
                    ->badge()
                    ->color(fn (StudentFinancialBalance $record): string => self::balanceStatusColor($record->balance_status))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total_remaining', $direction)),

                TextColumn::make('overdue_fees_count')
                    ->label(self::label('متأخرات', 'Overdue'))
                    ->badge()
                    ->color(fn (StudentFinancialBalance $record): string => (int) $record->overdue_fees_count > 0 ? 'danger' : 'gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label(self::label('السنة الدراسية', 'Academic year'))
                    ->options(fn (): array => AcademicYear::query()
                        ->orderBy('sort_order')
                        ->orderByDesc('starts_on')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('balance_status_filter')
                    ->label(self::label('حالة الرصيد', 'Balance status'))
                    ->options(self::balanceStatusOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'paid' => $query->where('total_remaining', '<=', 0),
                            'partial' => $query->where('total_remaining', '>', 0)->where('total_paid', '>', 0)->where('overdue_fees_count', '=', 0),
                            'unpaid' => $query->where('total_remaining', '>', 0)->where('total_paid', '<=', 0)->where('overdue_fees_count', '=', 0),
                            'overdue' => $query->where('total_remaining', '>', 0)->where('overdue_fees_count', '>', 0),
                            default => $query,
                        };
                    })
                    ->native(false),
            ])
            ->emptyStateHeading(self::label('لا توجد أرصدة طلاب', 'No student balances found'))
            ->emptyStateDescription(self::label(
                'تظهر الأرصدة بعد إضافة رسوم للطلاب وتشغيل بيانات المالية.',
                'Balances appear after student fees are created and finance data is available.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentFinancialBalances::route('/'),
        ];
    }

    public static function balanceStatusOptions(): array
    {
        return [
            'paid' => self::label('مسدد', 'Paid'),
            'partial' => self::label('مدفوع جزئيًا', 'Partially paid'),
            'unpaid' => self::label('غير مسدد', 'Unpaid'),
            'overdue' => self::label('متأخر', 'Overdue'),
        ];
    }

    public static function balanceStatusLabel(string $status): string
    {
        return self::balanceStatusOptions()[$status] ?? $status;
    }

    public static function balanceStatusColor(string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'partial' => 'warning',
            'overdue' => 'danger',
            default => 'gray',
        };
    }

    public static function ltrAttributes(): array
    {
        return [
            'dir' => 'ltr',
            'style' => 'unicode-bidi: plaintext; text-align: left; display: inline-block;',
        ];
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
