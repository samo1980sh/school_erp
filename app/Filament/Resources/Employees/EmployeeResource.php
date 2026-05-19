<?php

declare(strict_types=1);

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Employees\Pages\ManageEmployees;
use App\Models\Employee;
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

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $slug = 'employees';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return self::label('موظف', 'Employee');
    }

    public static function getPluralModelLabel(): string
    {
        return self::label('الموظفون', 'Employees');
    }

    public static function getNavigationLabel(): string
    {
        return self::label('الموظفون', 'Employees');
    }

    public static function getNavigationGroup(): ?string
    {
        return self::label('إدارة الأشخاص', 'People Management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('employees.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('employees.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('employees.update') ?? false;
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
                Section::make(self::label('البيانات الأساسية', 'Basic information'))
                    ->description(self::label(
                        'أدخل بيانات الموظف الأساسية ورقمه الوظيفي. الأرقام تظهر باتجاه LTR لمنع انعكاسها في الواجهة العربية.',
                        'Enter the employee basic data and employee number. Numeric values are displayed LTR to prevent reversal in RTL views.'
                    ))
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(self::label('ترتيب العرض', 'Display order'))
                            ->numeric()
                            ->rules(['integer', 'min:0'])
                            ->default(fn (): int => self::nextSortOrder())
                            ->required(),

                        TextInput::make('employee_number')
                            ->label(self::label('رقم الموظف', 'Employee number'))
                            ->required()
                            ->unique(table: 'employees', column: 'employee_number', ignoreRecord: true)
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left;']),

                        TextInput::make('first_name')
                            ->label(self::label('الاسم الأول', 'First name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('father_name')
                            ->label(self::label('اسم الأب', 'Father name'))
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->label(self::label('الكنية', 'Last name'))
                            ->maxLength(255),

                        Select::make('gender')
                            ->label(self::label('الجنس', 'Gender'))
                            ->options(self::genderOptions())
                            ->default('male')
                            ->required()
                            ->native(false),

                        DatePicker::make('birth_date')
                            ->label(self::label('تاريخ الميلاد', 'Birth date'))
                            ->native(false),

                        TextInput::make('national_id')
                            ->label(self::label('الرقم الوطني', 'National ID'))
                            ->unique(table: 'employees', column: 'national_id', ignoreRecord: true)
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left;']),

                        Select::make('marital_status')
                            ->label(self::label('الحالة الاجتماعية', 'Marital status'))
                            ->options(self::maritalStatusOptions())
                            ->native(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),

                Section::make(self::label('البيانات الوظيفية', 'Employment information'))
                    ->description(self::label(
                        'حدد المسمى الوظيفي والقسم ونوع العقد وحالة الموظف.',
                        'Set the job title, department, contract type, and employee status.'
                    ))
                    ->schema([
                        TextInput::make('job_title')
                            ->label(self::label('المسمى الوظيفي', 'Job title'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('department')
                            ->label(self::label('القسم', 'Department'))
                            ->maxLength(255),

                        Select::make('employment_type')
                            ->label(self::label('نوع الموظف', 'Employment type'))
                            ->options(self::employmentTypeOptions())
                            ->default('administrative')
                            ->required()
                            ->native(false),

                        DatePicker::make('hire_date')
                            ->label(self::label('تاريخ التعيين', 'Hire date'))
                            ->native(false),

                        Select::make('contract_type')
                            ->label(self::label('نوع العقد', 'Contract type'))
                            ->options(self::contractTypeOptions())
                            ->native(false),

                        Select::make('status')
                            ->label(self::label('الحالة', 'Status'))
                            ->options(self::statusOptions())
                            ->default('active')
                            ->required()
                            ->native(false),

                        Toggle::make('is_active')
                            ->label(self::label('مفعل', 'Active'))
                            ->default(true),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ]),

                Section::make(self::label('معلومات التواصل والمؤهلات', 'Contact and qualifications'))
                    ->description(self::label(
                        'أدخل معلومات التواصل والمؤهل العلمي والتخصص. الهواتف تظهر باتجاه LTR.',
                        'Enter contact information, qualification, and specialization. Phone numbers are displayed LTR.'
                    ))
                    ->schema([
                        TextInput::make('email')
                            ->label(self::label('البريد الإلكتروني', 'Email'))
                            ->email()
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left;']),

                        TextInput::make('phone')
                            ->label(self::label('الهاتف', 'Phone'))
                            ->tel()
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left;']),

                        TextInput::make('mobile')
                            ->label(self::label('الجوال', 'Mobile'))
                            ->tel()
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: plaintext; text-align: left;']),

                        TextInput::make('address')
                            ->label(self::label('العنوان', 'Address'))
                            ->maxLength(255),

                        TextInput::make('qualification')
                            ->label(self::label('المؤهل العلمي', 'Qualification'))
                            ->maxLength(255),

                        TextInput::make('specialization')
                            ->label(self::label('التخصص', 'Specialization'))
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
                    ->orderBy('sort_order')
                    ->orderBy('employee_number')
            )
            ->columns([
                TextColumn::make('employee_number')
                    ->label(self::label('رقم الموظف', 'Employee no.'))
                    ->formatStateUsing(fn (mixed $state): string => self::ltrText($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label(self::label('الموظف', 'Employee'))
                    ->state(fn (Employee $record): string => $record->display_name)
                    ->searchable(['first_name', 'father_name', 'last_name'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('first_name', $direction))
                    ->weight('bold')
                    ->description(fn (Employee $record): ?string => $record->job_title),

                TextColumn::make('department')
                    ->label(self::label('القسم', 'Department'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('mobile')
                    ->label(self::label('الجوال', 'Mobile'))
                    ->formatStateUsing(fn (mixed $state): string => self::ltrText($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label(self::label('الهاتف', 'Phone'))
                    ->formatStateUsing(fn (mixed $state): string => self::ltrText($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('national_id')
                    ->label(self::label('الرقم الوطني', 'National ID'))
                    ->formatStateUsing(fn (mixed $state): string => self::ltrText($state))
                    ->html()
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => self::statusColor((string) $state))
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label(self::label('مفعل', 'Active'))
                    ->state(fn (Employee $record): string => $record->is_active ? self::label('نعم', 'Yes') : self::label('لا', 'No'))
                    ->badge()
                    ->color(fn (Employee $record): string => $record->is_active ? 'success' : 'gray')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->label(self::label('القسم', 'Department'))
                    ->options(fn (): array => Employee::query()
                        ->whereNotNull('department')
                        ->where('department', '<>', '')
                        ->distinct()
                        ->orderBy('department')
                        ->pluck('department', 'department')
                        ->toArray())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(self::label('الحالة', 'Status'))
                    ->options(self::statusOptions())
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(self::label('تعديل', 'Edit'))
                    ->slideOver()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (Employee $record): bool => static::canEdit($record))
                    ->successNotificationTitle(self::label('تم تحديث الموظف بنجاح', 'Employee updated successfully')),
            ])
            ->emptyStateHeading(self::label('لا يوجد موظفون', 'No employees found'))
            ->emptyStateDescription(self::label(
                'ابدأ بإضافة موظف جديد أو استورد بيانات الموظفين من ملف Excel.',
                'Start by adding a new employee or import employees from Excel.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEmployees::route('/'),
        ];
    }

    public static function genderOptions(): array
    {
        return [
            'male' => self::label('ذكر', 'Male'),
            'female' => self::label('أنثى', 'Female'),
        ];
    }

    public static function maritalStatusOptions(): array
    {
        return [
            'single' => self::label('أعزب/عزباء', 'Single'),
            'married' => self::label('متزوج/متزوجة', 'Married'),
            'other' => self::label('أخرى', 'Other'),
        ];
    }

    public static function employmentTypeOptions(): array
    {
        return [
            'administrative' => self::label('إداري', 'Administrative'),
            'supervision' => self::label('إشراف', 'Supervision'),
            'finance' => self::label('مالي', 'Finance'),
            'operations' => self::label('تشغيلي', 'Operations'),
        ];
    }

    public static function contractTypeOptions(): array
    {
        return [
            'full_time' => self::label('دوام كامل', 'Full time'),
            'part_time' => self::label('دوام جزئي', 'Part time'),
            'temporary' => self::label('مؤقت', 'Temporary'),
            'contractor' => self::label('متعاقد', 'Contractor'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active' => self::label('نشط', 'Active'),
            'inactive' => self::label('غير نشط', 'Inactive'),
            'on_leave' => self::label('في إجازة', 'On leave'),
            'ended' => self::label('منتهي الخدمة', 'Ended'),
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
            'on_leave' => 'warning',
            'ended' => 'danger',
            default => 'gray',
        };
    }

    private static function nextSortOrder(): int
    {
        return ((int) Employee::query()->max('sort_order')) + 10;
    }

    private static function ltrText(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '—';
        }

        return '<span dir="ltr" style="unicode-bidi: plaintext; text-align: left; display: inline-block;">' . e($value) . '</span>';
    }

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}
