<?php

namespace App\Http\Controllers;

use App\Models\DesignationType;
use App\Models\Employee;
use App\Models\LevelNameMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::query()
            ->select(['EmpPersonalCode', 'EmpNameInNepali', 'EmpName', 'Designation', 'LevelName', 'Gender', 'Email'])
            ->orderBy('EmpName')
            ->get();

        return view('employee.index', compact('employees'));
    }

    public function create()
    {
        return view('employee.create', $this->dropdowns());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'EmpPersonalCode' => ['required', 'string', 'max:50', Rule::unique(Employee::class, 'EmpPersonalCode')],
            'EmpNameInNepali' => ['required', 'string', 'max:255'],
            'EmpName' => ['required', 'string', 'max:255'],
            'Designation' => ['required', 'string', 'max:255'],
            'LevelName' => ['required', 'string', 'max:255'],
            'Gender' => ['required', 'in:Male,Female,Other'],
            'Email' => ['required', 'email', 'max:255'],
        ]);

        Employee::create($data);

        return redirect('/employee_view.php')->with('alert', 'Employee Added Successfully');
    }

    public function edit(Request $request)
    {
        $emp = Employee::where('EmpPersonalCode', $this->code($request))->firstOrFail();

        return view('employee.edit', ['emp' => $emp] + $this->dropdowns());
    }

    public function update(Request $request)
    {
        $code = $this->code($request);
        Employee::where('EmpPersonalCode', $code)->firstOrFail();

        $data = $request->validate([
            'EmpNameInNepali' => ['required', 'string', 'max:255'],
            'EmpName' => ['required', 'string', 'max:255'],
            'Designation' => ['required', 'string', 'max:255'],
            'LevelName' => ['required', 'string', 'max:255'],
            'Gender' => ['required', 'in:Male,Female,Other'],
            'Email' => ['required', 'email', 'max:255'],
        ]);

        Employee::where('EmpPersonalCode', $code)->update($data);

        return redirect('/employee_view.php')->with('alert', 'Employee Updated Successfully');
    }

    private function code(Request $request): string
    {
        $code = $request->query('code');
        if (! $code) {
            abort(400, 'Invalid employee code');
        }

        return (string) $code;
    }

    private function dropdowns(): array
    {
        return [
            'designations' => DesignationType::orderBy('designationType')->get(['id', 'designationType']),
            'levels' => LevelNameMaster::orderBy('levelName')->get(['id', 'levelName']),
        ];
    }
}
