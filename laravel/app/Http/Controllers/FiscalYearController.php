<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    private const RULES = [
        'fy' => ['required', 'string', 'max:50'],
        'fy_startdate' => ['required', 'date'],
        'fy_enddate' => ['required', 'date'],
        'fy_status' => ['required', 'in:ACTIVE,INACTIVE'],
    ];

    public function index()
    {
        $fiscalYears = FiscalYear::orderByDesc('fiscal_year_master_id')->get();

        return view('fiscal_year.index', compact('fiscalYears'));
    }

    public function create()
    {
        return view('fiscal_year.create');
    }

    public function store(Request $request)
    {
        $v = validator($request->all(), self::RULES);
        if ($v->fails()) {
            return back()->withInput()->with('alert', 'Please fill in all fields!');
        }

        FiscalYear::create($v->validated() + ['created_date' => now()->format('Y-m-d H:i:s')]);

        return redirect('/fiscal_year.php')->with('alert', 'Fiscal Year added successfully!');
    }

    public function edit(Request $request)
    {
        return view('fiscal_year.edit', ['row' => $this->find($request)]);
    }

    public function update(Request $request)
    {
        $row = $this->find($request);

        $data = $request->validate(self::RULES);

        FiscalYear::where('fiscal_year_master_id', $row->fiscal_year_master_id)->update($data);

        return redirect('/fiscal_year.php');
    }

    private function find(Request $request): FiscalYear
    {
        $id = $request->query('id');
        if ($id === null || $id === '') {
            abort(400, 'Invalid request. No fiscal year ID provided.');
        }

        $row = FiscalYear::where('fiscal_year_master_id', $id)->first();
        if (! $row) {
            abort(404, 'Fiscal year record not found.');
        }

        return $row;
    }
}
