<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'district_name' => [
                'required', 'string', 'max:255',
                Rule::unique('DistrictMaster', 'District_name'),
            ],
            'district_name_nepali' => [
                'required', 'string', 'max:255',
                Rule::unique('DistrictMaster', 'District_name_nepali'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'district_name.required' => 'District name (English) is required.',
            'district_name_nepali.required' => 'District name (Nepali) is required.',
            'district_name.unique' => 'District already exists.',
            'district_name_nepali.unique' => 'District already exists.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'district_name' => trim((string) $this->input('district_name')),
            'district_name_nepali' => trim((string) $this->input('district_name_nepali')),
        ]);
    }
}
