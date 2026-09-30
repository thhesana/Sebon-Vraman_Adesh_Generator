@extends('layouts.app')

@section('title', 'Edit Country')

@section('content')
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Edit Country</h4>
        </div>

        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            <form action="{{ url('/update_country.php') }}" method="POST">
                @csrf
                <input type="hidden" name="Country_id" value="{{ $country->Country_id }}">

                <div class="mb-3">
                    <label class="form-label">Country Name</label>
                    <input type="text" name="Country_name" class="form-control"
                           value="{{ old('Country_name', $country->Country_name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Extra 33%?</label>
                    <select name="extra33percent_country" class="form-control">
                        <option value="1" {{ $country->extra33percent_country == 1 ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ $country->extra33percent_country == 0 ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ url('/countrylist.php') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
