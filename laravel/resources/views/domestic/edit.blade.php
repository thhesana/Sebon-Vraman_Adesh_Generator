@extends('layouts.app')

@section('title', 'Edit Domestic TADA Batch')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="{{ asset('css/domestic/batch-form.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="page">
    <x-page-header title="Edit Domestic TADA Batch" subtitle="Update the batch details and its employees.">
        <span class="badge badge-soft-primary">Batch ID: {{ $batchId }}</span>
    </x-page-header>

    <form method="POST" action="{{ route('domestic.update', $batchId) }}" id="tadaForm">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header">Batch details</div>
            <div class="card-body">
                <div class="row g-3">
                    <x-domestic.field label="Form Date" for="form_date" required>
                        <input type="date" class="form-control" name="form_date" id="form_date" required
                               value="{{ old('form_date', $batch->domestic_form_date->format('Y-m-d')) }}">
                    </x-domestic.field>
                    <x-domestic.field label="District" for="districtSelect" required>
                        <x-domestic.select name="district_id" id="districtSelect" :options="$districts"
                                           :selected="old('district_id', $batch->District_id)"
                                           placeholder="-- Select District --" select2-placeholder="Select a district" required />
                    </x-domestic.field>

                    <x-domestic.field label="TADA Type" for="tadaTypeSelect" required>
                        <x-domestic.select name="tada_type_id" id="tadaTypeSelect" :options="$tadaTypes"
                                           :selected="old('tada_type_id', $batch->TadaTypeMaster_id)"
                                           placeholder="-- Select TADA Type --" select2-placeholder="Select TADA type" required />
                    </x-domestic.field>
                    <x-domestic.field label="TADA Verifier" nepali="भ्रमण आदेश दिने अधिकारी" for="tadaVerifierSelect" required>
                        <x-domestic.select name="tadaverifier_id" id="tadaVerifierSelect" :options="$verifiers"
                                           :selected="old('tadaverifier_id', $batch->tadaverifier_id)"
                                           placeholder="-- Select TADA Verifier --" select2-placeholder="Select TADA Verifier" required />
                    </x-domestic.field>

                    <x-domestic.field label="Travel Objective" for="travel_objective" required full>
                        <textarea class="form-control" name="travel_objective" id="travel_objective" rows="3" required
                                  placeholder="Enter the purpose of domestic travel...">{{ old('travel_objective', $batch->domestic_travel_objective) }}</textarea>
                    </x-domestic.field>

                    <x-domestic.field label="Travel Start Date" for="startDate" required>
                        <input type="date" class="form-control" name="travelDateStart" id="startDate" required
                               value="{{ old('travelDateStart', $batch->domestic_travelDateStart->format('Y-m-d')) }}">
                    </x-domestic.field>
                    <x-domestic.field label="Travel End Date" for="endDate" required>
                        <input type="date" class="form-control" name="travelDateEnd" id="endDate" required
                               value="{{ old('travelDateEnd', $batch->domestic_travelDateEnd->format('Y-m-d')) }}">
                    </x-domestic.field>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Employees <span class="text-danger" aria-hidden="true">*</span></div>
            <div class="card-body">
                <x-domestic.employee-picker :employees="$employees" with-extra
                                            empty-text="No employees added yet. Select an employee above."
                                            select2-placeholder="Search and select employee..." />
            </div>
        </div>

        <input type="hidden" name="employee_data" id="employeeData" value="">

        <div class="card">
            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-href="{{ route('domestic.index') }}">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Update Batch</button>
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
    'withExtra' => true,
    'existing' => $existingEmployees,
    'oldEmployeeData' => old('employee_data'),
    'submittingText' => 'Updating batch...',
    'emptySubmitMessage' => 'Please add at least one employee before submitting.',
]) }};
</script>
<script src="{{ asset('js/domestic/batch-form.js') }}"></script>
@endpush
