@extends('layouts.app')

@section('title', 'Edit International TADA Batch')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="{{ asset('css/international/form.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="page intl-form">
    <x-page-header title="Edit International TADA Batch" subtitle="Batch ID: {{ $batchId }}">
        <a href="{{ route('international.index') }}" class="btn btn-secondary">Back to Records</a>
    </x-page-header>

    <form method="POST" action="{{ route('international.update', ['batch' => $batchId]) }}" id="tadaForm">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header">Batch details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="formDate">Form Date <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="date" class="form-control" name="form_date" id="formDate" required
                               value="{{ old('form_date', $batchData->form_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 d-none d-md-block"><!-- alignment spacer --></div>
                    <div class="col-md-6">
                        <label class="form-label" for="countrySelect">Country <span class="text-danger" aria-hidden="true">*</span></label>
                        <select name="country_id" id="countrySelect" class="form-select select2-single" required>
                            <option value="">-- Select Country --</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->Country_id }}"
                                        @selected((string) old('country_id', $batchData->Country_id) === (string) $country->Country_id)>
                                    {{ $country->Country_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="citySelect">City <span class="text-danger" aria-hidden="true">*</span>
                            <span class="text-muted intl-loading" id="cityLoading">Loading...</span>
                        </label>
                        <select name="city_id" id="citySelect" class="form-select select2-single" required>
                            <option value="">-- Select City --</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->City_id }}"
                                        data-country="{{ $city->Country_id }}"
                                        @selected((string) old('city_id', $batchData->City_id) === (string) $city->City_id)>
                                    {{ $city->City_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="travelObjective">Travel Objective <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea name="travel_objective" id="travelObjective" class="form-control" rows="3" required
                                  placeholder="Enter the purpose of international travel...">{{ old('travel_objective', $batchData->travel_objective) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="startDate">Travel Start Date <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="date" class="form-control" name="travelDateStart" id="startDate" required
                               value="{{ old('travelDateStart', $batchData->travelDateStart?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="endDate">Travel End Date <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="date" class="form-control" name="travelDateEnd" id="endDate" required
                               value="{{ old('travelDateEnd', $batchData->travelDateEnd?->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>
        </div>

        <x-international.employee-picker :employees="$allEmployees" label="Employees" />

        <input type="hidden" name="employee_data" id="employeeData" value="">

        <div class="card">
            <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('international.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitBtn">Update Batch {{ $batchId }}</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@use('Illuminate\Support\Js')
<script>
window.APP = {{ Js::from([
    'citiesUrl' => route('countries.cities', ['country' => '__ID__']),
    'oldCityId' => '',
    'oldEmployeeData' => '',
    'initialEmployees' => $batchEmployees,
    'cityPlaceholder' => 'Select a city',
    'emptyText' => 'No employees added yet.',
    'submittingText' => 'Updating Batch...',
    'loadCitiesOnInit' => false,
]) }};
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/international/form.js') }}"></script>
@endpush
