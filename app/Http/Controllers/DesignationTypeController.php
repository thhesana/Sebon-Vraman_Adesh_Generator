<?php

namespace App\Http\Controllers;

use App\Http\Requests\DesignationTypeRequest;
use App\Models\DesignationType;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** "Position Master": list, add and edit DesignationTypeMaster rows. */
class DesignationTypeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $positions = DesignationType::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('designationType', 'like', '%'.$search.'%')
                ->orWhere('designationTypeInNepali', 'like', '%'.$search.'%')))
            ->withCount('employees')
            ->orderBy('designationType')
            ->paginate(10)
            ->withQueryString();

        return view('position.index', compact('positions', 'search'));
    }

    public function create(): View
    {
        return view('position.create');
    }

    public function store(DesignationTypeRequest $request): RedirectResponse
    {
        DesignationType::create($request->validated());

        return redirect()->route('positions.index')->with('success', 'Position Added Successfully');
    }

    public function edit(DesignationType $position): View
    {
        return view('position.edit', compact('position'));
    }

    /**
     * Employees reference their position by NAME (Employee_Information.Designation), so a rename
     * is carried over to those employees in the same transaction.
     */
    public function update(DesignationTypeRequest $request, DesignationType $position): RedirectResponse
    {
        $oldName = $position->designationType;
        $moved = 0;

        DB::transaction(function () use ($request, $position, $oldName, &$moved) {
            $position->update($request->validated());

            if ($position->designationType !== $oldName) {
                $moved = Employee::where('Designation', $oldName)
                    ->update(['Designation' => $position->designationType, 'Designation_id' => $position->getKey()]);
            }
        });

        $message = 'Position Updated Successfully';
        if ($moved > 0) {
            $message .= " ({$moved} employee(s) updated to the new name)";
        }

        return redirect()->route('positions.index')->with('success', $message);
    }
}
