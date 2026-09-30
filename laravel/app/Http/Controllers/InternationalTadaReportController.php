<?php

namespace App\Http\Controllers;

use App\Services\InternationalTadaService;
use Illuminate\Http\Request;

class InternationalTadaReportController extends Controller
{
    public function __construct(private InternationalTadaService $tada)
    {
    }

    /** INTL_HOSTORYTADA.php — latest international visit per employee, with search. */
    public function history(Request $request)
    {
        $search = (string) $request->query('search', '');
        $rows = $this->tada->employeeStatus($search);

        return view('international.history', compact('search', 'rows'));
    }

    /** INTERNATIONAL_TADAREPORT.php — filter form, screen table, print section and (client-side) Excel export. */
    public function report(Request $request)
    {
        $filterApplied = $request->query->has('filter_applied');
        $str = fn (string $k) => trim((string) $request->query($k, ''));

        $filters = [
            'fy' => $str('fy'),
            'emp' => $str('emp'),
            'country' => $str('country'),
            'city' => $str('city'),
            'designation' => $str('designation'),
            'dress' => $str('dress'),
            'extra33' => $str('extra33'),
            'batch' => $str('batch'),
            'date_from' => $str('date_from'),
            'date_to' => $str('date_to'),
        ];

        $lists = $this->tada->reportLists();

        $records = collect();
        $grandTotalUSD = 0.0;
        $selectedFYName = '';

        if ($filterApplied) {
            if ($filters['fy'] !== '' && $filters['fy'] !== '0') {
                foreach ($lists['fyList'] as $f) {
                    if ($f->fiscal_year_master_id == $filters['fy']) {
                        $selectedFYName = $f->fy;
                        break;
                    }
                }
            }

            $records = $this->tada->reportRecords($filters);
            foreach ($records as $row) {
                $grandTotalUSD += (float) ($row->totalUSdrecevid ?? $row->tadaInUSD_Calc ?? 0);
            }
        }

        return view('international.report', array_merge($lists, [
            'filterApplied' => $filterApplied,
            'f' => $filters,
            'records' => $records,
            'grandTotalUSD' => $grandTotalUSD,
            'selectedFYName' => $selectedFYName,
        ]));
    }
}
