<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFiscalYearRequest;
use App\Http\Requests\UpdateFiscalYearRequest;
use App\Models\FiscalYear;

class FiscalYearController extends Controller
{
    public function index()
    {
        $fiscalYears = FiscalYear::orderByDesc('fiscal_year_master_id')->get();

        return view('fiscal_year.index', compact('fiscalYears'));
    }

    public function create()
    {
        return view('fiscal_year.create');
    }

    public function store(StoreFiscalYearRequest $request)
    {
        FiscalYear::create($request->validated() + ['created_date' => now()->format('Y-m-d H:i:s')]);

        return redirect()->route('fiscal_years.index')->with('success', 'Fiscal Year added successfully!');
    }

    public function edit(FiscalYear $fiscal_year)
    {
        return view('fiscal_year.edit', ['row' => $fiscal_year]);
    }

    public function update(UpdateFiscalYearRequest $request, FiscalYear $fiscal_year)
    {
        $fiscal_year->update($request->validated());

        return redirect()->route('fiscal_years.index');
    }
}
