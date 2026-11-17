<?php

namespace App\Http\Controllers;

use App\Actions\Domestic\CreateBatch;
use App\Actions\Domestic\UpdateBatch;
use App\Http\Requests\StoreDomesticTadaRequest;
use App\Http\Requests\UpdateDomesticTadaRequest;
use App\Jobs\SendBatchMails;
use App\Models\District;
use App\Models\DomesticTada;
use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\TadaVerifier;
use App\Models\TravelType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomesticTadaController extends Controller
{
    private const PER_PAGE = 5;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        // Pages hold whole batches: paginate the batch ids, then load every row of those batches.
        $batches = DomesticTada::batchSummaries($search)->paginate(self::PER_PAGE)->withQueryString();
        $records = DomesticTada::listing($batches->pluck('domestic_Batch_id'))->get();

        return view('domestic.index', [
            'searchName' => $search,
            'batches' => $batches,
            'records' => $records,
            'totalRecords' => DomesticTada::employeeNameLike($search)->count(),
            'totalRecordsOnPage' => $records->count(),
        ]);
    }

    public function create(): View
    {
        $this->currentFiscalYearId();

        return view('domestic.add', [
            'nextBatch' => DomesticTada::nextBatchId(),
            'nextChalani' => DomesticTada::nextChalani(),
        ] + $this->formLookups());
    }

    public function store(StoreDomesticTadaRequest $request, CreateBatch $createBatch): RedirectResponse
    {
        $result = $createBatch(
            $request->batchAttributes(),
            $request->has('is_twenty_percent_extra'),
            (string) $request->input('employee_data'),
            $this->currentFiscalYearId(),
            $request->user()->getKey(),
        );

        if ($result['inserted'] > 0) {
            SendBatchMails::dispatchAfterResponse('Domestic', $result['batchId']);

            $range = ($result['firstChalani'] == $result['lastChalani'])
                ? "Chalani #: {$result['firstChalani']}"
                : "Chalani #: {$result['firstChalani']} - {$result['lastChalani']}";
            $redirect = redirect()->route('domestic.index')
                ->with('success', "✅ Batch {$result['batchId']} created successfully!\n{$range}\nEmployees Added: {$result['inserted']}");
            if (! empty($result['errors'])) {
                $redirect->with('warning', "Warnings:\n".implode("\n", $result['errors']));
            }

            return $redirect;
        }

        $errorMsg = "❌ Error: Could not insert any records.\n\n";
        if (! empty($result['errors'])) {
            $errorMsg .= "Errors:\n".implode("\n", array_slice($result['errors'], 0, 5));
            if (count($result['errors']) > 5) {
                $errorMsg .= "\n... and ".(count($result['errors']) - 5).' more errors';
            }
        }

        return back()->withInput()->with('error', $errorMsg);
    }

    public function edit(string $batch): View
    {
        $header = $this->findBatch($batch);

        $existingEmployees = DomesticTada::batch($batch)
            ->with('employee.level')
            ->orderBy('domestic_tada_id')
            ->get()
            ->map(fn (DomesticTada $row) => [
                'code' => $row->EmpPersonalCode,
                'name' => $row->employee?->EmpName,
                'level' => $row->employee?->level_label ?? 'No Level',
                'rate' => $row->employee?->domestic_rate ?? DomesticTada::DEFAULT_RATE,
                'extra' => $row->domestic_isTwentyPercentExtra,
            ]);

        return view('domestic.edit', [
            'batchId' => $batch,
            'batch' => $header,
            'existingEmployees' => $existingEmployees,
        ] + $this->formLookups());
    }

    public function update(UpdateDomesticTadaRequest $request, string $batch, UpdateBatch $updateBatch): RedirectResponse
    {
        $header = $this->findBatch($batch);

        $fiscalYearId = FiscalYear::current()?->getKey() ?? $header->fiscal_year_master_id;
        if ($fiscalYearId === null) {
            return back()->withInput()->with('error', '⚠️ No active fiscal year found. Cannot save.');
        }

        $result = $updateBatch(
            $batch,
            $request->batchAttributes(),
            (string) $request->input('employee_data'),
            (int) $fiscalYearId,
            $request->user()->getKey(),
        );
        $warnings = $result['warnings'];

        if ($result['noValidEmployees']) {
            $msg = '❌ No valid employees to save.';
            if (! empty($warnings)) {
                $msg .= "\n\n".implode("\n", $warnings);
            }

            return back()->withInput()->with('error', $msg);
        }

        if ($result['ok']) {
            $redirect = redirect()->route('domestic.index')->with(
                'success',
                "✅ Batch {$batch} updated successfully!\nUpdated: {$result['updated']} | Added: {$result['inserted']} | Removed: {$result['deleted']}"
            );
            if (! empty($warnings)) {
                $redirect->with('warning', "Warnings:\n".implode("\n", $warnings));
            }

            return $redirect;
        }

        return back()->withInput()->with('error', "❌ Update failed — NO records were changed.\n".implode("\n", array_merge($warnings, $result['errors'])));
    }

    public function print(string $batch): View
    {
        $records = DomesticTada::forPrint($batch)->get();
        abort_if($records->isEmpty(), 404, 'No records found for this batch!');

        return view('domestic.print', ['batchId' => $batch, 'records' => $records]);
    }

    /** Dropdown data shared by the add and edit forms. */
    private function formLookups(): array
    {
        return [
            'districts' => District::orderBy('District_name')->pluck('District_name', 'District_id'),
            'tadaTypes' => TravelType::orderBy('type')->pluck('type', 'TadaTypeMaster_id'),
            'verifiers' => TadaVerifier::orderBy('tadaverifierPost')->pluck('tadaverifierPost', 'tadaverifier_id'),
            'employees' => Employee::with('level')->orderBy('EmpName')->get(),
        ];
    }

    private function findBatch(string $batch): DomesticTada
    {
        $header = DomesticTada::batch($batch)->first();
        abort_if(! $header, 404, 'Batch not found!');

        return $header;
    }

    private function currentFiscalYearId(): int
    {
        $fiscalYear = FiscalYear::current();
        if ($fiscalYear === null) {
            abort(response("<div style='background: #fee; padding: 20px; border-radius: 5px; color: #c00;'>
        <h3>❌ Fiscal Year Not Found!</h3>
        <p>No active fiscal year found for today's date. Please configure fiscal year master table.</p>
        </div>", 500));
        }

        return (int) $fiscalYear->getKey();
    }
}
