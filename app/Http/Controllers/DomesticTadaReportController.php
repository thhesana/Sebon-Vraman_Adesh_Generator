<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\DomesticTada;
use App\Models\Employee;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomesticTadaReportController extends Controller
{
    // DOMESTIC_TADAREPORT.php
    public function index(Request $request): View
    {
        $filterApplied = $request->query->has('filter_applied');
        $filters = [
            'fy' => trim((string) $request->query('fy', '')),
            'emp' => trim((string) $request->query('emp', '')),
            'district' => trim((string) $request->query('district', '')),
            'designation' => trim((string) $request->query('designation', '')),
        ];

        $fyList = FiscalYear::orderByDesc('fy')->get(['fiscal_year_master_id', 'fy']);

        $records = collect();
        $selectedFYName = '';
        if ($filterApplied) {
            if (! empty($filters['fy'])) {
                $selectedFYName = (string) $fyList->firstWhere('fiscal_year_master_id', $filters['fy'])?->fy;
            }
            $records = DomesticTada::report($filters)->get();
        }

        $chalaniNumbers = $records->pluck('domestic_Chalani_id')->filter()->unique()->sort()->values();
        $chalaniRange = match ($chalaniNumbers->count()) {
            0 => '—',
            1 => $chalaniNumbers->first(),
            default => $chalaniNumbers->first().'–'.$chalaniNumbers->last(),
        };

        return view('domestic.report', [
            'filterApplied' => $filterApplied,
            'filterFY' => $filters['fy'],
            'filterEmp' => $filters['emp'],
            'filterDistrict' => $filters['district'],
            'filterDesig' => $filters['designation'],
            'fyList' => $fyList->pluck('fy', 'fiscal_year_master_id'),
            'empList' => Employee::whereHas('domesticTadas')->orderBy('EmpName')->pluck('EmpName', 'EmpPersonalCode'),
            'districtList' => District::whereHas('domesticTadas')->orderBy('District_name')->pluck('District_name', 'District_id'),
            'desigList' => Employee::whereNotNull('Designation')->whereHas('domesticTadas')
                ->distinct()->orderBy('Designation')->pluck('Designation', 'Designation'),
            'records' => $records,
            'grandTotal' => (float) $records->sum('domestic_tada'),
            'batchCount' => $records->pluck('domestic_Batch_id')->unique()->count(),
            'empCount' => $records->pluck('EmpPersonalCode')->unique()->count(),
            'selectedFYName' => $selectedFYName,
            'chalaniRange' => (string) $chalaniRange,
        ]);
    }
}
