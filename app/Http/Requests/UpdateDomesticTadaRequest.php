<?php

namespace App\Http\Requests;

class UpdateDomesticTadaRequest extends StoreDomesticTadaRequest
{
    public function messages(): array
    {
        return ['travelDateEnd.after_or_equal' => '❌ End date must be the same as or after start date.']
            + parent::messages();
    }
}
