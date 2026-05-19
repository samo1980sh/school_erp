<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Grade;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class FeeTypesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $year = AcademicYear::query()->where('is_current', true)->first();

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code === '' || $name === '' || ! $year) {
                continue;
            }

            $gradeName = trim((string) ($row['grade'] ?? ''));
            $grade = $gradeName !== '' ? Grade::query()->where('name', $gradeName)->first() : null;

            FeeType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'academic_year_id' => $year->id,
                    'grade_id' => $grade?->id,
                    'sort_order' => (int) FeeType::query()->max('sort_order') + 10,
                    'name' => $name,
                    'amount' => (float) ($row['amount'] ?? 0),
                    'due_on' => filled($row['due_on'] ?? null) ? Carbon::parse($row['due_on']) : null,
                    'status' => trim((string) ($row['status'] ?? 'active')) ?: 'active',
                    'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                ]
            );
        }
    }
}
