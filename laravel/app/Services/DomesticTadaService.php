<?php

namespace App\Services;

use App\Models\District;
use App\Models\DomesticTada;
use App\Models\FiscalYear;
use App\Models\TadaVerifier;
use App\Models\TravelType;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared business logic of the domestic TADA module (batch ids, chalani numbers,
 * fiscal-year lookup, TADA calculation, lookups and the listing/print queries).
 */
class DomesticTadaService
{
    public const DEFAULT_TADA_RATE = 2400;

    // ─── Lookups ────────────────────────────────────────────────────────────────

    /** Active fiscal year that contains today's date (legacy getCurrentFiscalYearId). */
    public function currentFiscalYearId(): ?int
    {
        $id = FiscalYear::query()
            ->whereRaw('? BETWEEN fy_startdate AND fy_enddate', [date('Y-m-d')])
            ->where('fy_status', 'ACTIVE')
            ->value('fiscal_year_master_id');

        return $id === null ? null : (int) $id;
    }

    public function fiscalYearInfo(int $id): ?object
    {
        return FiscalYear::query()
            ->where('fiscal_year_master_id', $id)
            ->first(['fy', 'fy_startdate', 'fy_enddate']);
    }

    public function districts(): Collection
    {
        return District::query()->orderBy('District_name')->get(['District_id', 'District_name']);
    }

    public function travelTypes(): Collection
    {
        return TravelType::query()->orderBy('type')->get(['TadaTypeMaster_id', 'type']);
    }

    public function verifiers(): Collection
    {
        return TadaVerifier::query()->orderBy('tadaverifierPost')->get(['tadaverifier_id', 'tadaverifierPost']);
    }

    /** Employee + level/TADA rate query (rate defaults to 2400 when the level has none). */
    private function employeeRateQuery()
    {
        return DB::table('Employee_Information as e')
            ->leftJoin('DomesticTadaDefinerMasterBylevel as t', function ($join) {
                $join->whereRaw('LTRIM(RTRIM(e.LevelName)) = LTRIM(RTRIM(t.DomesticTadaDefinerMasterBylevel_name))');
            });
    }

    /** All employees with their per-day rate (Add page dropdown). */
    public function employeesWithRate(): Collection
    {
        return $this->employeeRateQuery()
            ->selectRaw('e.EmpPersonalCode, e.EmpName, e.LevelName, e.LevelName_id,
                ISNULL(t.DomesticTadaDefinerMasterBylevel_id, 0) AS DomesticTadaDefinerMasterBylevel_id,
                ISNULL(t.DomesticTadaDefinerMasterBylevel_name, e.LevelName) AS DomesticTadaDefinerMasterBylevel_name,
                COALESCE(NULLIF(t.tadaInNepali, 0), ?) AS tadaInNepali', [self::DEFAULT_TADA_RATE])
            ->orderBy('e.EmpName')
            ->get();
    }

    /** One employee with the per-day rate, or null. */
    public function employeeWithRate(string $code): ?object
    {
        return $this->employeeRateQuery()
            ->selectRaw('e.EmpPersonalCode, e.LevelName, COALESCE(NULLIF(t.tadaInNepali, 0), ?) AS tadaInNepali', [self::DEFAULT_TADA_RATE])
            ->where('e.EmpPersonalCode', $code)
            ->first();
    }

    // ─── Numbering ──────────────────────────────────────────────────────────────

    public function nextBatchId(): string
    {
        try {
            $max = DomesticTada::query()->max('domestic_Batch_id');
        } catch (\Throwable $e) {
            return 'DBATCH001';
        }
        if (empty($max)) {
            return 'DBATCH001';
        }

        return 'DBATCH'.str_pad((string) ((int) substr($max, 6) + 1), 3, '0', STR_PAD_LEFT);
    }

    /** Next chalani number; scoped to a fiscal year when given (Edit page), global otherwise (Add page). */
    public function nextChalani(?int $fiscalYearId = null): int
    {
        try {
            $query = DomesticTada::query();
            if (! empty($fiscalYearId)) {
                $query->where('fiscal_year_master_id', $fiscalYearId);
            }

            return (int) $query->max('domestic_Chalani_id') + 1;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    // ─── Calculation ────────────────────────────────────────────────────────────

    /** Inclusive number of days between two dates. */
    public function totalDays(string $start, string $end): int
    {
        return (int) Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->startOfDay(), false) + 1;
    }

    public function calculateTada(int $days, float $ratePerDay, bool $twentyPercentExtra): float
    {
        $total = $days * $ratePerDay;

        return $twentyPercentExtra ? $total * 1.20 : $total;
    }

    // ─── Add ────────────────────────────────────────────────────────────────────

    /**
     * Insert one row per employee (legacy behaviour: row by row, per-employee errors become warnings).
     *
     * @return array{inserted:int, errors:string[], firstChalani:?int, lastChalani:?int}
     */
    public function createBatch(string $batchId, array $data, array $empCodes, int $fiscalYearId, $userId): array
    {
        $inserted = 0;
        $errors = [];
        $first = null;
        $last = null;

        $days = $this->totalDays($data['travelDateStart'], $data['travelDateEnd']);

        foreach ($empCodes as $entry) {
            $empCode = trim($entry);
            if ($empCode === '') {
                continue;
            }

            $chalani = $this->nextChalani();
            $first ??= $chalani;
            $last = $chalani;

            try {
                $emp = $this->employeeWithRate($empCode);
            } catch (\Throwable $e) {
                $errors[] = "Employee {$empCode}: Query failed";
                continue;
            }
            if (! $emp) {
                $errors[] = "Employee {$empCode}: Not found in database";
                continue;
            }

            $total = $this->calculateTada($days, (float) $emp->tadaInNepali, (bool) $data['is_twenty_percent_extra']);

            try {
                DomesticTada::query()->insert([
                    'domestic_Batch_id' => $batchId,
                    'domestic_Chalani_id' => $chalani,
                    'domestic_form_date' => $data['form_date'],
                    'EmpPersonalCode' => $empCode,
                    'District_id' => $data['district_id'],
                    'domestic_isTwentyPercentExtra' => $data['is_twenty_percent_extra'],
                    'TadaTypeMaster_id' => $data['tada_type_id'],
                    'domestic_travel_objective' => $data['travel_objective'],
                    'domestic_travelDateStart' => $data['travelDateStart'],
                    'domestic_travelDateEnd' => $data['travelDateEnd'],
                    'domestic_tada' => $total,
                    'domestic_createdBy' => $userId,
                    'domestic_createddate' => DB::raw('GETDATE()'),
                    'fiscal_year_master_id' => $fiscalYearId,
                    'tadaverifier_id' => $data['tadaverifier_id'],
                ]);
                $inserted++;
            } catch (\Throwable $e) {
                $errors[] = "Employee {$empCode}: ".$this->sqlMessage($e);
                Log::error('SQL Insert Error: '.$e->getMessage());
            }
        }

        return ['inserted' => $inserted, 'errors' => $errors, 'firstChalani' => $first, 'lastChalani' => $last];
    }

    // ─── Edit ───────────────────────────────────────────────────────────────────

    public function batchHeader(string $batchId): ?object
    {
        return DomesticTada::query()
            ->where('domestic_Batch_id', $batchId)
            ->first([
                'domestic_Batch_id', 'domestic_Chalani_id', 'domestic_form_date', 'District_id',
                'domestic_travel_objective', 'domestic_travelDateStart', 'domestic_travelDateEnd',
                'TadaTypeMaster_id', 'fiscal_year_master_id', 'tadaverifier_id',
            ]);
    }

    /** Employees already in the batch. */
    public function batchEmployees(string $batchId): Collection
    {
        return DB::table('DomesticTada as dt')
            ->leftJoin('Employee_Information as e', 'dt.EmpPersonalCode', '=', 'e.EmpPersonalCode')
            ->leftJoin('DomesticTadaDefinerMasterBylevel as dtd', function ($join) {
                $join->whereRaw('LTRIM(RTRIM(e.LevelName)) = LTRIM(RTRIM(dtd.DomesticTadaDefinerMasterBylevel_name))');
            })
            ->selectRaw('dt.domestic_tada_id, dt.EmpPersonalCode, dt.domestic_tada, dt.domestic_totalday,
                dt.domestic_isTwentyPercentExtra, e.EmpName, e.LevelName,
                ISNULL(dtd.DomesticTadaDefinerMasterBylevel_name, e.LevelName) AS DomesticTadaDefinerMasterBylevel_name,
                COALESCE(NULLIF(dtd.tadaInNepali, 0), ?) AS tadaInNepali', [self::DEFAULT_TADA_RATE])
            ->where('dt.domestic_Batch_id', $batchId)
            ->orderBy('dt.domestic_tada_id')
            ->get();
    }

    /**
     * Update/insert/delete the batch rows inside one transaction (as the legacy Edit page did).
     *
     * @param  array<string,array{is_twenty_pct_extra:int,total_tada:float,total_days:int}>  $payloads  keyed by employee code
     * @return array{ok:bool, updated:int, inserted:int, deleted:int, errors:string[]}
     */
    public function updateBatch(string $batchId, array $data, array $payloads, int $fiscalYearId, $userId): array
    {
        $existing = DomesticTada::query()
            ->where('domestic_Batch_id', $batchId)
            ->pluck('domestic_tada_id', 'EmpPersonalCode')
            ->all();

        $updated = $inserted = $deleted = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($payloads as $empCode => $p) {
                if (isset($existing[$empCode])) {
                    try {
                        DomesticTada::query()
                            ->where('domestic_tada_id', $existing[$empCode])
                            ->where('domestic_Batch_id', $batchId)
                            ->update([
                                'domestic_form_date' => $data['form_date'],
                                'District_id' => $data['district_id'],
                                'domestic_travel_objective' => $data['travel_objective'],
                                'domestic_travelDateStart' => $data['travelDateStart'],
                                'domestic_travelDateEnd' => $data['travelDateEnd'],
                                'TadaTypeMaster_id' => $data['tada_type_id'],
                                'domestic_tada' => $p['total_tada'],
                                'domestic_isTwentyPercentExtra' => $p['is_twenty_pct_extra'],
                                'fiscal_year_master_id' => $fiscalYearId,
                                'tadaverifier_id' => $data['tadaverifier_id'],
                            ]);
                    } catch (\Throwable $e) {
                        throw new \RuntimeException("UPDATE failed for emp {$empCode}: ".$this->sqlMessage($e));
                    }
                    $updated++;
                }
            }

            foreach ($payloads as $empCode => $p) {
                if (! isset($existing[$empCode])) {
                    try {
                        // domestic_totalday is a computed column - never inserted.
                        DomesticTada::query()->insert([
                            'domestic_Batch_id' => $batchId,
                            'domestic_Chalani_id' => $this->nextChalani($fiscalYearId),
                            'domestic_form_date' => $data['form_date'],
                            'EmpPersonalCode' => $empCode,
                            'District_id' => $data['district_id'],
                            'domestic_travel_objective' => $data['travel_objective'],
                            'domestic_travelDateStart' => $data['travelDateStart'],
                            'domestic_travelDateEnd' => $data['travelDateEnd'],
                            'domestic_tada' => $p['total_tada'],
                            'domestic_isTwentyPercentExtra' => $p['is_twenty_pct_extra'],
                            'TadaTypeMaster_id' => $data['tada_type_id'],
                            'domestic_createdBy' => $userId,
                            'domestic_createddate' => DB::raw('GETDATE()'),
                            'fiscal_year_master_id' => $fiscalYearId,
                            'tadaverifier_id' => $data['tadaverifier_id'],
                        ]);
                    } catch (\Throwable $e) {
                        throw new \RuntimeException("INSERT failed for emp {$empCode}: ".$this->sqlMessage($e));
                    }
                    $inserted++;
                }
            }

            foreach ($existing as $empCode => $tadaId) {
                if (! isset($payloads[$empCode])) {
                    try {
                        DomesticTada::query()
                            ->where('domestic_tada_id', $tadaId)
                            ->where('domestic_Batch_id', $batchId)
                            ->delete();
                    } catch (\Throwable $e) {
                        throw new \RuntimeException("DELETE failed for tada_id {$tadaId}: ".$this->sqlMessage($e));
                    }
                    $deleted++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = $e->getMessage();

            return ['ok' => false, 'updated' => 0, 'inserted' => 0, 'deleted' => 0, 'errors' => $errors];
        }

        return ['ok' => true, 'updated' => $updated, 'inserted' => $inserted, 'deleted' => $deleted, 'errors' => []];
    }

    // ─── View (batch-based pagination) ──────────────────────────────────────────

    /**
     * Batch-based pagination: pages contain whole batches (all their employee rows).
     *
     * @return array{batches:LengthAwarePaginator, records:array, totalRecords:int, totalRecordsOnPage:int}
     */
    public function batchListing(string $search, int $perPage = 5): array
    {
        $batchQuery = DB::table('DomesticTada as dt')
            ->leftJoin('Employee_Information as e', 'dt.EmpPersonalCode', '=', 'e.EmpPersonalCode');
        if ($search !== '') {
            $batchQuery->where('e.EmpName', 'like', '%'.$search.'%');
        }

        $totalRecords = (int) (clone $batchQuery)->count();

        $allBatches = $batchQuery
            ->selectRaw('dt.domestic_Batch_id, MIN(dt.domestic_createddate) AS batch_created_date')
            ->groupBy('dt.domestic_Batch_id')
            ->orderByRaw('MIN(dt.domestic_createddate) DESC, dt.domestic_Batch_id DESC')
            ->pluck('domestic_Batch_id');

        $page = Paginator::resolveCurrentPage();
        $batches = (new LengthAwarePaginator(
            $allBatches->forPage($page, $perPage)->values(),
            $allBatches->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        ))->withQueryString();

        $currentPageBatches = $batches->items();

        $records = [];
        if (! empty($currentPageBatches)) {
            $records = DB::table('DomesticTada as dt')
                ->leftJoin('Employee_Information as e', 'dt.EmpPersonalCode', '=', 'e.EmpPersonalCode')
                ->leftJoin('DistrictMaster as dm', 'dt.District_id', '=', 'dm.District_id')
                ->leftJoin('DomesticTadaDefinerMasterBylevel as dtd', 'e.LevelName', '=', 'dtd.DomesticTadaDefinerMasterBylevel_name')
                ->leftJoin('Users as u', 'dt.domestic_createdBy', '=', 'u.user_id')
                ->selectRaw("dt.domestic_tada_id, dt.domestic_Batch_id, dt.domestic_Chalani_id, dt.domestic_form_date,
                    dt.EmpPersonalCode, e.EmpName, e.Designation, e.LevelName, dt.District_id, dm.District_name,
                    CASE WHEN dt.domestic_isTwentyPercentExtra = 1 THEN 'YES' ELSE 'NO' END AS isTwentyPercentExtra,
                    dt.domestic_travel_objective, dt.domestic_travelDateStart, dt.domestic_travelDateEnd,
                    dt.domestic_totalday, dt.domestic_tada, dtd.DomesticTadaDefinerMasterBylevel_name,
                    dtd.tadaInNepali, dt.domestic_createddate, u.username AS created_by_name")
                ->whereIn('dt.domestic_Batch_id', $currentPageBatches)
                ->orderBy('dt.domestic_Batch_id', 'desc')
                ->orderBy('dt.domestic_createddate', 'desc')
                ->orderBy('dt.domestic_tada_id')
                ->get()
                ->all();
        }

        return [
            'batches' => $batches,
            'records' => $records,
            'totalRecords' => $totalRecords,
            'totalRecordsOnPage' => count($records),
        ];
    }

    /**
     * Parse the edit form's "code:twentyPercentFlag,..." string into per-employee payloads.
     *
     * @return array{0:array<string,array>, 1:string[]}  [payloads keyed by employee code, warnings]
     */
    public function preparePayloads(string $employeeData, int $totalDays): array
    {
        $payloads = [];
        $errors = [];
        foreach (explode(',', $employeeData) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }
            $parts = explode(':', $entry);
            if (count($parts) !== 2) {
                $errors[] = "Invalid format: {$entry}";
                continue;
            }
            $empCode = trim($parts[0]);
            $twenty = (int) $parts[1];

            try {
                $emp = $this->employeeWithRate($empCode);
            } catch (\Throwable $e) {
                $errors[] = "Employee {$empCode}: query failed";
                continue;
            }
            if (! $emp) {
                $errors[] = "Employee {$empCode}: not found";
                continue;
            }

            $payloads[$empCode] = [
                'is_twenty_pct_extra' => $twenty,
                'total_tada' => $this->calculateTada($totalDays, (float) $emp->tadaInNepali, $twenty == 1),
                'total_days' => $totalDays,
            ];
        }

        return [$payloads, $errors];
    }

    // ─── Print ──────────────────────────────────────────────────────────────────

    public function printRecords(string $batchId): Collection
    {
        return DB::table('DomesticTada as dt')
            ->leftJoin('Employee_Information as e', 'dt.EmpPersonalCode', '=', 'e.EmpPersonalCode')
            ->leftJoin('DistrictMaster as dm', 'dt.District_id', '=', 'dm.District_id')
            ->leftJoin('DomesticTadaDefinerMasterBylevel as dtd', 'e.LevelName', '=', 'dtd.DomesticTadaDefinerMasterBylevel_name')
            ->leftJoin('DesignationTypeMaster as dtm', 'e.Designation', '=', 'dtm.designationType')
            ->leftJoin('TraveltypeMaster as ttm', 'dt.TadaTypeMaster_id', '=', 'ttm.TadaTypeMaster_id')
            ->leftJoin('fiscal_year_master as fy', 'dt.fiscal_year_master_id', '=', 'fy.fiscal_year_master_id')
            ->leftJoin('tadaverifier as tv', 'dt.tadaverifier_id', '=', 'tv.tadaverifier_id')
            ->select('dt.*', 'e.EmpName', 'e.EmpNameInNepali', 'e.Designation', 'e.LevelName', 'dm.District_name_nepali',
                'dtd.DomesticTadaDefinerMasterBylevel_name', 'dtd.tadaInNepali', 'dtm.designationTypeInNepali',
                'ttm.type as travel_type', 'fy.fy as fiscal_year', 'tv.tadaverifierPost as verifier_post')
            ->where('dt.domestic_Batch_id', $batchId)
            ->orderBy('dt.domestic_Chalani_id')
            ->get();
    }

    private function sqlMessage(\Throwable $e): string
    {
        $prev = $e->getPrevious();

        return $prev ? $prev->getMessage() : $e->getMessage();
    }
}
