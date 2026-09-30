<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFiscalYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fy' => ['required', 'string', 'max:50'],
            'fy_startdate' => ['required', 'date'],
            'fy_enddate' => ['required', 'date'],
            'fy_status' => ['required', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please fill in all fields!',
        ];
    }
}
