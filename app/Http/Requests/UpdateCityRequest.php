<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_name' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', 'exists:CountryMaster,Country_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'country_id.required' => 'Please select a country',
        ];
    }
}
