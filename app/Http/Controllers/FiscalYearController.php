<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFiscalYearRequest;
use App\Http\Requests\UpdateFiscalYearRequest;
use App\Models\FiscalYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    public function index()
    {
        // Latest fiscal year first (by start date, not by when the row was added).
        $fiscalYears = FiscalYear::orderByDesc('fy_startdate')->orderByDesc('fiscal_year_master_id')->get();

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

    /**
     * Saves BS dates converted in the browser (Nepali date picker library) for fiscal years
     * that do not have them yet. Existing BS values are never overwritten here.
     */
    public function syncBsDates(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'array', 'max:500'],
            'rows.*.id' => ['required', 'integer'],
            'rows.*.fy_startdateBS' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'rows.*.fy_enddateBS' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ]);

        $updated = 0;
        foreach ($data['rows'] as $row) {
            $fiscalYear = FiscalYear::find($row['id']);
            if (! $fiscalYear) {
                continue;
            }

            foreach (['fy_startdateBS', 'fy_enddateBS'] as $column) {
                if (blank($fiscalYear->{$column}) && filled($row[$column] ?? null)) {
                    $fiscalYear->{$column} = $row[$column];
                }
            }

            if ($fiscalYear->isDirty()) {
                $fiscalYear->save();
                $updated++;
            }
        }

        return response()->json(['updated' => $updated]);
    }
}
