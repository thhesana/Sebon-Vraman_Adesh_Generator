<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\DesignationType;
use App\Models\Employee;
use App\Models\LevelNameMaster;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::query()->orderBy('EmpName')->get();

        return view('employee.index', compact('employees'));
    }

    public function create()
    {
        return view('employee.create', $this->dropdowns());
    }

    public function store(StoreEmployeeRequest $request)
    {
        Employee::create($request->validated());

        return redirect()->route('employees.index')->with('success', 'Employee Added Successfully');
    }

    public function edit(Employee $employee)
    {
        return view('employee.edit', ['emp' => $employee] + $this->dropdowns());
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());

        return redirect()->route('employees.index')->with('success', 'Employee Updated Successfully');
    }

    private function dropdowns(): array
    {
        return [
            'designations' => DesignationType::orderBy('designationType')->get(),
            'levels' => LevelNameMaster::orderBy('levelName')->get(),
        ];
    }
}
