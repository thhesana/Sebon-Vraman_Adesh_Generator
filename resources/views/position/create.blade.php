@extends('layouts.app')

@section('title', 'Add Position')

@section('content')
<div class="page page-narrow">
    <x-page-header title="Add Position" subtitle="Register a new position (पद)" />

    <div class="card">
        <div class="card-header">Position Details</div>
        <form method="POST" action="{{ route('positions.store') }}">
            @csrf

            <div class="card-body">
                <x-form-field name="designationType" label="Position Name (English)" placeholder="Example: Director" maxlength="200" />
                <x-form-field name="designationTypeInNepali" label="Position Name (Nepali)" placeholder="उदाहरण: निर्देशक" :required="false"
                              maxlength="200" lang="ne" help="Printed as “पद” on the travel orders." />
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('positions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
