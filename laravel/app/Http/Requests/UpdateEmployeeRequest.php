<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'EmpNameInNepali' => ['required', 'string', 'max:255'],
            'EmpName' => ['required', 'string', 'max:255'],
            'Designation' => ['required', 'string', 'max:255'],
            'LevelName' => ['required', 'string', 'max:255'],
            'Gender' => ['required', 'in:Male,Female,Other'],
            'Email' => ['required', 'email', 'max:255'],
        ];
    }
}
