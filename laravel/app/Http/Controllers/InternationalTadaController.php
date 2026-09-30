<?php

namespace App\Http\Controllers;

use App\Actions\International\CreateBatch;
use App\Actions\International\UpdateBatch;
use App\Http\Requests\StoreInternationalTadaRequest;
use App\Http\Requests\UpdateInternationalTadaRequest;
use App\Jobs\SendBatchMails;
use App\Models\City;
use App\Models\Country;
use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\InternationalTada;
use App\Models\TadaVerifier;

class InternationalTadaController extends Controller
{
    /** Paged batch listing. */
    public function index()
    {
        $records = InternationalTada::query()
            ->with(['employee', 'country', 'tadaLevel'])
            ->newestFirst()
            ->paginate(7)
            ->withQueryString();

        return view('international.index', compact('records'));
    }

    public function create()
    {
        return view('international.add', [
            'nextBatch' => InternationalTada::nextBatchId(),
            'nextChalani' => InternationalTada::nextChalaniNumber(FiscalYear::current()?->getKey()),
            'countries' => Country::query()->orderBy('Country_name')->get(['Country_id', 'Country_name']),
            'verifiers' => TadaVerifier::query()->orderBy('tadaverifierPost')->get(),
            'employees' => $this->employeesWithTadaLevel(),
        ]);
    }

    /** Inserts the batch row by row (no transaction, per-employee warnings). */
    public function store(StoreInternationalTadaRequest $request, CreateBatch $createBatch)
    {
        $fiscalYearId = FiscalYear::current()?->getKey();
        if ($fiscalYearId === null) {
            return back()->withInput()->with('error', '⚠️ Error: No active fiscal year found!');
        }

        $result = $createBatch->handle($request->validated(), (int) $fiscalYearId, (int) ($request->user()->getKey() ?? 1));
        $errors = $result['errors'];

        if ($result['inserted'] > 0) {
            $batchId = $result['batchId'];
            SendBatchMails::dispatchAfterResponse('International', $batchId);

            $message = "✅ Batch {$batchId} created successfully!\n"
                .InternationalTada::chalaniRange($result['first'], $result['last'])
                ."\nEmployees Added: {$result['inserted']}\nFiscal Year ID: {$fiscalYearId}";

            return redirect()->route('international.index')
                ->with('success', $message)
                ->with('warning', $errors ? "Warnings:\n".implode("\n", $errors) : null);
        }

        $errorMsg = '❌ Error: Could not insert any records.';
        if (! empty($errors)) {
            $errorMsg .= "\n\n".implode("\n", $errors);
        }

        return back()->withInput()->with('error', $errorMsg);
    }

    public function edit(string $batch)
    {
        $batchData = InternationalTada::query()->batch($batch)->firstOrFail();

        return view('international.edit', [
            'batchId' => $batch,
            'batchData' => $batchData,
            'batchEmployees' => InternationalTada::query()
                ->batch($batch)
                ->with(['employee', 'tadaLevel'])
                ->orderBy('International_tada_id')
                ->get()
                ->map->toFormEmployee(),
            'countries' => Country::query()->orderBy('Country_name')->get(['Country_id', 'Country_name']),
            'cities' => City::query()->orderBy('City_name')->get(['City_id', 'City_name', 'Country_id']),
            'allEmployees' => $this->employeesWithTadaLevel(),
        ]);
    }

    /** Validates every employee first, then replaces the batch rows inside one transaction. */
    public function update(UpdateInternationalTadaRequest $request, string $batch, UpdateBatch $updateBatch)
    {
        $batchData = InternationalTada::query()->batch($batch)->firstOrFail();

        $result = $updateBatch->handle($batchData, $request->validated(), (int) ($request->user()->getKey() ?? 1));

        if ($result['ok']) {
            return redirect()->route('international.index')
                ->with('success', $result['message'])
                ->with('warning', $result['warnings'] ? "Warnings:\n".implode("\n", $result['warnings']) : null);
        }

        return redirect()->route('international.edit', $batch)->withInput()->with('error', $result['message']);
    }

    /** Standalone printable Anusuchi-5 forms for a batch. */
    public function print(string $batch)
    {
        $employees = InternationalTada::query()
            ->batch($batch)
            ->with(['fiscalYear', 'country', 'city', 'tadaLevel', 'employee.designationType'])
            ->orderBy('International_tada_id')
            ->get();
        abort_if($employees->isEmpty(), 404, 'No records found for this batch');

        return view('international.print', [
            'batchId' => $batch,
            'employees' => $employees,
            'fyText' => $employees->first()->fiscalYear?->fy ?? 'N/A',
            'usdRate' => $employees->first()->usdRate()?->amount,
        ]);
    }

    /** Employee dropdown source (with their TADA level, matched by level name). */
    private function employeesWithTadaLevel()
    {
        return Employee::query()
            ->with('tadaLevel')
            ->orderBy('EmpName')
            ->get(['EmpPersonalCode', 'EmpName', 'LevelName']);
    }
}
