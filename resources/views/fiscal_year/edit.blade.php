@extends('layouts.app')

@section('title', 'Edit Fiscal Year')

@include('fiscal_year._bs-assets')

@section('content')
<div class="page page-narrow">
    <x-page-header title="Edit Fiscal Year" subtitle="Update the fiscal year" />

    <div class="card">
        <div class="card-header">Fiscal Year Details</div>
        <form method="POST" action="{{ route('fiscal_years.update', $row) }}" data-fy-form>
            @csrf
            @method('PUT')

            <div class="card-body">
                <x-form-field name="fy" id="fy" label="Fiscal Year" :value="$row->fy" />
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-form-field name="fy_startdate" id="fy_startdate" type="date" label="Start Date (AD)" data-bs-pair="#fy_startdateBS"
                                      :value="\Carbon\Carbon::parse($row->fy_startdate)->format('Y-m-d')" />
                    </div>
                    <div class="col-md-6">
                        <x-form-field name="fy_startdateBS" id="fy_startdateBS" label="Start Date (BS)" :required="false" :value="$row->fy_startdateBS"
                                      placeholder="YYYY-MM-DD" autocomplete="off" inputClass="form-control bs-date" />
                    </div>
                    <div class="col-md-6">
                        <x-form-field name="fy_enddate" id="fy_enddate" type="date" label="End Date (AD)" data-bs-pair="#fy_enddateBS"
                                      :value="\Carbon\Carbon::parse($row->fy_enddate)->format('Y-m-d')" />
                    </div>
                    <div class="col-md-6">
                        <x-form-field name="fy_enddateBS" id="fy_enddateBS" label="End Date (BS)" :required="false" :value="$row->fy_enddateBS"
                                      placeholder="YYYY-MM-DD" autocomplete="off" inputClass="form-control bs-date" />
                    </div>
                </div>
                <p class="form-text mt-0">Pick either the AD or the BS date; the other one is filled automatically.</p>
                <x-select-field name="fy_status" id="fy_status" label="Status"
                                :options="['ACTIVE' => 'ACTIVE', 'INACTIVE' => 'INACTIVE']"
                                :selected="$row->fy_status" />
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('fiscal_years.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
