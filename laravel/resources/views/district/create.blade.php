@extends('layouts.app')

@section('title', 'Add New District')

@section('content')
<div class="page page-narrow">
    <x-page-header title="Add District" subtitle="Register a new district" />

    <div class="card">
        <div class="card-header">District Details</div>
        <form method="POST" action="{{ route('districts.store') }}">
            @csrf

            <div class="card-body">
                <x-form-field name="district_name" label="District Name (English)" />

                <x-form-field name="district_name_nepali" label="District Name (Nepali)" inputClass="form-control nepali" lang="ne" />
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('districts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
