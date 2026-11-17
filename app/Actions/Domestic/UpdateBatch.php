<?php

namespace App\Actions\Domestic;

use App\Models\DomesticTada;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Updates a domestic TADA batch from the edit form: rows of employees still in the form are
 * updated, new employees are inserted, removed employees are deleted - all in ONE transaction.
 * Chalani numbers of newly added employees are per fiscal year (preserved legacy quirk).
 */
class UpdateBatch
{
    /**
     * @param  array<string,mixed>  $attributes  batch-wide DomesticTada columns
     * @param  string  $employeeData  "code:flag,code:flag" (flag 1 = 20% extra)
     * @return array{ok:bool, noValidEmployees:bool, updated:int, inserted:int, deleted:int, warnings:string[], errors:string[]}
     */
    public function __invoke(string $batchId, array $attributes, string $employeeData, int $fiscalYearId, int|string $userId): array
    {
        $days = DomesticTada::inclusiveDays($attributes['domestic_travelDateStart'], $attributes['domestic_travelDateEnd']);
        [$payloads, $warnings] = $this->parseEmployees($employeeData, $days);

        $result = ['ok' => false, 'noValidEmployees' => false, 'updated' => 0, 'inserted' => 0, 'deleted' => 0, 'warnings' => $warnings, 'errors' => []];

        if ($payloads === []) {
            return ['noValidEmployees' => true] + $result;
        }

        $existing = DomesticTada::batch($batchId)->get()->keyBy('EmpPersonalCode');
        $attributes += ['fiscal_year_master_id' => $fiscalYearId];

        try {
            DB::transaction(function () use ($batchId, $attributes, $payloads, $existing, $userId, $fiscalYearId, &$result) {
                foreach ($payloads as $code => $payload) {
                    $row = $existing->get($code);
                    if ($row) {
                        $this->guard("UPDATE failed for emp {$code}", fn () => $row->update($attributes + $payload));
                        $result['updated']++;
                    }
                }

                foreach ($payloads as $code => $payload) {
                    if (! $existing->has($code)) {
                        $this->guard("INSERT failed for emp {$code}", fn () => DomesticTada::create($attributes + $payload + [
                            'domestic_Batch_id' => $batchId,
                            'domestic_Chalani_id' => DomesticTada::nextChalani($fiscalYearId),
                            'EmpPersonalCode' => $code,
                            'domestic_createdBy' => $userId,
                        ]));
                        $result['inserted']++;
                    }
                }

                foreach ($existing as $code => $row) {
                    if (! isset($payloads[$code])) {
                        $this->guard("DELETE failed for tada_id {$row->getKey()}", fn () => $row->delete());
                        $result['deleted']++;
                    }
                }
            });
        } catch (Throwable $e) {
            return ['updated' => 0, 'inserted' => 0, 'deleted' => 0, 'errors' => [$e->getMessage()]] + $result;
        }

        return ['ok' => true] + $result;
    }

    /**
     * Turn the form's "code:flag,..." string into per-employee columns (keyed by employee code).
     *
     * @return array{0:array<string,array<string,mixed>>, 1:string[]}  [payloads, warnings]
     */
    private function parseEmployees(string $employeeData, int $days): array
    {
        $payloads = [];
        $warnings = [];

        foreach (explode(',', $employeeData) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            $parts = explode(':', $entry);
            if (count($parts) !== 2) {
                $warnings[] = "Invalid format: {$entry}";

                continue;
            }

            $code = trim($parts[0]);
            $extra = (int) $parts[1] === 1;

            try {
                $employee = Employee::with('level')->find($code);
            } catch (Throwable) {
                $warnings[] = "Employee {$code}: query failed";

                continue;
            }
            if (! $employee) {
                $warnings[] = "Employee {$code}: not found";

                continue;
            }

            $payloads[$code] = [
                'domestic_isTwentyPercentExtra' => $extra,
                'domestic_tada' => DomesticTada::calculateTada($days, $employee->domestic_rate, $extra),
            ];
        }

        return [$payloads, $warnings];
    }

    /** Run one write and report SQL failures with the driver message (the transaction is rolled back). */
    private function guard(string $context, callable $write): void
    {
        try {
            $write();
        } catch (Throwable $e) {
            throw new RuntimeException("{$context}: ".($e->getPrevious()?->getMessage() ?? $e->getMessage()));
        }
    }
}
