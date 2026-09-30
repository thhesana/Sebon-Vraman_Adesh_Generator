<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDomesticTadaRequest;
use App\Http\Requests\UpdateDomesticTadaRequest;
use App\Services\DomesticTadaService;
use App\Services\MailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DomesticTadaController extends Controller
{
    public function __construct(
        private DomesticTadaService $tada,
        private MailService $mail,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        return view('domestic.index', ['searchName' => $search] + $this->tada->batchListing($search));
    }

    public function create(): View
    {
        $this->requireFiscalYearId();

        return view('domestic.add', [
            'nextBatch' => $this->tada->nextBatchId(),
            'nextChalani' => $this->tada->nextChalani(),
            'districts' => $this->tada->districts(),
            'tadaTypes' => $this->tada->travelTypes(),
            'verifiers' => $this->tada->verifiers(),
            'employees' => $this->tada->employeesWithRate(),
        ]);
    }

    public function store(StoreDomesticTadaRequest $request): RedirectResponse
    {
        $fiscalYearId = $this->requireFiscalYearId();

        $start = date('Y-m-d', strtotime($request->input('travelDateStart')));
        $end = date('Y-m-d', strtotime($request->input('travelDateEnd')));

        $batchId = $this->tada->nextBatchId();
        $result = $this->tada->createBatch($batchId, [
            'form_date' => date('Y-m-d', strtotime($request->input('form_date'))),
            'district_id' => (int) $request->input('district_id'),
            'travel_objective' => trim($request->input('travel_objective')),
            'travelDateStart' => $start,
            'travelDateEnd' => $end,
            'is_twenty_percent_extra' => $request->has('is_twenty_percent_extra') ? 1 : 0,
            'tada_type_id' => (int) $request->input('tada_type_id'),
            'tadaverifier_id' => (int) $request->input('tadaverifier_id'),
        ], explode(',', (string) $request->input('employee_data')), $fiscalYearId, $request->user()->getKey());

        if ($result['inserted'] > 0) {
            try {
                $this->mail->sendDomesticBatch($batchId);
            } catch (\Throwable $e) {
                Log::error('Domestic batch mail failed: '.$e->getMessage());
            }

            $range = ($result['firstChalani'] == $result['lastChalani'])
                ? "Chalani #: {$result['firstChalani']}"
                : "Chalani #: {$result['firstChalani']} - {$result['lastChalani']}";
            $redirect = redirect()->route('domestic.index')
                ->with('success', "✅ Batch {$batchId} created successfully!\n{$range}\nEmployees Added: {$result['inserted']}");
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

        return view('domestic.edit', [
            'batchId' => $batch,
            'batch' => $header,
            'batchEmployees' => $this->tada->batchEmployees($batch),
            'districts' => $this->tada->districts(),
            'tadaTypes' => $this->tada->travelTypes(),
            'verifiers' => $this->tada->verifiers(),
            'allEmployees' => $this->tada->employeesWithRate(),
        ]);
    }

    public function update(UpdateDomesticTadaRequest $request, string $batch): RedirectResponse
    {
        $header = $this->findBatch($batch);

        $start = date('Y-m-d', strtotime($request->input('travelDateStart')));
        $end = date('Y-m-d', strtotime($request->input('travelDateEnd')));
        $totalDays = $this->tada->totalDays($start, $end);

        $fiscalYearId = $this->tada->currentFiscalYearId() ?? $header->fiscal_year_master_id;
        if ($fiscalYearId === null) {
            return back()->withInput()->with('error', '⚠️ No active fiscal year found. Cannot save.');
        }

        [$payloads, $preErrors] = $this->tada->preparePayloads((string) $request->input('employee_data'), $totalDays);

        if (empty($payloads)) {
            $msg = '❌ No valid employees to save.';
            if (! empty($preErrors)) {
                $msg .= "\n\n".implode("\n", $preErrors);
            }

            return back()->withInput()->with('error', $msg);
        }

        $result = $this->tada->updateBatch($batch, [
            'form_date' => date('Y-m-d', strtotime($request->input('form_date'))),
            'district_id' => (int) $request->input('district_id'),
            'travel_objective' => trim($request->input('travel_objective')),
            'travelDateStart' => $start,
            'travelDateEnd' => $end,
            'tada_type_id' => (int) $request->input('tada_type_id'),
            'tadaverifier_id' => (int) $request->input('tadaverifier_id'),
        ], $payloads, (int) $fiscalYearId, $request->user()->getKey());

        if ($result['ok']) {
            $redirect = redirect()->route('domestic.index')->with(
                'success',
                "✅ Batch {$batch} updated successfully!\nUpdated: {$result['updated']} | Added: {$result['inserted']} | Removed: {$result['deleted']}"
            );
            if (! empty($preErrors)) {
                $redirect->with('warning', "Warnings:\n".implode("\n", $preErrors));
            }

            return $redirect;
        }

        return back()->withInput()->with('error', "❌ Update failed — NO records were changed.\n".implode("\n", array_merge($preErrors, $result['errors'])));
    }

    public function print(string $batch): View
    {
        $records = $this->tada->printRecords($batch);
        abort_if($records->isEmpty(), 404, 'No records found for this batch!');

        return view('domestic.print', ['batchId' => $batch, 'records' => $records]);
    }

    private function findBatch(string $batch): object
    {
        $header = $this->tada->batchHeader($batch);
        abort_if(! $header, 404, 'Batch not found!');

        return $header;
    }

    private function requireFiscalYearId(): int
    {
        $fiscalYearId = $this->tada->currentFiscalYearId();
        if ($fiscalYearId === null) {
            abort(response("<div style='background: #fee; padding: 20px; border-radius: 5px; color: #c00;'>
        <h3>❌ Fiscal Year Not Found!</h3>
        <p>No active fiscal year found for today's date. Please configure fiscal year master table.</p>
        </div>", 500));
        }

        return $fiscalYearId;
    }
}
