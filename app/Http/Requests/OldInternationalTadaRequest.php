<?php

namespace App\Http\Requests;

use App\Models\FiscalYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Add / edit form of a manually entered old international travel order.
 * Everything is typed in by the user; nothing is pre-filled or calculated. Every date is a BS
 * 'YYYY-MM-DD' string from the Nepali date picker. The fiscal year is not entered: it is
 * looked up from the form date (BS) and added to the validated data.
 */
class OldInternationalTadaRequest extends FormRequest
{
    /** BS 'YYYY-MM-DD': month 01-12, day 01-32 (BS months have up to 32 days). */
    public const BS_DATE = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[0-2])$/';

    private const BS_FIELDS = ['form_dateBS', 'travelDateStartBS', 'travelDateEndBS', 'exchange_rate_dateBS'];

    private ?FiscalYear $fiscalYear = null;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $bs = ['regex:'.self::BS_DATE];

        return [
            'Chalani_id' => ['nullable', 'integer', 'min:1'],
            'form_dateBS' => ['required', ...$bs],
            'EmpPersonalCode' => ['required', 'string', 'exists:Employee_Information,EmpPersonalCode'],
            'Country_id' => ['required', 'integer', 'exists:CountryMaster,Country_id'],
            'City_id' => ['nullable', 'integer', 'exists:CityMaster,City_id'],
            'travel_objective' => ['required', 'string', 'max:1000'],
            'travelDateStartBS' => ['required', ...$bs],
            'travelDateEndBS' => ['required', ...$bs],
            'Designation_id' => ['required', 'integer', 'exists:DesignationTypeMaster,id'],
            'tadaverifier_id' => ['nullable', 'integer', 'exists:tadaverifier,tadaverifier_id'],
            'daily_rate_usd' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'total_days' => ['required', 'numeric', 'min:0', 'max:9999'],
            'total_usd' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'exchange_rate' => ['required', 'numeric', 'min:0', 'max:99999'],
            'exchange_rate_dateBS' => ['nullable', ...$bs],
            'total_npr' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'dress_allowance_npr' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }

    /** End >= start, and a fiscal year must cover the form date. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->input('travelDateEndBS') < $this->input('travelDateStartBS')) {
                    $validator->errors()->add('travelDateEndBS', 'End date cannot be before start date!');
                }

                $this->fiscalYear = FiscalYear::forBsDate($this->input('form_dateBS'));
                if (! $this->fiscalYear) {
                    $validator->errors()->add('form_dateBS', 'No fiscal year in the Fiscal Year master covers the form date '.$this->input('form_dateBS').' (BS). Add that fiscal year (with its BS dates) first.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $clean = [];

        // Amounts may be typed with thousands separators ("187,444.13").
        foreach (['daily_rate_usd', 'total_days', 'total_usd', 'exchange_rate', 'total_npr', 'dress_allowance_npr'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = str_replace([',', ' '], '', $this->input($field));
            }
        }

        // BS dates: accept 2083/7/20 or 2083-07-20, store zero-padded 2083-07-20.
        foreach (self::BS_FIELDS as $field) {
            $clean[$field] = self::normaliseBs($this->input($field));
        }

        $this->merge($clean);
    }

    public static function normaliseBs(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $parts = preg_split('/[-\/.]/', strtr($value, ['०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4', '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9']));
        if (count($parts) !== 3 || ! ctype_digit(implode('', $parts))) {
            return $value; // left as typed so the regex rule reports it
        }

        return sprintf('%04d-%02d-%02d', ...array_map('intval', $parts));
    }

    /** Validated columns ready for the model, incl. the fiscal year found from the form date. */
    public function record(): array
    {
        $data = $this->validated();
        $data['travel_objective'] = trim($data['travel_objective']);
        // Dress allowance is just the typed amount; the flag follows it (blank / 0 = not granted).
        $data['dress_allowance_npr'] = (float) ($data['dress_allowance_npr'] ?? 0);
        $data['DressAllowance'] = $data['dress_allowance_npr'] > 0;
        $data['fiscal_year_master_id'] = $this->fiscalYear->getKey();

        return $data;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'regex' => 'The :attribute must be a BS date in YYYY-MM-DD format (e.g. 2083-07-20).',
            'EmpPersonalCode.required' => 'Please select an employee!',
            'EmpPersonalCode.exists' => 'Selected employee was not found.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'form_dateBS' => 'form date (BS)',
            'travelDateStartBS' => 'travel start date (BS)',
            'travelDateEndBS' => 'travel end date (BS)',
            'exchange_rate_dateBS' => 'exchange rate date (BS)',
            'Chalani_id' => 'chalani number',
            'Country_id' => 'country',
            'City_id' => 'city',
            'Designation_id' => 'position',
            'tadaverifier_id' => 'TADA verifier',
            'daily_rate_usd' => 'rate (USD)',
            'total_days' => 'days',
            'total_usd' => 'total allowance (USD)',
            'exchange_rate' => 'exchange rate',
            'total_npr' => 'total amount (NPR)',
            'dress_allowance_npr' => 'dress allowance',
        ];
    }
}
