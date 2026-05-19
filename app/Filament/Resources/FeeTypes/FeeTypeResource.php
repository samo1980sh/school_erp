<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeTypes;

use App\Filament\Resources\FeeTypes\Pages\ManageFeeTypes;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Grade;
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
        return app()->getLocale() === 'en' ? 'Fee type' : 'نوع رسم';
    }
public static function getPluralModelLabel(): string
{
    return app()->getLocale() === 'en' ? 'Fee Types' : 'أنواع الرسوم';
}

public static function getNavigationLabel(): string
{
    return app()->getLocale() === 'en' ? 'Fee Types' : 'أنواع الرسوم';
}

public static function getNavigationGroup(): ?string
{
    return app()->getLocale() === 'en' ? 'Finance & Fees' : 'المالية والرسوم';
}
    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['academicYear:id,name', 'grade:id,name'])->orderBy('sort_order')->orderBy('name'))->columns([
            TextColumn::make('code')->label(self::label('الكود', 'Code'))->formatStateUsing(fn ($state): string => self::ltr($state))->html()->copyable()->searchable()->sortable(),
            TextColumn::make('name')->label(self::label('اسم الرسم', 'Fee name'))->searchable()->sortable()->weight('bold')->description(fn (FeeType $record): ?string => $record->grade?->name),
            TextColumn::make('academicYear.name')->label(self::label('السنة', 'Year'))->sortable(),
            TextColumn::make('amount')->label(self::label('المبلغ', 'Amount'))->money('SYP')->sortable(),
            TextColumn::make('due_on')->label(self::label('الاستحقاق', 'Due'))->date('Y-m-d')->sortable(),
            TextColumn::make('status')->label(self::label('الحالة', 'Status'))->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))->badge()->color(fn (?string $state): string => self::statusColor((string) $state)),
        ])->filters([
            SelectFilter::make('academic_year_id')->label(self::label('السنة الدراسية', 'Academic year'))->options(fn (): array => AcademicYear::query()->orderBy('sort_order')->pluck('name', 'id')->toArray())->searchable()->preload(),
            SelectFilter::make('status')->label(self::label('الحالة', 'Status'))->options(self::statusOptions()),
        ])->recordActions([
            EditAction::make()->label(self::label('تعديل', 'Edit'))->slideOver()->modalWidth(Width::SevenExtraLarge)->visible(fn (FeeType $record): bool => static::canEdit($record)),
        ]);
    }

    public static function getPages(): array { return ['index' => ManageFeeTypes::route('/')]; }
    public static function getRelations(): array { return []; }
    public static function statusOptions(): array { return ['active' => self::label('نشط', 'Active'), 'inactive' => self::label('غير نشط', 'Inactive'), 'archived' => self::label('مؤرشف', 'Archived')]; }
    public static function statusLabel(string $status): string { return self::statusOptions()[$status] ?? $status; }
    public static function statusColor(string $status): string { return match ($status) { 'active' => 'success', 'archived' => 'gray', default => 'warning' }; }
    private static function nextSortOrder(): int { return ((int) FeeType::query()->max('sort_order')) + 10; }
    private static function ltr(mixed $value): string { $value = trim((string) $value); return $value === '' ? '—' : '<span dir="ltr" style="unicode-bidi: plaintext; text-align: left; display: inline-block;">' . e($value) . '</span>'; }
    private static function label(string $ar, string $en): string { return app()->getLocale() === 'en' ? $en : $ar; }
}
