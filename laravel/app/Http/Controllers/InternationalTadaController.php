<?php

namespace App\Http\Controllers;

use App\Services\InternationalTadaService;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InternationalTadaController extends Controller
{
    public function __construct(private InternationalTadaService $tada)
    {
    }

    /** InternationalVraman.php — paged batch listing. */
    public function index(Request $request)
    {
        $recordsPerPage = 7;
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $recordsPerPage;

        $totalRecords = $this->tada->totalRecords();
        $totalPages = (int) ceil($totalRecords / $recordsPerPage);
        $records = $this->tada->listRecords($offset, $recordsPerPage);

        return view('international.index', compact('records', 'currentPage', 'recordsPerPage', 'totalRecords', 'totalPages'));
    }

    /** add_international_tada.php (GET) */
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

    /** add_international_tada.php (POST) — inserts the batch row by row (no transaction, per-employee warnings). */
    public function store(Request $request)
    {
        $fiscalYearId = $this->tada->currentFiscalYearId();
        if ($fiscalYearId === null) {
            return back()->withInput()->with('alert', '⚠️ Error: No active fiscal year found!');
        }

        $validator = Validator::make($request->all(), [
            'form_date' => ['required', 'date'],
            'country_id' => ['required', 'integer'],
            'city_id' => ['required', 'integer'],
            'travel_objective' => ['required', 'string'],
            'travelDateStart' => ['required', 'date'],
            'travelDateEnd' => ['required', 'date', 'after_or_equal:travelDateStart'],
            'employee_data' => ['required', 'string'],
            'tadaverifier_id' => ['required', 'integer', 'min:1'],
        ], [
            'employee_data.required' => 'Please add at least one employee!',
            'tadaverifier_id.required' => 'Please select a TADA Verifier!',
            'tadaverifier_id.min' => 'Please select a TADA Verifier!',
            'tadaverifier_id.integer' => 'Please select a TADA Verifier!',
            'travelDateEnd.after_or_equal' => 'End date cannot be before start date!',
        ]);
        if ($validator->fails()) {
            return back()->withInput()->with('alert', '⚠️ '.$validator->errors()->first());
        }

        $countryId = (int) $request->input('country_id');
        $cityId = (int) $request->input('city_id');
        $travelObjective = trim($request->input('travel_objective'));
        $verifierId = (int) $request->input('tadaverifier_id');
        $createdBy = session('user_id') ?? 1;

        $formDate = date('Y-m-d', strtotime($request->input('form_date')));
        $startDate = date('Y-m-d', strtotime($request->input('travelDateStart')));
        $endDate = date('Y-m-d', strtotime($request->input('travelDateEnd')));
        $totalDays = $this->tada->totalDays($startDate, $endDate);

        $batchId = $this->tada->nextBatchId();
        $parsed = $this->tada->parseEmployeeData($request->input('employee_data'));
        $errors = $parsed['errors'];
        $insertedCount = 0;
        $firstChalani = null;
        $lastChalani = null;

        foreach ($parsed['entries'] as $entry) {
            $empCode = $entry['code'];

            $chalaniId = $this->tada->nextChalaniNumber($fiscalYearId);
            if ($firstChalani === null) {
                $firstChalani = $chalaniId;
            }
            $lastChalani = $chalaniId;

            try {
                $detail = $this->tada->employeeTadaDetail($empCode);
            } catch (\Throwable $e) {
                $errors[] = "Employee {$empCode}: Query failed - ".$e->getMessage();

                continue;
            }
            if (! $detail || empty($detail->TadaDefinerMasterBylevel_id)) {
                $errors[] = "Employee {$empCode}: TADA info missing";

                continue;
            }

            try {
                $this->tada->insertRow([
                    'Batch_id' => $batchId,
                    'fiscal_year_master_id' => $fiscalYearId,
                    'Chalani_id' => $chalaniId,
                    'form_date' => $formDate,
                    'EmpPersonalCode' => $empCode,
                    'Country_id' => $countryId,
                    'City_id' => $cityId,
                    'travel_objective' => $travelObjective,
                    'travelDateStart' => $startDate,
                    'travelDateEnd' => $endDate,
                    'TadaDefinerMasterBylevel_id' => (int) $detail->TadaDefinerMasterBylevel_id,
                    'totalUSdrecevid' => $this->tada->totalUsd($totalDays, $detail->tadaInUSD),
                    'DressAllowance' => $entry['dress'],
                    'createdBy' => $createdBy,
                    'tadaverifier_id' => $verifierId,
                ]);
            } catch (\Throwable $e) {
                $errors[] = "Employee {$empCode}: Insert failed - ".$e->getMessage();

                continue;
            }
            $insertedCount++;
        }

        if ($insertedCount > 0) {
            try {
                app(MailService::class)->sendInternationalBatch($batchId);
            } catch (\Throwable $e) {
                report($e);
            }

            $chalaniRange = ($firstChalani == $lastChalani) ? "Chalani #: {$firstChalani}" : "Chalani #: {$firstChalani} - {$lastChalani}";
            $message = "✅ Batch {$batchId} created successfully!\n{$chalaniRange}\nEmployees Added: {$insertedCount}\nFiscal Year ID: {$fiscalYearId}";
            if (! empty($errors)) {
                $message .= "\n\nWarnings:\n".implode("\n", $errors);
            }

            return redirect()->route('international.tada.index')->with('alert', $message);
        }

        $errorMsg = '❌ Error: Could not insert any records.';
        if (! empty($errors)) {
            $errorMsg .= "\n\n".implode("\n", $errors);
        }

        return back()->withInput()->with('alert', $errorMsg);
    }

    /** edit_international_tada.php (GET) */
    public function edit(Request $request)
    {
        $batchId = $request->query('batch_id');
        if (! $batchId) {
            return redirect()->route('international.tada.index')->with('alert', 'No batch ID provided!');
        }

        $batchData = $this->tada->batchHeader($batchId);
        if (! $batchData) {
            return redirect()->route('international.tada.index')->with('alert', 'Batch not found!');
        }

        return view('international.edit', [
            'batchId' => $batchId,
            'batchData' => $batchData,
            'batchEmployees' => $this->tada->batchEmployees($batchId),
            'countries' => $this->tada->countries(),
            'cities' => $this->tada->allCities(),
            'allEmployees' => $this->tada->employeesWithTada(),
        ]);
    }

    /**
     * edit_international_tada.php (POST) — validates every employee first, then replaces the batch rows
     * inside one transaction so a failure never loses the original data (as in the legacy page).
     */
    public function update(Request $request)
    {
        $batchId = $request->query('batch_id');
        if (! $batchId) {
            return redirect()->route('international.tada.index')->with('alert', 'No batch ID provided!');
        }
        $batchData = $this->tada->batchHeader($batchId);
        if (! $batchData) {
            return redirect()->route('international.tada.index')->with('alert', 'Batch not found!');
        }
        $back = redirect()->route('international.tada.edit', ['batch_id' => $batchId]);

        $validator = Validator::make($request->all(), [
            'form_date' => ['required', 'date'],
            'country_id' => ['required', 'integer'],
            'city_id' => ['required', 'integer'],
            'travel_objective' => ['required', 'string'],
            'travelDateStart' => ['required', 'date'],
            'travelDateEnd' => ['required', 'date', 'after_or_equal:travelDateStart'],
            'employee_data' => ['required', 'string'],
        ], [
            'employee_data.required' => 'Please add at least one employee!',
            'travelDateEnd.after_or_equal' => 'End date cannot be before start date!',
        ]);
        if ($validator->fails()) {
            return $back->withInput()->with('alert', '⚠️ '.$validator->errors()->first());
        }

        $countryId = (int) $request->input('country_id');
        $cityId = (int) $request->input('city_id');
        $travelObjective = trim($request->input('travel_objective'));
        $createdBy = session('user_id') ?? 1;

        $formDate = date('Y-m-d', strtotime($request->input('form_date')));
        $startDate = date('Y-m-d', strtotime($request->input('travelDateStart')));
        $endDate = date('Y-m-d', strtotime($request->input('travelDateEnd')));
        $totalDays = $this->tada->totalDays($startDate, $endDate);

        // Fiscal year scopes the chalani numbers; fall back to the batch's stored one.
        $fiscalYearId = $this->tada->currentFiscalYearId() ?? $batchData->fiscal_year_master_id;

        // Step 1 - pre-validate every entry before touching existing rows.
        $parsed = $this->tada->parseEmployeeData($request->input('employee_data'), 'Invalid format: ');
        $preErrors = $parsed['errors'];
        $payloads = [];
        foreach ($parsed['entries'] as $entry) {
            $empCode = $entry['code'];
            try {
                $detail = $this->tada->employeeTadaDetail($empCode);
            } catch (\Throwable $e) {
                $preErrors[] = "Employee {$empCode}: query failed";

                continue;
            }
            if (! $detail || empty($detail->TadaDefinerMasterBylevel_id)) {
                $preErrors[] = "Employee {$empCode}: no TADA level assigned — skipped";

                continue;
            }
            $payloads[] = [
                'emp_code' => $empCode,
                'dress_allowance' => $entry['dress'],
                'tada_level_id' => (int) $detail->TadaDefinerMasterBylevel_id,
                'total_usd' => $this->tada->totalUsd($totalDays, $detail->tadaInUSD),
            ];
        }

        if (empty($payloads)) {
            $msg = '❌ No valid employees to update.';
            if (! empty($preErrors)) {
                $msg .= "\n\n".implode("\n", $preErrors);
            }

            return $back->withInput()->with('alert', $msg);
        }

        // Step 2 - delete + re-insert inside one transaction.
        $insertedCount = 0;
        $insertErrors = [];
        $firstChalani = null;
        $lastChalani = null;

        DB::beginTransaction();
        try {
            DB::table('International_tada')->where('Batch_id', $batchId)->delete();

            foreach ($payloads as $payload) {
                // Re-queried inside the transaction so the just-deleted rows are not counted.
                $chalaniId = $this->tada->nextChalaniNumber($fiscalYearId);
                if ($firstChalani === null) {
                    $firstChalani = $chalaniId;
                }
                $lastChalani = $chalaniId;

                try {
                    $this->tada->insertRow([
                        'Batch_id' => $batchId,
                        'fiscal_year_master_id' => $fiscalYearId,
                        'Chalani_id' => $chalaniId,
                        'form_date' => $formDate,
                        'EmpPersonalCode' => $payload['emp_code'],
                        'Country_id' => $countryId,
                        'City_id' => $cityId,
                        'travel_objective' => $travelObjective,
                        'travelDateStart' => $startDate,
                        'travelDateEnd' => $endDate,
                        'TadaDefinerMasterBylevel_id' => $payload['tada_level_id'],
                        'totalUSdrecevid' => $payload['total_usd'],
                        'DressAllowance' => $payload['dress_allowance'],
                        'createdBy' => $createdBy,
                        // Legacy bug fix: the original verifier was silently dropped on every edit.
                        'tadaverifier_id' => $batchData->tadaverifier_id,
                    ]);
                } catch (\Throwable $e) {
                    $insertErrors[] = "Employee {$payload['emp_code']}: insert failed - ".$e->getMessage();
                    break; // stop inserting; roll back below
                }
                $insertedCount++;
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            return $back->withInput()->with('alert', '❌ Error deleting existing records: '.$e->getMessage());
        }

        if (empty($insertErrors) && $insertedCount > 0) {
            DB::commit();

            $chalaniRange = ($firstChalani == $lastChalani) ? "Chalani #: {$firstChalani}" : "Chalani #: {$firstChalani} - {$lastChalani}";
            $message = "✅ Batch {$batchId} updated successfully!\n{$chalaniRange}\nEmployees: {$insertedCount}";
            $warnings = array_merge($preErrors, $insertErrors);
            if (! empty($warnings)) {
                $message .= "\n\nWarnings:\n".implode("\n", $warnings);
            }

            return redirect()->route('international.tada.index')->with('alert', $message);
        }

        DB::rollBack();
        $msg = "❌ Update failed — original records have been kept.\n".implode("\n", array_merge($preErrors, $insertErrors));

        return $back->withInput()->with('alert', $msg);
    }

    /** print_international_tada.php — standalone printable Anusuchi-5 forms for a batch. */
    public function print(Request $request)
    {
        $batchId = $request->query('batch_id');
        if (! $batchId) {
            abort(400, 'Batch ID is required');
        }

        $employees = $this->tada->printRows($batchId)->all();
        if (empty($employees)) {
            abort(404, 'No records found for this batch');
        }

        $fyText = $employees[0]->FiscalYear ?? 'N/A';
        $rate = $this->tada->usdRateOn(\Carbon\Carbon::parse($employees[0]->form_date)->format('Y-m-d'));
        $usdRate = $rate ? $rate->amount : null;

        return view('international.print', [
            'batchId' => $batchId,
            'employees' => $employees,
            'fyText' => $fyText,
            'usdRate' => $usdRate,
        ]);
    }
}
