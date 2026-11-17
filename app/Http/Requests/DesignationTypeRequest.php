<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Add / edit a position (DesignationTypeMaster). */
class DesignationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ignoreId = $this->route('position')?->getKey();

        return [
            'designationType' => [
                'required', 'string', 'max:200',
                Rule::unique('DesignationTypeMaster', 'designationType')->ignore($ignoreId, 'id'),
            ],
            'designationTypeInNepali' => ['nullable', 'string', 'max:200'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'designationType' => trim((string) $this->input('designationType')),
            'designationTypeInNepali' => trim((string) $this->input('designationTypeInNepali')) ?: null,
        ]);
    }

    public function messages(): array
    {
        return [
            'designationType.required' => 'Position name (English) is required.',
            'designationType.unique' => 'This position already exists.',
        ];
    }

    public function attributes(): array
    {
        return [
            'designationType' => 'position name (English)',
            'designationTypeInNepali' => 'position name (Nepali)',
        ];
    }
}
