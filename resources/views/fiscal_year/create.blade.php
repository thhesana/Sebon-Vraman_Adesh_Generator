@extends('layouts.app')

@section('title', 'Add New Fiscal Year')

@include('fiscal_year._bs-assets')

@section('content')
<div class="page page-narrow">
    <x-page-header title="Add Fiscal Year" subtitle="Register a new fiscal year" />

    <div class="card">
        <div class="card-header">Fiscal Year Details</div>
        <form action="{{ route('fiscal_years.store') }}" method="POST" data-fy-form>
            @csrf

            <div class="card-body">
                <x-form-field name="fy" id="fy" label="Fiscal Year" placeholder="Example: 2082/83" />
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-form-field name="fy_startdate" id="fy_startdate" type="date" label="Start Date (AD)" data-bs-pair="#fy_startdateBS" />
                    </div>
                    <div class="col-md-6">
                        <x-form-field name="fy_startdateBS" id="fy_startdateBS" label="Start Date (BS)" :required="false"
                                      placeholder="YYYY-MM-DD" autocomplete="off" inputClass="form-control bs-date" />
                    </div>
                    <div class="col-md-6">
                        <x-form-field name="fy_enddate" id="fy_enddate" type="date" label="End Date (AD)" data-bs-pair="#fy_enddateBS" />
                    </div>
                    <div class="col-md-6">
                        <x-form-field name="fy_enddateBS" id="fy_enddateBS" label="End Date (BS)" :required="false"
                                      placeholder="YYYY-MM-DD" autocomplete="off" inputClass="form-control bs-date" />
                    </div>
                </div>
                <p class="form-text mt-0">Pick either the AD or the BS date; the other one is filled automatically.</p>
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
