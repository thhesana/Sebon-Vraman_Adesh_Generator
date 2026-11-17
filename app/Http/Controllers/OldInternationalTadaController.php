<?php

namespace App\Http\Controllers;

use App\Http\Requests\OldInternationalTadaRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\DesignationType;
use App\Models\Employee;
use App\Models\OldInternationalTada;
use App\Models\TadaVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * "Old Int'l Travel": manual entry of international travel orders issued before this system.
 * One record per employee order; amounts are stored as typed and no mail is sent.
 */
class OldInternationalTadaController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $records = OldInternationalTada::query()
            ->with(['employee', 'country', 'city', 'fiscalYear'])
            ->search($search)
            ->newestFirst()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('old_international.index', compact('records', 'search'));
    }

    public function create(): View
    {
        return view('old_international.add', ['record' => new OldInternationalTada] + $this->formLookups());
    }

    public function store(OldInternationalTadaRequest $request): RedirectResponse
    {
        $record = OldInternationalTada::create($request->record() + [
            'createdBy' => (string) $request->user()->getKey(),
        ]);

        return redirect()->route('old_international.index')
            ->with('success', "✅ Old travel record saved (Chalani #: {$record->Chalani_id}, Employee: {$record->EmpPersonalCode}).");
    }

    public function edit(OldInternationalTada $record): View
    {
        return view('old_international.edit', ['record' => $record] + $this->formLookups());
    }

    public function update(OldInternationalTadaRequest $request, OldInternationalTada $record): RedirectResponse
    {
        $record->update($request->record() + [
            'updatedBy' => (string) $request->user()->getKey(),
            // Database clock, like createddate (PHP's app timezone is UTC, the server's is Nepal time).
            'updateddate' => DB::raw('GETDATE()'),
        ]);

        return redirect()->route('old_international.index')
            ->with('success', "✅ Old travel record updated (Chalani #: {$record->Chalani_id}, Employee: {$record->EmpPersonalCode}).");
    }

    /** Printable Anusuchi-5 form built only from the stored figures. */
    public function print(OldInternationalTada $record): View
    {
        $record->load(['fiscalYear', 'country', 'city', 'verifier', 'designation', 'employee']);

        return view('old_international.print', ['record' => $record]);
    }

    /** Dropdown data shared by the add and edit forms. */
    private function formLookups(): array
    {
        return [
            'countries' => Country::query()->orderBy('Country_name')->pluck('Country_name', 'Country_id'),
            'cities' => City::query()->orderBy('City_name')->get(['City_id', 'City_name', 'Country_id']),
            'verifiers' => TadaVerifier::query()->orderBy('tadaverifierPost')->pluck('tadaverifierPost', 'tadaverifier_id'),
            'designations' => DesignationType::query()->orderBy('designationType')->get(['id', 'designationType', 'designationTypeInNepali']),
            'employees' => Employee::query()->orderBy('EmpName')->get(['EmpPersonalCode', 'EmpName']),
        ];
    }
}
