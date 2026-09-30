<?php

namespace App\Http\Controllers;

use App\Models\InternationalTada;
use Illuminate\Http\Request;

class InternationalTadaReportController extends Controller
{
    /** INTL_HOSTORYTADA.php — latest international visit per employee, with search. */
    public function history(Request $request)
    {
        $search = (string) $request->query('search', '');
        $rows = InternationalTada::latestVisits($search);

        return view('international.history', compact('search', 'rows'));
    }

    /** INTERNATIONAL_TADAREPORT.php — filter form, screen table, print section and (client-side) Excel export. */
    public function report(Request $request)
    {
        $filterApplied = $request->query->has('filter_applied');

        $filters = collect(['fy', 'emp', 'country', 'city', 'designation', 'dress', 'extra33', 'batch', 'date_from', 'date_to'])
            ->mapWithKeys(fn (string $key) => [$key => trim((string) $request->query($key, ''))])
            ->all();

        $lists = InternationalTada::reportFilterOptions();

        $records = collect();
        $selectedFYName = '';

        if ($filterApplied) {
            if ($filters['fy'] !== '' && $filters['fy'] !== '0') {
                $selectedFYName = $lists['fyList']->firstWhere('fiscal_year_master_id', $filters['fy'])?->fy ?? '';
            }

            $records = InternationalTada::query()
                ->with(['employee.designationByName', 'country', 'city', 'tadaLevel', 'fiscalYear'])
                ->filter($filters)
                ->orderByDesc('International_tada_id')
                ->get();
        }

        return view('international.report', $lists + [
            'filterApplied' => $filterApplied,
            'f' => $filters,
            'records' => $records,
            'grandTotalUSD' => (float) $records->sum('usd_total'),
            'selectedFYName' => $selectedFYName,
        ]);
    }
}
