{{-- Add / edit form of an old international travel order. Expects $record plus the controller's formLookups().
     Everything is typed in by the user exactly as on the old paper order: nothing is fetched or calculated. --}}
@php
    $val = fn (string $field, $default = null) => old($field, $record->{$field} ?? $default);
    // BS dates are stored as 'YYYY-MM-DD' strings.
    $bsVal = fn (string $field) => old($field, $record->{$field});
    // Plain numbers in inputs (no thousands separators, no float noise); 0 dress allowance shows as blank.
    $num = fn (string $field) => old($field, ($record->{$field} === null || ($field === 'dress_allowance_npr' && (float) $record->{$field} == 0))
        ? '' : rtrim(rtrim(number_format((float) $record->{$field}, 4, '.', ''), '0'), '.'));
@endphp

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="{{ asset('calender/nepali.datepicker.v5.0.6.min.css') }}" rel="stylesheet">
<link href="{{ \App\Support\Asset::v('css/international/form.css') }}" rel="stylesheet">
<link href="{{ \App\Support\Asset::v('css/old_international/form.css') }}" rel="stylesheet">
@endpush

{{-- 1. Order --}}
<div class="card mb-3">
    <div class="card-header">Order details</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="formDate">Form Date (मिति) – BS <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" class="form-control bs-date @error('form_dateBS') is-invalid @enderror" name="form_dateBS" id="formDate" required autocomplete="off"
                       value="{{ $bsVal('form_dateBS') }}" placeholder="YYYY-MM-DD (e.g. 2083-07-20)">
                <div class="form-text">The fiscal year is set automatically from this date.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="chalaniId">Chalani No. (संख्या)</label>
                <input type="number" min="1" step="1" class="form-control @error('Chalani_id') is-invalid @enderror" name="Chalani_id" id="chalaniId" value="{{ $val('Chalani_id') }}" placeholder="As on the old order">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tadaVerifierSelect">TADA Verifier</label>
                <select name="tadaverifier_id" id="tadaVerifierSelect" class="form-select select2-single">
                    <option value="">-- Select TADA Verifier --</option>
                    @foreach ($verifiers as $id => $post)
                        <option value="{{ $id }}" @selected((string) $val('tadaverifier_id') === (string) $id)>{{ $post }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- 2. Employee and travel --}}
<div class="card mb-3">
    <div class="card-header">Employee and travel</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="employeeSelect">Employee <span class="text-danger" aria-hidden="true">*</span></label>
                <select name="EmpPersonalCode" id="employeeSelect" class="form-select select2-single @error('EmpPersonalCode') is-invalid @enderror" required>
                    <option value="">-- Search and Select Employee --</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->EmpPersonalCode }}" @selected((string) $val('EmpPersonalCode') === (string) $emp->EmpPersonalCode)>
                            {{ $emp->EmpName }} ({{ $emp->EmpPersonalCode }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="designationSelect">Position (पद) <span class="text-danger" aria-hidden="true">*</span></label>
                <select name="Designation_id" id="designationSelect" class="form-select select2-single @error('Designation_id') is-invalid @enderror" required>
                    <option value="">-- Select Position --</option>
                    @foreach ($designations as $designation)
                        <option value="{{ $designation->id }}" @selected((string) $val('Designation_id') === (string) $designation->id)>
                            {{ $designation->designationType }}@if (filled($designation->designationTypeInNepali)) – {{ $designation->designationTypeInNepali }}@endif
                        </option>
                    @endforeach
                </select>
                <div class="form-text">The position as written on the old order.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="countrySelect">Country <span class="text-danger" aria-hidden="true">*</span></label>
                <select name="Country_id" id="countrySelect" class="form-select select2-single @error('Country_id') is-invalid @enderror" required>
                    <option value="">-- Select Country --</option>
                    @foreach ($countries as $id => $name)
                        <option value="{{ $id }}" @selected((string) $val('Country_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="citySelect">City</label>
                <select name="City_id" id="citySelect" class="form-select select2-single">
                    <option value="">-- Select City --</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->City_id }}" data-country="{{ $city->Country_id }}"
                                @selected((string) $val('City_id') === (string) $city->City_id)>{{ $city->City_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="travelObjective">Travel Objective (भ्रमणको उद्देश्य) <span class="text-danger" aria-hidden="true">*</span></label>
                <textarea name="travel_objective" id="travelObjective" class="form-control @error('travel_objective') is-invalid @enderror" rows="3" required maxlength="1000"
                          placeholder="Purpose of the travel, as on the old order...">{{ $val('travel_objective') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="startDate">Travel Start Date – BS <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" class="form-control bs-date @error('travelDateStartBS') is-invalid @enderror" name="travelDateStartBS" id="startDate" required autocomplete="off"
                       value="{{ $bsVal('travelDateStartBS') }}" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="endDate">Travel End Date – BS <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" class="form-control bs-date @error('travelDateEndBS') is-invalid @enderror" name="travelDateEndBS" id="endDate" required autocomplete="off"
                       value="{{ $bsVal('travelDateEndBS') }}" placeholder="YYYY-MM-DD">
            </div>
        </div>
    </div>
</div>

{{-- 3. Allowance table, field for field as printed on the order --}}
<div class="card mb-3">
    <div class="card-header">Allowance (as on the old order)</div>
    <div class="card-body">
        <p class="form-text mt-0 mb-3">Type every value exactly as written on the paper order.</p>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="dailyRate">दर – Rate (USD/day) <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" inputmode="decimal" class="form-control text-end calc @error('daily_rate_usd') is-invalid @enderror" name="daily_rate_usd" id="dailyRate" required value="{{ $num('daily_rate_usd') }}" placeholder="225">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="totalDays">दिन – Days <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" inputmode="decimal" class="form-control text-end calc @error('total_days') is-invalid @enderror" name="total_days" id="totalDays" required value="{{ $num('total_days') }}" placeholder="5.5">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="totalUsd">जम्मा भत्ता – Total (USD) <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" inputmode="decimal" class="form-control text-end calc @error('total_usd') is-invalid @enderror" name="total_usd" id="totalUsd" required value="{{ $num('total_usd') }}" placeholder="1237.50">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="exchangeRate">विनिमय दर – Exchange Rate (NPR/USD) <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" inputmode="decimal" class="form-control text-end calc @error('exchange_rate') is-invalid @enderror" name="exchange_rate" id="exchangeRate" required value="{{ $num('exchange_rate') }}" placeholder="151.47">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="exchangeDateBs">विनिमय दर मिति – Rate Date (BS)</label>
                <input type="text" class="form-control bs-date @error('exchange_rate_dateBS') is-invalid @enderror" name="exchange_rate_dateBS" id="exchangeDateBs" autocomplete="off"
                       value="{{ $bsVal('exchange_rate_dateBS') }}" placeholder="YYYY-MM-DD (e.g. 2083-07-20)">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="totalNpr">जम्मा रकम रु. – Total (NPR) <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" inputmode="decimal" class="form-control text-end calc @error('total_npr') is-invalid @enderror" name="total_npr" id="totalNpr" required value="{{ $num('total_npr') }}" placeholder="187444.13">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="dressAmount">लुगा भत्ता – Dress Allowance (NPR)</label>
                <input type="text" inputmode="decimal" class="form-control text-end calc @error('dress_allowance_npr') is-invalid @enderror" name="dress_allowance_npr" id="dressAmount" value="{{ $num('dress_allowance_npr') }}" placeholder="Leave blank if not granted">
                <div class="form-text">Blank = not granted (prints “दुई वर्ष पूरा नभएको”).</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="grandTotal">जम्मा – Grand Total (NPR)</label>
                <input type="text" class="form-control text-end fw-semibold" id="grandTotal" readonly tabindex="-1" value="">
                <div class="form-text">Total NPR + dress allowance (shown for checking).</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
        <a href="{{ route('old_international.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary" id="submitBtn">{{ $submitText }}</button>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('calender/nepali.datepicker.v5.0.6.min.js') }}"></script>
<script src="{{ \App\Support\Asset::v('js/old_international/form.js') }}"></script>
@endpush
