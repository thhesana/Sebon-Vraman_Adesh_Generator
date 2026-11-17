<?php

namespace App\Http\Controllers;

use App\Http\Requests\OldInternationalTadaRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\DesignationType;
use App\Models\Employee;
use App\Models\InternationalTada;
use App\Models\OldInternationalTada;
use App\Models\UsdForex;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InternationalTadaReportController extends Controller
{
    /** INTL_HOSTORYTADA.php — latest international visit per employee, with search. */
    public function history(Request $request)
    {
        $search = (string) $request->query('search', '');
        $rows = InternationalTada::latestVisits($search);

        return view('international.history', compact('search', 'rows'));
    }

    /**
     * INTERNATIONAL_TADAREPORT.php — filter form, screen table, print section and (client-side) Excel export.
     * Lists International_tada batches together with the manually entered Old_International_tada records.
     */
    public function report(Request $request)
    {
        $filterApplied = $request->query->has('filter_applied');
        [$filters, $queryFilters, $dateErrors] = $this->parseFilters($request);
        $lists = $this->filterOptions();

        $records = $filterApplied ? $this->records($queryFilters) : collect();

        return view('international.report', $lists + [
            'filterApplied' => $filterApplied,
            'f' => $filters,
            'dateErrors' => $dateErrors,
            'records' => $records,
            'grandTotalUSD' => (float) $records->sum('totalUsd'),
            'totalDays' => (float) $records->sum('days'),
            // A sum of daily rates means nothing, so the footer shows their average.
            'avgRate' => (float) ($records->avg('rate') ?? 0),
            'selectedFYName' => $filterApplied ? $this->fiscalYearName($lists, $filters) : '',
        ]);
    }

    /** Sortable columns of the summarized report => row property. */
    private const SUMMARY_SORTS = [
        'name' => 'empName', 'visits' => 'visits', 'days' => 'days', 'amount' => 'totalNpr', 'last' => 'lastTravelBS',
    ];

    /**
     * Summarized Int'l report: the same filters and records as report(), one line per employee
     * (visit count, countries, total days, total amount received), sortable asc / desc.
     */
    public function summary(Request $request)
    {
        [$filters, $queryFilters, $dateErrors] = $this->parseFilters($request);
        $lists = $this->filterOptions();

        $sort = array_key_exists($request->query('sort'), self::SUMMARY_SORTS) ? $request->query('sort') : 'visits';
        // Default order is Low → High; only an explicit dir=desc reverses it.
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $summary = $this->records($queryFilters)
            ->groupBy('empCode')
            ->map(function (Collection $trips, string $code) {
                $latest = $trips->sortByDesc(fn (object $r) => $r->startBS ?? $r->startAD ?? '')->first();

                return (object) [
                    'empCode' => $code,
                    'empName' => $latest->empName,
                    'designation' => $latest->designation,
                    'visits' => $trips->count(),
                    'oldVisits' => $trips->where('isOld', true)->count(),
                    'countries' => $trips->pluck('country')->filter()->countBy()->sortDesc(),
                    'days' => (float) $trips->sum('days'),
                    'totalUsd' => (float) $trips->sum('totalUsd'),
                    // Rupees received; trips whose form date has no NRB rate are counted separately.
                    'totalNpr' => (float) $trips->whereNotNull('totalNpr')->sum('totalNpr'),
                    'nprMissing' => $trips->whereNull('totalNpr')->count(),
                    'lastTravelBS' => $latest->startBS ?? '',
                    'lastTravelAD' => $latest->startAD,
                ];
            })
            ->values();

        // Ties (e.g. many employees with 1 visit) are broken by amount received, then days.
        $key = self::SUMMARY_SORTS[$sort];
        $summary = ($dir === 'asc'
            ? $summary->sortBy(fn (object $r) => [$r->{$key}, $r->totalNpr, $r->days])
            : $summary->sortByDesc(fn (object $r) => [$r->{$key}, $r->totalNpr, $r->days]))->values();

        // Sorting by amount: employees whose every trip lacks a USD rate have no known amount,
        // so they go last in either direction instead of looking like "Rs 0".
        if ($sort === 'amount') {
            [$unknown, $known] = $summary->partition(fn (object $r) => $r->nprMissing === $r->visits);
            $summary = $known->concat($unknown)->values();
        }

        return view('international.summary', $lists + [
            'f' => $filters,
            'dateErrors' => $dateErrors,
            'summary' => $summary,
            'sort' => $sort,
            'dir' => $dir,
            'sortOptions' => ['visits' => 'Visit count', 'days' => 'Total program days', 'amount' => 'Total amount', 'name' => 'Employee name', 'last' => 'Last travel'],
            'selectedFYName' => $this->fiscalYearName($lists, $filters),
        ]);
    }

    /**
     * Report filters from the query string. Travel From / To are BS dates ('2083/7/20' is accepted
     * and stored as '2083-07-20'); an invalid one is reported and left out of the query.
     *
     * @return array{0: array<string,string>, 1: array<string,string>, 2: array<string,bool>} [filters for the form, filters for the query, date errors]
     */
    private function parseFilters(Request $request): array
    {
        $filters = collect(['fy', 'emp', 'country', 'city', 'designation', 'dress', 'extra33', 'batch', 'date_from', 'date_to'])
            ->mapWithKeys(fn (string $key) => [$key => trim((string) $request->query($key, ''))])
            ->all();

        $dateErrors = [];
        foreach (['date_from', 'date_to'] as $key) {
            $filters[$key] = (string) OldInternationalTadaRequest::normaliseBs($filters[$key]);
            if ($filters[$key] !== '' && ! preg_match(OldInternationalTadaRequest::BS_DATE, $filters[$key])) {
                $dateErrors[$key] = true;
            }
        }

        return [$filters, array_merge($filters, array_fill_keys(array_keys($dateErrors), '')), $dateErrors];
    }

    /** International_tada rows plus old records matching the filters, newest travel first. */
    private function records(array $queryFilters): Collection
    {
        $trips = InternationalTada::query()
            ->with(['employee.designationByName', 'country', 'city', 'tadaLevel', 'fiscalYear'])
            ->filter($queryFilters)
            ->get();

        // NRB USD rate of each form date, in one query (the printed order converts with this rate).
        $formDates = $trips->pluck('form_date')->filter()->map(fn ($d) => $d->format('Y-m-d'))->unique()->values();
        $usdRates = $formDates->isEmpty() ? collect() : UsdForex::query()
            ->whereIn(DB::raw('CAST(conversion_date AS date)'), $formDates->all())
            ->get()
            ->mapWithKeys(fn (UsdForex $r) => [Carbon::parse($r->conversion_date)->format('Y-m-d') => (float) $r->amount]);

        $current = $trips->map(fn (InternationalTada $row) => $this->currentRow($row, $usdRates->get($row->form_date?->format('Y-m-d'))));

        $old = OldInternationalTada::query()
            ->with(['employee', 'designation', 'country', 'city', 'fiscalYear'])
            ->reportFilter($queryFilters)
            ->get()
            ->map(fn (OldInternationalTada $row) => $this->oldRow($row));

        // BS 'YYYY-MM-DD' strings sort correctly as text.
        return $current->concat($old)
            ->sortByDesc(fn (object $r) => [$r->startBS ?? $r->startAD ?? '', $r->isOld ? 0 : 1, $r->id])
            ->values();
    }

    private function fiscalYearName(array $lists, array $filters): string
    {
        return ($filters['fy'] !== '' && $filters['fy'] !== '0')
            ? (string) ($lists['fyList']->firstWhere('fiscal_year_master_id', $filters['fy'])?->fy ?? '')
            : '';
    }

    /** One report line from an International_tada row (figures from the model's accessors). */
    private function currentRow(InternationalTada $row, ?float $usdRate = null): object
    {
        // Rupees as on the printed order: daily rate (incl. 33%) x days x NRB rate of the form date
        // + dress allowance. Unknown (null) when that date has no stored rate.
        $npr = ($usdRate && $row->daily_rate !== null && $row->days !== null)
            ? round((float) $row->daily_rate * (float) $row->days * $usdRate + $row->dress_amount, 2)
            : null;

        return (object) [
            'totalNpr' => $npr,
            'usdRate' => $usdRate,
            'isOld' => false,
            'id' => $row->getKey(),
            'batch' => $row->Batch_id,
            'chalani' => ($row->fiscalYear?->fy ?? '').'-'.($row->Chalani_id ?? ''),
            'empCode' => $row->EmpPersonalCode,
            'empName' => $row->employee?->EmpNameInNepali ?? $row->employee?->EmpName ?? $row->EmpPersonalCode,
            'designation' => $row->employee?->designationByName?->designationTypeInNepali ?? '-',
            'country' => $row->country?->Country_name,
            'city' => $row->city?->City_name,
            'objective' => $row->travel_objective,
            'startBS' => $row->travelDateStartBS,
            'endBS' => $row->travelDateEndBS,
            'startAD' => $row->travelDateStart?->format('Y-m-d'),
            'endAD' => $row->travelDateEnd?->format('Y-m-d'),
            'days' => $row->days,
            'rate' => (float) $row->daily_rate,
            'totalUsd' => (float) $row->usd_total,
            'dress' => (bool) $row->DressAllowance,
            'extra33' => (int) $row->country?->extra33percent_country === 1,
        ];
    }

    /** One report line from a manually entered old record (figures exactly as typed). */
    private function oldRow(OldInternationalTada $row): object
    {
        return (object) [
            // Rupees as entered: total NPR + dress allowance (the grand_total_npr column).
            'totalNpr' => $row->grand_total,
            'usdRate' => (float) $row->exchange_rate,
            'isOld' => true,
            'id' => $row->getKey(),
            'batch' => null,
            'chalani' => ($row->fiscalYear?->fy ?? '').'-'.($row->Chalani_id ?? ''),
            'empCode' => $row->EmpPersonalCode,
            'empName' => $row->employee?->EmpNameInNepali ?? $row->employee?->EmpName ?? $row->EmpPersonalCode,
            'designation' => $row->designation?->designationTypeInNepali ?: ($row->designation?->designationType ?? '-'),
            'country' => $row->country?->Country_name,
            'city' => $row->city?->City_name,
            'objective' => $row->travel_objective,
            'startBS' => $row->travelDateStartBS,
            'endBS' => $row->travelDateEndBS,
            'startAD' => null,
            'endAD' => null,
            'days' => (float) $row->total_days,
            'rate' => (float) $row->daily_rate_usd,
            'totalUsd' => (float) $row->total_usd,
            'dress' => (bool) $row->DressAllowance,
            'extra33' => (int) $row->country?->extra33percent_country === 1,
        ];
    }

    /**
     * Filter dropdowns: International_tada's lists plus the employees, countries, cities and
     * positions that appear only in old records.
     *
     * @return array<string, Collection>
     */
    private function filterOptions(): array
    {
        $lists = InternationalTada::reportFilterOptions();
        $old = OldInternationalTada::query();

        $lists['empList'] = $lists['empList']
            ->concat(Employee::query()->whereIn('EmpPersonalCode', (clone $old)->select('EmpPersonalCode'))->get(['EmpPersonalCode', 'EmpName']))
            ->unique('EmpPersonalCode')->sortBy('EmpName')->values();
        $lists['countryList'] = $lists['countryList']
            ->concat(Country::query()->whereIn('Country_id', (clone $old)->select('Country_id'))->get(['Country_id', 'Country_name']))
            ->unique('Country_id')->sortBy('Country_name')->values();
        $lists['cityList'] = $lists['cityList']
            ->concat(City::query()->whereIn('City_id', (clone $old)->whereNotNull('City_id')->select('City_id'))->get(['City_id', 'City_name']))
            ->unique('City_id')->sortBy('City_name')->values();
        $lists['desigList'] = $lists['desigList']
            ->concat(DesignationType::query()->whereIn('id', (clone $old)->whereNotNull('Designation_id')->select('Designation_id'))->pluck('designationType'))
            ->filter()->unique()->sort()->values();

        return $lists;
    }
}
