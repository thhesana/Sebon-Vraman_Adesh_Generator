<?php

namespace App\Services;

use App\Models\FiscalYear;
use App\Models\UsdForex;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Shared logic of the international TADA module (batch ids, chalani numbers,
 * fiscal year lookup, TADA amounts, lookups and the list / print / report queries).
 */
class InternationalTadaService
{
    /** Half-day rule: (days between + 1) - 0.5. */
    public function totalDays(string $start, string $end): float
    {
        $days = abs((int) Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->startOfDay()));

        return $days + 1 - 0.5;
    }

    /** Amount stored in totalUSdrecevid on insert (legacy: days * level rate, no 33% applied). */
    public function totalUsd(float $totalDays, $usdPerDay): float
    {
        return $totalDays * (float) $usdPerDay;
    }

    /** Daily rate incl. the 33% extra-country rule. */
    public function dailyRate($tadaInUsd, $extra33): float
    {
        $rate = (float) $tadaInUsd;

        return ((int) $extra33 === 1) ? $rate + ($rate * 0.33) : $rate;
    }

    public function currentFiscalYearId()
    {
        return FiscalYear::query()
            ->whereRaw('CAST(GETDATE() AS DATE) BETWEEN CAST(fy_startdate AS DATE) AND CAST(fy_enddate AS DATE)')
            ->where('fy_status', 'ACTIVE')
            ->value('fiscal_year_master_id');
    }

    public function nextChalaniNumber($fiscalYearId): int
    {
        if (empty($fiscalYearId)) {
            return 1;
        }
        try {
            $max = DB::table('International_tada')
                ->where('fiscal_year_master_id', $fiscalYearId)
                ->max('Chalani_id');

            return (int) $max + 1;
        } catch (\Throwable $e) {
            report($e);

            return 1;
        }
    }

    public function nextBatchId(): string
    {
        try {
            $row = DB::selectOne('SELECT dbo.fn_GenerateBatchId() AS Batch_id');

            return $row->Batch_id;
        } catch (\Throwable $e) {
            try {
                $maxBatch = DB::table('International_tada')->max('Batch_id');
            } catch (\Throwable $e2) {
                return 'BATCH001';
            }
            if (empty($maxBatch)) {
                return 'BATCH001';
            }

            return 'BATCH'.str_pad((int) substr($maxBatch, 5) + 1, 3, '0', STR_PAD_LEFT);
        }
    }

    public function countries()
    {
        return DB::table('CountryMaster')->select('Country_id', 'Country_name')->orderBy('Country_name')->get();
    }

    public function allCities()
    {
        return DB::table('CityMaster')->select('City_id', 'City_name', 'Country_id')->orderBy('City_name')->get();
    }

    public function verifiers()
    {
        return DB::table('tadaverifier')->select('tadaverifier_id', 'tadaverifierPost')->orderBy('tadaverifierPost')->get();
    }

    /** Employees joined to their TADA level (dropdown source). */
    public function employeesWithTada()
    {
        return DB::table('Employee_Information as e')
            ->leftJoin('TadaDefinerMasterBylevel as t', 'e.LevelName', '=', 't.TadaDefinerMasterBylevel_name')
            ->select('e.EmpPersonalCode', 'e.EmpName', 'e.LevelName', 't.TadaDefinerMasterBylevel_id', 't.tadaInUSD')
            ->orderBy('e.EmpName')
            ->get();
    }

    public function employeeTadaDetail(string $empCode)
    {
        return DB::table('Employee_Information as e')
            ->leftJoin('TadaDefinerMasterBylevel as t', 'e.LevelName', '=', 't.TadaDefinerMasterBylevel_name')
            ->select('e.EmpPersonalCode', 'e.LevelName', 't.TadaDefinerMasterBylevel_id', 't.tadaInUSD')
            ->where('e.EmpPersonalCode', $empCode)
            ->first();
    }

    /**
     * Parse the "code:dress,code:dress" string built by the page JS.
     *
     * @return array{entries: array<int, array{code:string,dress:int}>, errors: array<int,string>}
     */
    public function parseEmployeeData(string $data, string $invalidPrefix = 'Invalid employee data format: '): array
    {
        $entries = [];
        $errors = [];
        foreach (explode(',', $data) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }
            $parts = explode(':', $entry);
            if (count($parts) !== 2) {
                $errors[] = $invalidPrefix.$entry;

                continue;
            }
            $entries[] = ['code' => trim($parts[0]), 'dress' => (int) $parts[1] === 1 ? 1 : 0];
        }

        return ['entries' => $entries, 'errors' => $errors];
    }

    /** Insert one International_tada row (createddate = GETDATE()). */
    public function insertRow(array $row): void
    {
        $row['createddate'] = DB::raw('GETDATE()');
        DB::table('International_tada')->insert($row);
    }

    /** Paged batch listing (InternationalVraman.php). */
    public function listRecords(int $offset, int $limit)
    {
        return DB::table('International_tada as i')
            ->leftJoin('CountryMaster as c', 'i.Country_id', '=', 'c.Country_id')
            ->leftJoin('CityMaster as ci', 'i.City_id', '=', 'ci.City_id')
            ->leftJoin('TadaDefinerMasterBylevel as t', 'i.TadaDefinerMasterBylevel_id', '=', 't.TadaDefinerMasterBylevel_id')
            ->leftJoin('Employee_Information as emp', 'i.EmpPersonalCode', '=', 'emp.EmpPersonalCode')
            ->leftJoin('Users as u', 'i.createdBy', '=', 'u.user_id')
            ->selectRaw("
                i.International_tada_id, i.Batch_id, i.Chalani_id, i.form_date, i.EmpPersonalCode,
                emp.EmpName AS EmployeeName, c.Country_name AS Country, ci.City_name AS City,
                i.travel_objective, i.travelDateStart, i.travelDateEnd,
                t.TadaDefinerMasterBylevel_name AS TADA_Level, t.tadaInUSD,
                CAST(DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5 AS DECIMAL(5,2)) AS totalday,
                CASE
                    WHEN c.extra33percent_country = 1
                        THEN (t.tadaInUSD + (t.tadaInUSD * 0.33)) * (DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5)
                    ELSE t.tadaInUSD * (DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5)
                END AS tadaInUSD_Final,
                CASE WHEN i.DressAllowance = 1 THEN 'Yes' ELSE 'No' END AS DressAllowance,
                CASE WHEN c.extra33percent_country = 1 THEN 'Receives Extra 33%' ELSE 'No Extra' END AS Extra33Percent,
                i.createdBy, u.username AS CreatedByName, i.createddate
            ")
            ->orderByDesc('i.createddate')
            ->orderByDesc('i.Batch_id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function totalRecords(): int
    {
        return (int) DB::table('International_tada')->count();
    }

    /** First row of a batch (edit page header data). */
    public function batchHeader(string $batchId)
    {
        return DB::table('International_tada')
            ->select('Batch_id', 'fiscal_year_master_id', 'Chalani_id', 'form_date', 'Country_id', 'City_id',
                'travel_objective', 'travelDateStart', 'travelDateEnd', 'TadaDefinerMasterBylevel_id',
                'DressAllowance', 'tadaverifier_id')
            ->where('Batch_id', $batchId)
            ->first();
    }

    /** All employees of a batch with level info (edit page pre-population). */
    public function batchEmployees(string $batchId)
    {
        return DB::table('International_tada as it')
            ->leftJoin('Employee_Information as e', 'it.EmpPersonalCode', '=', 'e.EmpPersonalCode')
            ->leftJoin('TadaDefinerMasterBylevel as t', 'it.TadaDefinerMasterBylevel_id', '=', 't.TadaDefinerMasterBylevel_id')
            ->select('it.International_tada_id', 'it.EmpPersonalCode', 'it.totalUSdrecevid', 'it.DressAllowance',
                'e.EmpName', 't.TadaDefinerMasterBylevel_name', 't.TadaDefinerMasterBylevel_id', 't.tadaInUSD')
            ->where('it.Batch_id', $batchId)
            ->orderBy('it.International_tada_id')
            ->get();
    }

    /** Rows of a batch for the printable Anusuchi-5 form. */
    public function printRows(string $batchId)
    {
        return DB::table('International_tada as i')
            ->leftJoin('fiscal_year_master as fy', 'i.fiscal_year_master_id', '=', 'fy.fiscal_year_master_id')
            ->leftJoin('CountryMaster as c', 'i.Country_id', '=', 'c.Country_id')
            ->leftJoin('CityMaster as ci', 'i.City_id', '=', 'ci.City_id')
            ->leftJoin('TadaDefinerMasterBylevel as t', 'i.TadaDefinerMasterBylevel_id', '=', 't.TadaDefinerMasterBylevel_id')
            ->leftJoin('Employee_Information as emp', 'i.EmpPersonalCode', '=', 'emp.EmpPersonalCode')
            ->leftJoin('DesignationTypeMaster as dt', 'emp.Designation_id', '=', 'dt.id')
            ->selectRaw("
                i.International_tada_id, i.Batch_id, i.Chalani_id, i.form_date, i.EmpPersonalCode,
                i.fiscal_year_master_id, fy.fy AS FiscalYear,
                emp.EmpName AS EmployeeName, emp.EmpNameInNepali AS EmployeeNamenepali,
                dt.designationTypeInNepali AS DesignationInNepali,
                c.Country_name, c.extra33percent_country, ci.City_name, i.travel_objective,
                i.travelDateStart, i.travelDateEnd, t.tadaInUSD,
                CASE WHEN c.extra33percent_country = 1 THEN t.tadaInUSD * 1.33 ELSE t.tadaInUSD END AS tadaInUSD_Final,
                i.totalday,
                CASE
                    WHEN i.DressAllowance = 0 THEN 0
                    WHEN i.DressAllowance = 1 AND emp.LevelName_id IN (5, 11) THEN 10000
                    WHEN i.DressAllowance = 1 THEN 8000
                    ELSE 0
                END AS DressAllowance
            ")
            ->where('i.Batch_id', $batchId)
            ->orderBy('i.International_tada_id')
            ->get();
    }

    /** USD rate whose conversion_date exactly matches the form date. */
    public function usdRateOn(string $date)
    {
        return UsdForex::query()->whereRaw('CAST(conversion_date AS DATE) = ?', [$date])->first();
    }

    /** History page: latest visit per employee, optional search (bound, not interpolated). */
    public function employeeStatus(string $search)
    {
        $latest = DB::table('International_tada')
            ->select('EmpPersonalCode', DB::raw('MAX(travelDateStart) AS LatestTravelStart'))
            ->groupBy('EmpPersonalCode');

        $like = '%'.$search.'%';

        return DB::table('Employee_Information as ei')
            ->leftJoinSub($latest, 'latest', 'ei.EmpPersonalCode', '=', 'latest.EmpPersonalCode')
            ->leftJoin('International_tada as it', function ($join) {
                $join->on('ei.EmpPersonalCode', '=', 'it.EmpPersonalCode')
                    ->on('it.travelDateStart', '=', 'latest.LatestTravelStart');
            })
            ->leftJoin('CountryMaster as cm', 'it.Country_id', '=', 'cm.Country_id')
            ->selectRaw('ei.EmpPersonalCode, ei.EmpName, it.Batch_id, cm.Country_name, it.travelDateStart, it.travelDateEnd,
                CASE WHEN it.travelDateStart IS NOT NULL THEN DATEDIFF(DAY, it.travelDateStart, GETDATE()) ELSE NULL END AS DaysDifference')
            ->where(function ($q) use ($like) {
                $q->where('ei.EmpPersonalCode', 'like', $like)
                    ->orWhere('ei.EmpName', 'like', $like)
                    ->orWhere('cm.Country_name', 'like', $like)
                    ->orWhere('it.Batch_id', 'like', $like);
            })
            ->orderBy('ei.EmpPersonalCode')
            ->orderByDesc('it.travelDateStart')
            ->get();
    }

    // ── Report ──────────────────────────────────────────────────────────────

    /** @return array<string, \Illuminate\Support\Collection> dropdown lists for the report filter */
    public function reportLists(): array
    {
        return [
            'fyList' => DB::table('fiscal_year_master')->select('fiscal_year_master_id', 'fy')->orderByDesc('fy')->get(),
            'empList' => DB::table('Employee_Information as e')
                ->join('International_tada as it', 'e.EmpPersonalCode', '=', 'it.EmpPersonalCode')
                ->select('e.EmpPersonalCode', 'e.EmpName')->distinct()->orderBy('e.EmpName')->get(),
            'countryList' => DB::table('CountryMaster as c')
                ->join('International_tada as it', 'c.Country_id', '=', 'it.Country_id')
                ->select('c.Country_id', 'c.Country_name')->distinct()->orderBy('c.Country_name')->get(),
            'cityList' => DB::table('CityMaster as ci')
                ->join('International_tada as it', 'ci.City_id', '=', 'it.City_id')
                ->select('ci.City_id', 'ci.City_name')->distinct()->orderBy('ci.City_name')->get(),
            'desigList' => DB::table('Employee_Information as e')
                ->join('International_tada as it', 'e.EmpPersonalCode', '=', 'it.EmpPersonalCode')
                ->whereNotNull('e.Designation')
                ->select('e.Designation')->distinct()->orderBy('e.Designation')->get(),
        ];
    }

    /** Filtered report rows (semantics identical to the legacy WHERE builder). */
    public function reportRecords(array $f)
    {
        $q = DB::table('International_tada as it')
            ->leftJoin('Employee_Information as e', 'it.EmpPersonalCode', '=', 'e.EmpPersonalCode')
            ->leftJoin('CountryMaster as c', 'it.Country_id', '=', 'c.Country_id')
            ->leftJoin('CityMaster as ci', 'it.City_id', '=', 'ci.City_id')
            ->leftJoin('TadaDefinerMasterBylevel as t', 'it.TadaDefinerMasterBylevel_id', '=', 't.TadaDefinerMasterBylevel_id')
            ->leftJoin('DesignationTypeMaster as dtm', 'e.Designation', '=', 'dtm.designationType')
            ->leftJoin('Users as u', 'it.createdBy', '=', 'u.user_id')
            ->leftJoin('fiscal_year_master as fy', 'it.fiscal_year_master_id', '=', 'fy.fiscal_year_master_id')
            ->selectRaw('
                it.International_tada_id, it.Batch_id, it.Chalani_id, it.form_date, it.EmpPersonalCode,
                e.EmpName, e.EmpNameInNepali, e.Designation, e.LevelName, dtm.designationTypeInNepali,
                c.Country_id, c.Country_name, c.extra33percent_country, ci.City_name,
                it.travel_objective, it.travelDateStart, it.travelDateEnd, it.totalday,
                it.totalUSdrecevid, it.DressAllowance,
                t.TadaDefinerMasterBylevel_name AS TADA_Level, t.tadaInUSD,
                CASE
                    WHEN c.extra33percent_country = 1 THEN (t.tadaInUSD + (t.tadaInUSD * 0.33)) * it.totalday
                    ELSE t.tadaInUSD * it.totalday
                END AS tadaInUSD_Calc,
                fy.fy AS fiscal_year, u.username AS created_by
            ');

        $has = fn ($v) => $v !== null && $v !== '' && $v !== '0'; // PHP empty() semantics

        if ($has($f['fy'])) {
            $q->where('it.fiscal_year_master_id', $f['fy']);
        }
        if ($has($f['emp'])) {
            $q->where('it.EmpPersonalCode', $f['emp']);
        }
        if ($has($f['country'])) {
            $q->where('it.Country_id', $f['country']);
        }
        if ($has($f['city'])) {
            $q->where('it.City_id', $f['city']);
        }
        if ($has($f['designation'])) {
            $q->where('e.Designation', $f['designation']);
        }
        if ($has($f['batch'])) {
            $q->where('it.Batch_id', $f['batch']);
        }
        if ($f['dress'] !== '') {
            $q->where('it.DressAllowance', (int) $f['dress']);
        }
        if ($f['extra33'] === '1') {
            $q->where('c.extra33percent_country', 1);
        } elseif ($f['extra33'] === '0') {
            $q->where(function ($w) {
                $w->where('c.extra33percent_country', 0)->orWhereNull('c.extra33percent_country');
            });
        }
        if ($has($f['date_from'])) {
            $q->where('it.travelDateStart', '>=', $f['date_from']);
        }
        if ($has($f['date_to'])) {
            $q->where('it.travelDateEnd', '<=', $f['date_to']);
        }

        return $q->orderByDesc('it.International_tada_id')->get();
    }
}
