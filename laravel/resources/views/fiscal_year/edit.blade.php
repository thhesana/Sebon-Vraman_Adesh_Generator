@extends('layouts.app')

@section('title', 'Edit Fiscal Year')

@section('content')
<div class="page page-narrow">
    <x-page-header title="Edit Fiscal Year" subtitle="Update the fiscal year" />

    <div class="card">
        <div class="card-header">Fiscal Year Details</div>
        <form method="POST" action="{{ route('fiscal_years.update', $row) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                <x-form-field name="fy" id="fy" label="Fiscal Year" :value="$row->fy" />
                <x-form-field name="fy_startdate" id="fy_startdate" type="date" label="Start Date"
                              :value="\Carbon\Carbon::parse($row->fy_startdate)->format('Y-m-d')" />
                <x-form-field name="fy_enddate" id="fy_enddate" type="date" label="End Date"
                              :value="\Carbon\Carbon::parse($row->fy_enddate)->format('Y-m-d')" />
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
