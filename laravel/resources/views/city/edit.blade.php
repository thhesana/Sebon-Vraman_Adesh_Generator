@extends('layouts.app')

@section('title', 'Edit City')

@section('content')
<h2 class="text-center mt-4">Edit City</h2>
<div class="container mt-3">
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ url('/city_edit.php') }}?id={{ $city->City_id }}">
        @csrf

        <div class="mb-3">
            <label>City Name:</label>
            <input type="text" name="city_name" class="form-control" required
                value="{{ old('city_name', $city->City_name) }}">
        </div>

        <div class="mb-3">
            <label>Select Country:</label>
            <select name="country_id" required class="form-control">
                @foreach ($countries as $c)
                    <option value="{{ $c->Country_id }}"
                        {{ $c->Country_id == old('country_id', $city->Country_id) ? 'selected' : '' }}>
                        {{ $c->Country_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-primary">Update City</button>
        <a href="{{ url('/cityLIst.php') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
