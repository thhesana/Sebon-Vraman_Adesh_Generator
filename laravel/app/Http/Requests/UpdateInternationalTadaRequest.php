<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInternationalTadaRequest extends FormRequest
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
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'employee_data.required' => 'Please add at least one employee!',
            'travelDateEnd.after_or_equal' => 'End date cannot be before start date!',
        ];
    }
}
