<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInternationalTadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'form_date' => ['required', 'date'],
            'country_id' => ['required', 'integer'],
            'city_id' => ['required', 'integer'],
            'travel_objective' => ['required', 'string'],
            'travelDateStart' => ['required', 'date'],
            'travelDateEnd' => ['required', 'date', 'after_or_equal:travelDateStart'],
            'employee_data' => ['required', 'string'],
            'tadaverifier_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'employee_data.required' => 'Please add at least one employee!',
            'tadaverifier_id.required' => 'Please select a TADA Verifier!',
            'tadaverifier_id.min' => 'Please select a TADA Verifier!',
            'tadaverifier_id.integer' => 'Please select a TADA Verifier!',
            'travelDateEnd.after_or_equal' => 'End date cannot be before start date!',
        ];
    }
}
