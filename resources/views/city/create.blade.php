@extends('layouts.app')

@section('title', 'Add New City')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="page page-narrow">
    <x-page-header title="Add City" subtitle="Register a new city under a country" />

    <div class="card">
        <div class="card-header">City Details</div>
        <form method="POST" action="{{ route('cities.store') }}">
            @csrf

            <div class="card-body">
                <x-select-field name="country_id" id="country_id" label="Country"
                                :options="$countries->pluck('Country_name', 'Country_id')->all()"
                                inputClass="form-control"
                                placeholder="-- Select Country --" />

                <div class="mb-3">
                    <label for="city_name" class="form-label">City Name</label>
                    <input type="text" name="city_name" id="city_name" class="form-control" value="{{ old('city_name') }}" @disabled(! old('country_id')) required>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('cities.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/city/create.js') }}"></script>
@endpush
