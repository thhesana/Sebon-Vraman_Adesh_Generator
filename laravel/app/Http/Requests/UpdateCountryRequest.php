<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'Country_name' => ['required', 'string', 'max:255'],
            'extra33percent_country' => ['required', 'in:0,1'],
        ];
    }
}
