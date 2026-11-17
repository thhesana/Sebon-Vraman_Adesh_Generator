@extends('layouts.app')

@section('title', 'Edit City')

@section('content')
<div class="page page-narrow">
    <x-page-header title="Edit City" subtitle="Update the city details" />

    <div class="card">
        <div class="card-header">City Details</div>
        <form method="POST" action="{{ route('cities.update', $city) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                <x-form-field name="city_name" label="City Name" :value="$city->City_name" />

                <x-select-field name="country_id" label="Country"
                                :options="$countries->pluck('Country_name', 'Country_id')->all()"
                                :selected="$city->Country_id" />
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('cities.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
