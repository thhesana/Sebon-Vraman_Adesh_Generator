<?php

namespace App\Http\Controllers;

use App\Services\DomesticTadaReportService;
use Illuminate\Http\Request;

class DomesticTadaReportController extends Controller
{
    public function __construct(private DomesticTadaReportService $report)
    {
    }

    // DOMESTIC_TADAREPORT.php
    public function index(Request $request)
    {
        $filterApplied = $request->query->has('filter_applied');
        $fy = trim((string) $request->query('fy', ''));
        $emp = trim((string) $request->query('emp', ''));
        $district = trim((string) $request->query('district', ''));
        $desig = trim((string) $request->query('designation', ''));

        $fyList = $this->report->fiscalYears();
        $empList = $this->report->employees();
        $districtList = $this->report->districts();
        $desigList = $this->report->designations();

        $records = [];
        $grandTotal = 0.0;
        $selectedFYName = '';

        if ($filterApplied) {
            if (! empty($fy)) {
                foreach ($fyList as $f) {
                    if ($f->fiscal_year_master_id == $fy) {
                        $selectedFYName = $f->fy;
                        break;
                    }
                }
            }

            $records = $this->report->records($fy, $emp, $district, $desig);
            foreach ($records as $row) {
                $grandTotal += (float) $row->domestic_tada;
            }
        }

        $chalaniNums = [];
        if (! empty($records)) {
            $chalaniNums = array_values(array_unique(array_filter(array_column(array_map(fn ($r) => (array) $r, $records), 'domestic_Chalani_id'))));
            sort($chalaniNums);
        }
        $chalaniRange = ! empty($chalaniNums)
            ? (count($chalaniNums) === 1 ? $chalaniNums[0] : $chalaniNums[0].'–'.end($chalaniNums))
            : '—';

        return view('domestic.report', [
            'filterApplied' => $filterApplied,
            'filterFY' => $fy,
            'filterEmp' => $emp,
            'filterDistrict' => $district,
            'filterDesig' => $desig,
            'fyList' => $fyList,
            'empList' => $empList,
            'districtList' => $districtList,
            'desigList' => $desigList,
            'records' => $records,
            'grandTotal' => $grandTotal,
            'selectedFYName' => $selectedFYName,
            'chalaniRange' => $chalaniRange,
        ]);
    }
}
