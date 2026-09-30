@extends('layouts.app')

@section('title', 'Add International TADA Batch')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="{{ asset('css/international/form.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="page intl-form">
    <x-page-header title="Add International TADA Batch" subtitle="Next Batch ID: {{ $nextBatch }} · Next Chalani No.: {{ $nextChalani }}">
        <a href="{{ route('international.index') }}" class="btn btn-secondary">Back to Records</a>
    </x-page-header>

    <form method="POST" action="{{ route('international.store') }}" id="tadaForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header">Batch details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="formDate">Form Date <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="date" class="form-control" name="form_date" id="formDate" required value="{{ old('form_date', now()->toDateString()) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tadaVerifierSelect">TADA Verifier <span class="text-danger" aria-hidden="true">*</span></label>
                        <select name="tadaverifier_id" id="tadaVerifierSelect" class="form-select select2-single" required>
                            <option value="">-- Select TADA Verifier --</option>
                            @foreach ($verifiers as $verifier)
                                <option value="{{ (int) $verifier->tadaverifier_id }}" @selected((string) old('tadaverifier_id') === (string) $verifier->tadaverifier_id)>
                                    {{ $verifier->tadaverifierPost }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="countrySelect">Country <span class="text-danger" aria-hidden="true">*</span></label>
                        <select name="country_id" id="countrySelect" class="form-select select2-single" required>
                            <option value="">-- Select Country --</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->Country_id }}" @selected((string) old('country_id') === (string) $country->Country_id)>
                                    {{ $country->Country_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="citySelect">City <span class="text-danger" aria-hidden="true">*</span>
                            <span class="text-muted intl-loading" id="cityLoading">Loading...</span>
                        </label>
                        <select name="city_id" id="citySelect" class="form-select select2-single" required disabled>
                            <option value="">-- Select Country First --</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="travelObjective">Travel Objective <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea name="travel_objective" id="travelObjective" class="form-control" rows="3" required placeholder="Enter the purpose of international travel...">{{ old('travel_objective') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="startDate">Travel Start Date <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="date" class="form-control" name="travelDateStart" id="startDate" required value="{{ old('travelDateStart') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="endDate">Travel End Date <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="date" class="form-control" name="travelDateEnd" id="endDate" required value="{{ old('travelDateEnd') }}">
                    </div>
                </div>
            </div>
        </div>

        <x-international.employee-picker :employees="$employees" label="Employees" />

        <input type="hidden" name="employee_data" id="employeeData" value="">

        <div class="card">
            <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('international.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Create Batch ({{ $nextBatch }} - Chalani No. {{ $nextChalani }})</button>
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
    'oldCityId' => (string) old('city_id', ''),
    'oldEmployeeData' => (string) old('employee_data', ''),
    'initialEmployees' => [],
    'cityPlaceholder' => 'Select country first',
    'emptyText' => 'No employees added yet. Select from the dropdown above.',
    'submittingText' => 'Creating Batch...',
    'loadCitiesOnInit' => true,
]) }};
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/international/form.js') }}"></script>
@endpush
