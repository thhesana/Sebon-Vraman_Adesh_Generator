<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFiscalYearRequest extends FormRequest
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
            'fy_startdateBS' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'fy_enddateBS' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'fy_status' => ['required', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please fill in all fields!',
            'regex' => 'BS dates must be in YYYY-MM-DD format (e.g. 2082-04-01).',
        ];
    }
}
