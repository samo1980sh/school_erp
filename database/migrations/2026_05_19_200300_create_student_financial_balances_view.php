<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS student_financial_balances');

        $dueDateExpression = Schema::hasColumn('student_fees', 'due_on')
            ? 'sf.due_on'
            : (Schema::hasColumn('student_fees', 'due_date') ? 'sf.due_date' : 'NULL');

        $remainingExpression = 'GREATEST(COALESCE(sf.amount, 0) - COALESCE(sf.paid_amount, 0), 0)';

        DB::statement("
            CREATE VIEW student_financial_balances AS
            SELECT
                MIN(sf.id) AS id,
                sf.student_id,
                sf.academic_year_id,
                COALESCE(s.student_number, '') AS student_number,
                TRIM(CONCAT_WS(' ',
                    NULLIF(s.first_name, ''),
                    NULLIF(s.father_name, ''),
                    NULLIF(s.last_name, '')
                )) AS student_name,
                ay.name AS academic_year_name,
                COUNT(sf.id) AS fees_count,
                SUM(COALESCE(sf.amount, 0)) AS total_fees,
                SUM(COALESCE(sf.paid_amount, 0)) AS total_paid,
                SUM({$remainingExpression}) AS total_remaining,
                SUM(CASE
                    WHEN {$remainingExpression} > 0
                        AND {$dueDateExpression} IS NOT NULL
                        AND {$dueDateExpression} < CURRENT_DATE
                    THEN 1
                    ELSE 0
                END) AS overdue_fees_count,
                (
                    SELECT MAX(sp.paid_on)
                    FROM student_payments sp
                    WHERE sp.student_id = sf.student_id
                        AND sp.academic_year_id = sf.academic_year_id
                ) AS last_payment_date
            FROM student_fees sf
            INNER JOIN students s ON s.id = sf.student_id
            INNER JOIN academic_years ay ON ay.id = sf.academic_year_id
            GROUP BY
                sf.student_id,
                sf.academic_year_id,
                s.student_number,
                s.first_name,
                s.father_name,
                s.last_name,
                ay.name
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS student_financial_balances');
    }
};