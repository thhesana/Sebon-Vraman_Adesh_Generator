<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDomesticTadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'form_date' => ['required', 'date'],
            'district_id' => ['required'],
            'travel_objective' => ['required', 'string'],
            'travelDateStart' => ['required', 'date'],
            'travelDateEnd' => ['required', 'date', 'after_or_equal:travelDateStart'],
            'tada_type_id' => ['required', 'integer', 'min:1'],
            'tadaverifier_id' => ['required', 'integer', 'min:1'],
            'is_twenty_percent_extra' => ['nullable'],
            'employee_data' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_data.required' => '⚠️ Please add at least one employee!',
            'tada_type_id.required' => '⚠️ Please select a TADA Type!',
            'tada_type_id.integer' => '⚠️ Please select a TADA Type!',
            'tada_type_id.min' => '⚠️ Please select a TADA Type!',
            'tadaverifier_id.required' => '⚠️ Please select a TADA Verifier!',
            'tadaverifier_id.integer' => '⚠️ Please select a TADA Verifier!',
            'tadaverifier_id.min' => '⚠️ Please select a TADA Verifier!',
            'travelDateEnd.after_or_equal' => '❌ End date must be same or after start date.',
        ];
    }
}
