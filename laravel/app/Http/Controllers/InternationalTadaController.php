<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInternationalTadaRequest;
use App\Http\Requests\UpdateInternationalTadaRequest;
use App\Services\InternationalTadaService;
use App\Services\MailService;
use Carbon\Carbon;

class InternationalTadaController extends Controller
{
    public function __construct(
        private InternationalTadaService $tada,
        private MailService $mail,
    ) {
    }

    /** Paged batch listing. */
    public function index()
    {
        return view('international.index', [
            'records' => $this->tada->listRecords(7),
        ]);
    }

    public function create()
    {
        return view('international.add', [
            'nextBatch' => $this->tada->nextBatchId(),
            'nextChalani' => $this->tada->nextChalaniNumber($this->tada->currentFiscalYearId()),
            'countries' => $this->tada->countries(),
            'verifiers' => $this->tada->verifiers(),
            'employees' => $this->tada->employeesWithTada(),
        ]);
    }

    /** Inserts the batch row by row (no transaction, per-employee warnings). */
    public function store(StoreInternationalTadaRequest $request)
    {
        $fiscalYearId = $this->tada->currentFiscalYearId();
        if ($fiscalYearId === null) {
            return back()->withInput()->with('error', '⚠️ Error: No active fiscal year found!');
        }

        $result = $this->tada->createBatch($request->validated(), (int) $fiscalYearId, (int) ($request->user()->getKey() ?? 1));
        $errors = $result['errors'];

        if ($result['inserted'] > 0) {
            $batchId = $result['batchId'];
            try {
                $this->mail->sendInternationalBatch($batchId);
            } catch (\Throwable $e) {
                report($e);
            }

            $message = "✅ Batch {$batchId} created successfully!\n"
                .$this->tada->chalaniRange($result['first'], $result['last'])
                ."\nEmployees Added: {$result['inserted']}\nFiscal Year ID: {$fiscalYearId}";

            $redirect = redirect()->route('international.index')->with('success', $message);
            if (! empty($errors)) {
                $redirect->with('warning', "Warnings:\n".implode("\n", $errors));
            }

            return $redirect;
        }

        $errorMsg = '❌ Error: Could not insert any records.';
        if (! empty($errors)) {
            $errorMsg .= "\n\n".implode("\n", $errors);
        }

        return back()->withInput()->with('error', $errorMsg);
    }

    public function edit(string $batch)
    {
        $batchData = $this->tada->batchHeader($batch);
        abort_if($batchData === null, 404);

        return view('international.edit', [
            'batchId' => $batch,
            'batchData' => $batchData,
            'batchEmployees' => $this->tada->batchEmployees($batch),
            'countries' => $this->tada->countries(),
            'cities' => $this->tada->allCities(),
            'allEmployees' => $this->tada->employeesWithTada(),
        ]);
    }

    /** Validates every employee first, then replaces the batch rows inside one transaction. */
    public function update(UpdateInternationalTadaRequest $request, string $batch)
    {
        $batchData = $this->tada->batchHeader($batch);
        abort_if($batchData === null, 404);

        $result = $this->tada->updateBatch($batch, $batchData, $request->validated(), (int) ($request->user()->getKey() ?? 1));

        if ($result['ok']) {
            $redirect = redirect()->route('international.index')->with('success', $result['message']);
            if (! empty($result['warnings'])) {
                $redirect->with('warning', "Warnings:\n".implode("\n", $result['warnings']));
            }

            return $redirect;
        }

        return redirect()->route('international.edit', $batch)->withInput()->with('error', $result['message']);
    }

    /** Standalone printable Anusuchi-5 forms for a batch. */
    public function print(string $batch)
    {
        $employees = $this->tada->printRows($batch)->all();
        abort_if(empty($employees), 404, 'No records found for this batch');

        $fyText = $employees[0]->FiscalYear ?? 'N/A';
        $rate = $this->tada->usdRateOn(Carbon::parse($employees[0]->form_date)->format('Y-m-d'));

        return view('international.print', [
            'batchId' => $batch,
            'employees' => $employees,
            'fyText' => $fyText,
            'usdRate' => $rate ? $rate->amount : null,
        ]);
    }
}
