@extends('layouts.app')

@section('title', 'Add New City')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<h2 class="text-center mt-4">Add New City</h2>
<div class="container mt-3">

    <form method="POST" action="{{ route('cities.store') }}">
        @csrf

        <div class="mb-3">
            <label>Select Country:</label>
            <select name="country_id" id="country_id" class="form-control" required>
                <option value="">-- Select Country --</option>
                @foreach ($countries as $c)
                    <option value="{{ $c->Country_id }}" {{ old('country_id') == $c->Country_id ? 'selected' : '' }}>{{ $c->Country_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>City Name:</label>
            <input type="text" name="city_name" id="city_name" class="form-control" value="{{ old('city_name') }}" {{ old('country_id') ? '' : 'disabled' }} required>
        </div>

        <button class="btn btn-primary">Save City</button>
        <a href="{{ route('cities.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        // apply Select2 on country select
        $('#country_id').select2({
            placeholder: "-- Select Country --",
            allowClear: true
        });

        // enable city field only after selecting a country
        $('#country_id').on('change', function () {
            if ($(this).val()) {
                $('#city_name').prop('disabled', false);
            } else {
                $('#city_name').prop('disabled', true);
            }
        });
    });
</script>
@endpush
