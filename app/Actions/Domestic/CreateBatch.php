<?php

namespace App\Actions\Domestic;

use App\Models\DomesticTada;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Creates a new domestic TADA batch: one row per employee.
 *
 * Deliberately row by row and WITHOUT a transaction (legacy behaviour): a failing
 * employee becomes a warning while the others are still saved. Chalani numbers are
 * global (not per fiscal year) when creating.
 */
class CreateBatch
{
    /**
     * @param  array<string,mixed>  $attributes  batch-wide DomesticTada columns (see StoreDomesticTadaRequest::batchAttributes())
     * @param  string  $employeeData  comma separated employee codes
     * @return array{batchId:string, inserted:int, errors:string[], firstChalani:?int, lastChalani:?int}
     */
    public function __invoke(array $attributes, bool $twentyPercentExtra, string $employeeData, int $fiscalYearId, int|string $userId): array
    {
        $batchId = DomesticTada::nextBatchId();
        $days = DomesticTada::inclusiveDays($attributes['domestic_travelDateStart'], $attributes['domestic_travelDateEnd']);

        $inserted = 0;
        $errors = [];
        $first = $last = null;

        foreach (explode(',', $employeeData) as $entry) {
            $code = trim($entry);
            if ($code === '') {
                continue;
            }

            $chalani = DomesticTada::nextChalani();
            $first ??= $chalani;
            $last = $chalani;

            try {
                $employee = Employee::with('level')->find($code);
            } catch (Throwable) {
                $errors[] = "Employee {$code}: Query failed";

                continue;
            }
            if (! $employee) {
                $errors[] = "Employee {$code}: Not found in database";

                continue;
            }

            try {
                DomesticTada::create($attributes + [
                    'domestic_Batch_id' => $batchId,
                    'domestic_Chalani_id' => $chalani,
                    'EmpPersonalCode' => $code,
                    'domestic_isTwentyPercentExtra' => $twentyPercentExtra,
                    'domestic_tada' => DomesticTada::calculateTada($days, $employee->domestic_rate, $twentyPercentExtra),
                    'domestic_createdBy' => $userId,
                    'fiscal_year_master_id' => $fiscalYearId,
                ]);
                $inserted++;
            } catch (Throwable $e) {
                $errors[] = "Employee {$code}: ".($e->getPrevious()?->getMessage() ?? $e->getMessage());
                Log::error('SQL Insert Error: '.$e->getMessage());
            }
        }

        return ['batchId' => $batchId, 'inserted' => $inserted, 'errors' => $errors, 'firstChalani' => $first, 'lastChalani' => $last];
    }
}
