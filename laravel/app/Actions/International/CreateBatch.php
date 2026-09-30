<?php

namespace App\Actions\International;

use App\Models\Employee;
use App\Models\InternationalTada;
use Carbon\Carbon;

/**
 * Creates a batch row by row. Deliberately NOT transactional: an employee that fails only
 * produces a warning while the others are still inserted.
 */
class CreateBatch
{
    public function __construct(private ParseEmployeeData $parse)
    {
    }

    /**
     * @param  array<string, mixed>  $data  validated request data
     * @return array{batchId:string, inserted:int, first:?int, last:?int, errors:array<int,string>}
     */
    public function handle(array $data, int $fiscalYearId, int $userId): array
    {
        $formDate = Carbon::parse($data['form_date'])->toDateString();
        $start = Carbon::parse($data['travelDateStart'])->toDateString();
        $end = Carbon::parse($data['travelDateEnd'])->toDateString();
        $days = InternationalTada::daysBetween($start, $end);

        $batchId = InternationalTada::nextBatchId();
        $parsed = ($this->parse)($data['employee_data']);
        $errors = $parsed['errors'];
        $inserted = 0;
        $first = null;
        $last = null;

        foreach ($parsed['entries'] as $entry) {
            $empCode = $entry['code'];

            $chalaniId = InternationalTada::nextChalaniNumber($fiscalYearId);
            $first ??= $chalaniId;
            $last = $chalaniId;

            try {
                $level = Employee::query()->with('tadaLevel')->find($empCode)?->tadaLevel;
            } catch (\Throwable $e) {
                $errors[] = "Employee {$empCode}: Query failed - ".$e->getMessage();

                continue;
            }
            if (! $level || empty($level->TadaDefinerMasterBylevel_id)) {
                $errors[] = "Employee {$empCode}: TADA info missing";

                continue;
            }

            try {
                InternationalTada::create([
                    'Batch_id' => $batchId,
                    'fiscal_year_master_id' => $fiscalYearId,
                    'Chalani_id' => $chalaniId,
                    'form_date' => $formDate,
                    'EmpPersonalCode' => $empCode,
                    'Country_id' => (int) $data['country_id'],
                    'City_id' => (int) $data['city_id'],
                    'travel_objective' => trim($data['travel_objective']),
                    'travelDateStart' => $start,
                    'travelDateEnd' => $end,
                    'TadaDefinerMasterBylevel_id' => (int) $level->TadaDefinerMasterBylevel_id,
                    'totalUSdrecevid' => InternationalTada::storedUsd($days, $level->tadaInUSD),
                    'DressAllowance' => $entry['dress'],
                    'createdBy' => $userId,
                    'tadaverifier_id' => (int) $data['tadaverifier_id'],
                ]);
            } catch (\Throwable $e) {
                $errors[] = "Employee {$empCode}: Insert failed - ".$e->getMessage();

                continue;
            }
            $inserted++;
        }

        return ['batchId' => $batchId, 'inserted' => $inserted, 'first' => $first, 'last' => $last, 'errors' => $errors];
    }
}
