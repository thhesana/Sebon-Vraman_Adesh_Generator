@extends('layouts.app')

@section('title', 'Country List')

@section('content')
<div class="container mt-4">
    <h3 class="text-center mb-4">Country List</h3>

    <!-- SEARCH BOX -->
    <form method="get" action="{{ route('countries.index') }}" class="mb-3 text-center">
        <input type="text" name="search" value="{{ $search }}"
               placeholder="Search Country..." class="form-control w-50 d-inline-block">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="bg-primary text-white">
            <tr>
                <th>Country ID</th>
                <th>Country Name</th>
                <th>Extra 33%?</th>
                <th style="width: 150px;">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($countries as $row)
                <tr>
                    <td>{{ $row->Country_id }}</td>
                    <td>{{ $row->Country_name }}</td>
                    <td>{{ $row->extra33percent_country == 1 ? 'Yes' : 'No' }}</td>
                    <td>
                        <a href="{{ route('countries.edit', $row) }}" class="btn btn-sm btn-primary">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">No countries found</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">{{ $countries->links() }}</div>
</div>
@endsection
