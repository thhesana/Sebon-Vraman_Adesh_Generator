@extends('layouts.app')

@section('title', 'Edit Position')

@section('content')
<div class="page page-narrow">
    <x-page-header title="Edit Position" subtitle="Update the position details" />

    <div class="card">
        <div class="card-header">Position Details</div>
        <form method="POST" action="{{ route('positions.update', $position) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                <x-form-field name="designationType" label="Position Name (English)" :value="$position->designationType" maxlength="200"
                              help="Renaming also updates every employee who holds this position." />
                <x-form-field name="designationTypeInNepali" label="Position Name (Nepali)" :value="$position->designationTypeInNepali" :required="false"
                              maxlength="200" lang="ne" help="Printed as “पद” on the travel orders." />
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('positions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
