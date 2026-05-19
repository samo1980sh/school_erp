<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentFinancialBalances\Pages;

use App\Exports\StudentFinancialBalancesExport;
use App\Filament\Resources\StudentFinancialBalances\StudentFinancialBalanceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageStudentFinancialBalances extends ManageRecords
{
    protected static string $resource = StudentFinancialBalanceResource::class;

    public function getTitle(): string
    {
        return app()->getLocale() === 'en' ? 'Student balances' : 'أرصدة الطلاب';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label(app()->getLocale() === 'en' ? 'Export Excel' : 'تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('fees.export') ?? false)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new StudentFinancialBalancesExport(),
                    'student-balances-export.xlsx'
                )),
        ];
    }
}
