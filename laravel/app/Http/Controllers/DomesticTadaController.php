<?php

namespace App\Http\Controllers;

use App\Services\DomesticTadaService;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DomesticTadaController extends Controller
{
    public function __construct(private DomesticTadaService $tada)
    {
    }

    // ─── DomesticTadaView.php ───────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));

        return view('domestic.index', ['searchName' => $search] + $this->tada->batchListing($search, $page));
    }

    // ─── AddDomesticTada.php ────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $fiscalYearId = $this->tada->currentFiscalYearId();
        if ($fiscalYearId === null) {
            abort(response("<div style='background: #fee; padding: 20px; border-radius: 5px; color: #c00;'>
        <h3>❌ Fiscal Year Not Found!</h3>
        <p>No active fiscal year found for today's date. Please configure fiscal year master table.</p>
        </div>", 500));
        }

        if ($request->isMethod('post')) {
            $result = $this->store($request, $fiscalYearId);
            if ($result !== null) {
                return $result;
            }
        }

        return view('domestic.add', [
            'nextBatch' => $this->tada->nextBatchId(),
            'nextChalani' => $this->tada->nextChalani(),
            'districts' => $this->tada->districts(),
            'tadaTypes' => $this->tada->travelTypes(),
            'verifiers' => $this->tada->verifiers(),
            'employees' => $this->tada->employeesWithRate(),
        ]);
    }

    /** Returns a redirect on success, or null to re-render the form (alerts are flashed). */
    private function store(Request $request, int $fiscalYearId)
    {
        $request->validate([
            'form_date' => 'required|date',
            'district_id' => 'required',
            'travel_objective' => 'required|string',
            'travelDateStart' => 'required|date',
            'travelDateEnd' => 'required|date',
            'tada_type_id' => 'nullable',
            'tadaverifier_id' => 'nullable',
            'employee_data' => 'nullable|string',
        ]);

        $employeeData = (string) $request->input('employee_data', '');
        $tadaTypeId = (int) $request->input('tada_type_id');
        $verifierId = (int) $request->input('tadaverifier_id');

        if ($employeeData === '') {
            return $this->alertBack('⚠️ Please add at least one employee!');
        }
        if ($tadaTypeId <= 0) {
            return $this->alertBack('⚠️ Please select a TADA Type!');
        }
        if ($verifierId <= 0) {
            return $this->alertBack('⚠️ Please select a TADA Verifier!');
        }

        $start = date('Y-m-d', strtotime($request->input('travelDateStart')));
        $end = date('Y-m-d', strtotime($request->input('travelDateEnd')));
        if ($this->tada->totalDays($start, $end) <= 0) {
            return $this->alertBack('❌ End date must be same or after start date.');
        }

        $batchId = $this->tada->nextBatchId();
        $result = $this->tada->createBatch($batchId, [
            'form_date' => date('Y-m-d', strtotime($request->input('form_date'))),
            'district_id' => (int) $request->input('district_id'),
            'travel_objective' => trim($request->input('travel_objective')),
            'travelDateStart' => $start,
            'travelDateEnd' => $end,
            'is_twenty_percent_extra' => $request->has('is_twenty_percent_extra') ? 1 : 0,
            'tada_type_id' => $tadaTypeId,
            'tadaverifier_id' => $verifierId,
        ], explode(',', $employeeData), $fiscalYearId, session('user_id', 1));

        if ($result['inserted'] > 0) {
            try {
                app(MailService::class)->sendDomesticBatch($batchId);
            } catch (\Throwable $e) {
                Log::error('Domestic batch mail failed: '.$e->getMessage());
            }

            $range = ($result['firstChalani'] == $result['lastChalani'])
                ? "Chalani #: {$result['firstChalani']}"
                : "Chalani #: {$result['firstChalani']} - {$result['lastChalani']}";
            $message = "✅ Batch {$batchId} created successfully!\n{$range}\nEmployees Added: {$result['inserted']}";
            if (! empty($result['errors'])) {
                $message .= "\n\nWarnings:\n".implode("\n", $result['errors']);
            }

            return redirect()->route('domestic.index')->with('alert', $message);
        }

        $errorMsg = "❌ Error: Could not insert any records.\n\n";
        if (! empty($result['errors'])) {
            $errorMsg .= "Errors:\n".implode("\n", array_slice($result['errors'], 0, 5));
            if (count($result['errors']) > 5) {
                $errorMsg .= "\n... and ".(count($result['errors']) - 5).' more errors';
            }
        }

        return $this->alertBack($errorMsg);
    }

    // ─── EditDomesticTada.php ───────────────────────────────────────────────────

    public function edit(Request $request)
    {
        $batchId = $request->query('batch_id');
        if (! $batchId) {
            return redirect()->route('domestic.index')->with('alert', 'No batch ID provided!');
        }
        $batchId = (string) $batchId;

        $batch = $this->tada->batchHeader($batchId);
        if (! $batch) {
            return redirect()->route('domestic.index')->with('alert', 'Batch not found!');
        }

        if ($request->isMethod('post')) {
            $result = $this->update($request, $batchId, $batch);
            if ($result !== null) {
                return $result;
            }
        }

        return view('domestic.edit', [
            'batchId' => $batchId,
            'batch' => $batch,
            'batchEmployees' => $this->tada->batchEmployees($batchId),
            'districts' => $this->tada->districts(),
            'tadaTypes' => $this->tada->travelTypes(),
            'verifiers' => $this->tada->verifiers(),
            'allEmployees' => $this->tada->employeesWithRate(),
        ]);
    }

    private function update(Request $request, string $batchId, object $batch)
    {
        $request->validate([
            'form_date' => 'required|date',
            'district_id' => 'required',
            'travel_objective' => 'required|string',
            'travelDateStart' => 'required|date',
            'travelDateEnd' => 'required|date',
            'tada_type_id' => 'nullable',
            'tadaverifier_id' => 'nullable',
            'employee_data' => 'nullable|string',
        ]);

        $employeeData = (string) $request->input('employee_data', '');
        $verifierId = (int) $request->input('tadaverifier_id');

        if ($employeeData === '') {
            return $this->alertBack('⚠️ Please add at least one employee!');
        }
        if ($verifierId <= 0) {
            return $this->alertBack('⚠️ Please select a TADA Verifier!');
        }

        $start = date('Y-m-d', strtotime($request->input('travelDateStart')));
        $end = date('Y-m-d', strtotime($request->input('travelDateEnd')));
        $totalDays = $this->tada->totalDays($start, $end);
        if ($totalDays <= 0) {
            return $this->alertBack('❌ End date must be the same as or after start date.');
        }

        $fiscalYearId = $this->tada->currentFiscalYearId() ?? $batch->fiscal_year_master_id;
        if ($fiscalYearId === null) {
            return $this->alertBack('⚠️ No active fiscal year found. Cannot save.');
        }

        // Step 1: pre-validate submitted employees ("code:twentyPercentFlag")
        $payloads = [];
        $preErrors = [];
        foreach (explode(',', $employeeData) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }
            $parts = explode(':', $entry);
            if (count($parts) !== 2) {
                $preErrors[] = "Invalid format: {$entry}";
                continue;
            }
            $empCode = trim($parts[0]);
            $twenty = (int) $parts[1];

            try {
                $emp = $this->tada->employeeWithRate($empCode);
            } catch (\Throwable $e) {
                $preErrors[] = "Employee {$empCode}: query failed";
                continue;
            }
            if (! $emp) {
                $preErrors[] = "Employee {$empCode}: not found";
                continue;
            }

            $payloads[$empCode] = [
                'is_twenty_pct_extra' => $twenty,
                'total_tada' => $this->tada->calculateTada($totalDays, (float) $emp->tadaInNepali, $twenty == 1),
                'total_days' => $totalDays,
            ];
        }

        if (empty($payloads)) {
            $msg = '❌ No valid employees to save.';
            if (! empty($preErrors)) {
                $msg .= "\n\n".implode("\n", $preErrors);
            }

            return $this->alertBack($msg);
        }

        $result = $this->tada->updateBatch($batchId, [
            'form_date' => date('Y-m-d', strtotime($request->input('form_date'))),
            'district_id' => (int) $request->input('district_id'),
            'travel_objective' => trim($request->input('travel_objective')),
            'travelDateStart' => $start,
            'travelDateEnd' => $end,
            'tada_type_id' => (int) $request->input('tada_type_id'),
            'tadaverifier_id' => $verifierId,
        ], $payloads, (int) $fiscalYearId, session('user_id', 1));

        if ($result['ok']) {
            $message = "✅ Batch {$batchId} updated successfully!\n";
            $message .= "Updated: {$result['updated']} | Added: {$result['inserted']} | Removed: {$result['deleted']}";
            if (! empty($preErrors)) {
                $message .= "\n\nWarnings:\n".implode("\n", $preErrors);
            }

            return redirect()->route('domestic.index')->with('alert', $message);
        }

        return $this->alertBack("❌ Update failed — NO records were changed.\n".implode("\n", array_merge($preErrors, $result['errors'])));
    }

    // ─── PrintDomesticTada.php ──────────────────────────────────────────────────

    public function print(Request $request)
    {
        $batchId = (string) $request->query('batch_id', '');
        if ($batchId === '') {
            abort(400, 'Invalid Batch ID!');
        }

        $records = $this->tada->printRecords($batchId);
        if ($records->isEmpty()) {
            abort(404, 'No records found for this batch!');
        }

        return view('domestic.print', ['batchId' => $batchId, 'records' => $records]);
    }

    private function alertBack(string $message)
    {
        return back()->with('alert', $message);
    }
}
