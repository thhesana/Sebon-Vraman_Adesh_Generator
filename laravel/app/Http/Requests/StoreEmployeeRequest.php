<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Employee;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'EmpPersonalCode' => ['required', 'string', 'max:50', Rule::unique(Employee::class, 'EmpPersonalCode')],
            'EmpNameInNepali' => ['required', 'string', 'max:255'],
            'EmpName' => ['required', 'string', 'max:255'],
            'Designation' => ['required', 'string', 'max:255'],
            'LevelName' => ['required', 'string', 'max:255'],
            'Gender' => ['required', 'in:Male,Female,Other'],
            'Email' => ['required', 'email', 'max:255'],
        ];
    }
}
