@extends('layouts.app')

@section('title', 'Add New Fiscal Year')

@section('content')
<div class="page page-narrow">
    <x-page-header title="Add Fiscal Year" subtitle="Register a new fiscal year" />

    <div class="card">
        <div class="card-header">Fiscal Year Details</div>
        <form action="{{ route('fiscal_years.store') }}" method="POST">
            @csrf

            <div class="card-body">
                <x-form-field name="fy" id="fy" label="Fiscal Year" placeholder="Example: 2082/83" />
                <x-form-field name="fy_startdate" id="fy_startdate" type="date" label="Start Date" />
                <x-form-field name="fy_enddate" id="fy_enddate" type="date" label="End Date" />
                <x-select-field name="fy_status" id="fy_status" label="Status"
                                :options="['ACTIVE' => 'ACTIVE', 'INACTIVE' => 'INACTIVE']"
                                placeholder="-- Select Status --" />
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('fiscal_years.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
