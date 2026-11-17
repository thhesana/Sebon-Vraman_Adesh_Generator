@extends('layouts.app')

@section('title', 'Edit Country')

@section('content')
<div class="page page-narrow">
    <x-page-header title="Edit Country" subtitle="Update the country details" />

    <div class="card">
        <div class="card-header">Country Details</div>
        <form action="{{ route('countries.update', $country) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                <x-form-field name="Country_name" label="Country Name" :value="$country->Country_name" />

                <x-select-field name="extra33percent_country" label="Extra 33%?"
                                :options="[1 => 'Yes', 0 => 'No']" :selected="$country->extra33percent_country" />
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('countries.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
