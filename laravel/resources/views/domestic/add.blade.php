@extends('layouts.app')

@section('title', 'Add Domestic TADA Batch')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="{{ asset('css/domestic/batch-form.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="page">
    <x-page-header title="Add Domestic TADA Batch" subtitle="Create a new batch of domestic travel orders.">
        <span class="badge badge-soft-primary">Next Batch ID: {{ $nextBatch }}</span>
        <span class="badge badge-soft-muted">Next Chalani No: {{ $nextChalani }}</span>
    </x-page-header>

    <form method="POST" action="{{ route('domestic.store') }}" id="tadaForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header">Batch details</div>
            <div class="card-body">
                <div class="row g-3">
                    <x-domestic.field label="Form Date" for="form_date" required>
                        <input type="date" class="form-control" name="form_date" id="form_date" required value="{{ old('form_date', now()->toDateString()) }}">
                    </x-domestic.field>
                    <x-domestic.field label="District" for="districtSelect" required>
                        <x-domestic.select name="district_id" id="districtSelect" :options="$districts" :selected="old('district_id')"
                                           placeholder="-- Select District --" required />
                    </x-domestic.field>

                    <x-domestic.field label="TADA Type" for="tadaTypeSelect" required>
                        <x-domestic.select name="tada_type_id" id="tadaTypeSelect" :options="$tadaTypes" :selected="old('tada_type_id')"
                                           placeholder="-- Select TADA Type --" required />
                    </x-domestic.field>
                    <x-domestic.field label="TADA Verifier" nepali="भ्रमण आदेश दिने अधिकारी" for="tadaVerifierSelect" required>
                        <x-domestic.select name="tadaverifier_id" id="tadaVerifierSelect" :options="$verifiers" :selected="old('tadaverifier_id')"
                                           placeholder="-- Select TADA Verifier --" required />
                    </x-domestic.field>

                    <x-domestic.field label="Travel Objective" for="travel_objective" required full>
                        <textarea class="form-control" name="travel_objective" id="travel_objective" rows="3" required placeholder="Enter the purpose of domestic travel...">{{ old('travel_objective') }}</textarea>
                    </x-domestic.field>

                    <x-domestic.field label="Travel Start Date" for="startDate" required>
                        <input type="date" class="form-control" name="travelDateStart" id="startDate" required value="{{ old('travelDateStart') }}">
                    </x-domestic.field>
                    <x-domestic.field label="Travel End Date" for="endDate" required>
                        <input type="date" class="form-control" name="travelDateEnd" id="endDate" required value="{{ old('travelDateEnd') }}">
                    </x-domestic.field>

                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_twenty_percent_extra" id="twentyPercentExtra" @checked(old('is_twenty_percent_extra'))>
                            <label class="form-check-label" for="twentyPercentExtra">Add 20% Extra TADA</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Employees <span class="text-danger" aria-hidden="true">*</span></div>
            <div class="card-body">
                <x-domestic.employee-picker :employees="$employees"
                                            empty-text="No employees added yet. Select an employee above." />
            </div>
        </div>

        <input type="hidden" name="employee_data" id="employeeData" value="">

        <div class="card">
            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-href="{{ route('domestic.index') }}">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Create Batch</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
window.APP = window.APP || {};
window.APP.domesticForm = {{ Js::from([
    'withExtra' => false,
    'existing' => [],
    'oldEmployeeData' => old('employee_data'),
    'submittingText' => 'Creating batch...',
    'emptySubmitMessage' => 'Please add at least one employee.',
]) }};
</script>
<script src="{{ asset('js/domestic/batch-form.js') }}"></script>
@endpush
