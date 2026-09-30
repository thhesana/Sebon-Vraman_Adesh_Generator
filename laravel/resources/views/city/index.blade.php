@extends('layouts.app')

@section('title', 'City List')

@section('content')
<h2 class="text-center mt-4">City List</h2>
<div class="container mt-3">
    <a href="{{ route('cities.create') }}" class="btn btn-success mb-3 float-end">Add New City</a>

    <!-- SEARCH FORM -->
    <form method="GET" action="{{ route('cities.index') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control"
                   placeholder="Search city, country..."
                   value="{{ $search }}">
            <button class="btn btn-primary" type="submit">Search</button>
            @if ($search !== '')
                <a href="{{ route('cities.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    @if ($search !== '')
        <p class="text-muted">Showing results for: <strong>{{ $search }}</strong> ({{ $cities->total() }} found)</p>
    @endif

    <table class="table table-bordered table-striped" id="cityTable">
        <thead class="bg-primary text-white">
            <tr>
                <th>S.N.</th>
                <th style="display:none;">City ID</th>
                <th>City Name</th>
                <th>Country</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cities as $row)
                <tr>
                    <td>{{ $cities->firstItem() + $loop->index }}</td>
                    <td style="display:none;">{{ $row->City_id }}</td>
                    <td>{{ $row->City_name }}</td>
                    <td>{{ $row->country?->Country_name }}</td>
                    <td>
                        <a href="{{ route('cities.edit', $row) }}" class="btn btn-primary btn-sm">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No cities found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">{{ $cities->links() }}</div>
</div>
@endsection
