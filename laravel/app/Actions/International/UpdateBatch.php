<?php

namespace App\Actions\International;

use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\InternationalTada;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Replaces a batch: every employee is validated first, then the old rows are deleted and
 * the new ones inserted inside ONE transaction. The batch keeps its existing verifier.
 */
class UpdateBatch
{
    public function __construct(private ParseEmployeeData $parse)
    {
    }

    /**
     * @param  array<string, mixed>  $data  validated request data
     * @return array{ok:bool, message:string, warnings:array<int,string>}
     */
    public function handle(InternationalTada $batchHeader, array $data, int $userId): array
    {
        $batchId = $batchHeader->Batch_id;
        $formDate = Carbon::parse($data['form_date'])->toDateString();
        $start = Carbon::parse($data['travelDateStart'])->toDateString();
        $end = Carbon::parse($data['travelDateEnd'])->toDateString();
        $days = InternationalTada::daysBetween($start, $end);

        // Fiscal year scopes the chalani numbers; fall back to the batch's stored one.
        $fiscalYearId = FiscalYear::current()?->getKey() ?? $batchHeader->fiscal_year_master_id;

        // Step 1 - pre-validate every entry before touching existing rows.
        $parsed = ($this->parse)($data['employee_data'], 'Invalid format: ');
        $preErrors = $parsed['errors'];
        $payloads = [];
        foreach ($parsed['entries'] as $entry) {
            $empCode = $entry['code'];
            try {
                $level = Employee::query()->with('tadaLevel')->find($empCode)?->tadaLevel;
            } catch (\Throwable $e) {
                $preErrors[] = "Employee {$empCode}: query failed";

                continue;
            }
            if (! $level || empty($level->TadaDefinerMasterBylevel_id)) {
                $preErrors[] = "Employee {$empCode}: no TADA level assigned — skipped";

                continue;
            }
            $payloads[] = [
                'EmpPersonalCode' => $empCode,
                'DressAllowance' => $entry['dress'],
                'TadaDefinerMasterBylevel_id' => (int) $level->TadaDefinerMasterBylevel_id,
                'totalUSdrecevid' => InternationalTada::storedUsd($days, $level->tadaInUSD),
            ];
        }

        if (empty($payloads)) {
            return [
                'ok' => false,
                'message' => "❌ No valid employees to update.\n\n".implode("\n", $preErrors),
                'warnings' => [],
            ];
        }

        // Step 2 - delete + re-insert inside one transaction.
        $insertErrors = [];
        $first = null;
        $last = null;

        try {
            DB::transaction(function () use ($batchId, $payloads, $fiscalYearId, $formDate, $start, $end, $data, $userId, $batchHeader, &$insertErrors, &$first, &$last) {
                InternationalTada::batch($batchId)->delete();

                foreach ($payloads as $payload) {
                    // Re-queried inside the transaction so the just-deleted rows are not counted.
                    $chalaniId = InternationalTada::nextChalaniNumber($fiscalYearId);
                    $first ??= $chalaniId;
                    $last = $chalaniId;

                    try {
                        InternationalTada::create($payload + [
                            'Batch_id' => $batchId,
                            'fiscal_year_master_id' => $fiscalYearId,
                            'Chalani_id' => $chalaniId,
                            'form_date' => $formDate,
                            'Country_id' => (int) $data['country_id'],
                            'City_id' => (int) $data['city_id'],
                            'travel_objective' => trim($data['travel_objective']),
                            'travelDateStart' => $start,
                            'travelDateEnd' => $end,
                            'createdBy' => $userId,
                            'tadaverifier_id' => $batchHeader->tadaverifier_id,
                        ]);
                    } catch (\Throwable $e) {
                        $insertErrors[] = "Employee {$payload['EmpPersonalCode']}: insert failed - ".$e->getMessage();

                        throw new \RuntimeException('insert failed', 0, $e); // rolls everything back
                    }
                }
            });
        } catch (\Throwable $e) {
            if ($insertErrors === []) {
                return ['ok' => false, 'message' => '❌ Error deleting existing records: '.$e->getMessage(), 'warnings' => []];
            }

            return [
                'ok' => false,
                'message' => "❌ Update failed — original records have been kept.\n".implode("\n", array_merge($preErrors, $insertErrors)),
                'warnings' => [],
            ];
        }

        return [
            'ok' => true,
            'message' => "✅ Batch {$batchId} updated successfully!\n".InternationalTada::chalaniRange($first, $last)."\nEmployees: ".count($payloads),
            'warnings' => $preErrors,
        ];
    }
}
