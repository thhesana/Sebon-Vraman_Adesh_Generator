@extends('layouts.app')

@section('title', 'Country List')

@section('content')
<div class="container mt-4">
    <h3 class="text-center mb-4">Country List</h3>

    <!-- SEARCH BOX -->
    <form method="get" action="{{ url('/countrylist.php') }}" class="mb-3 text-center">
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
            @foreach ($countries as $row)
                <tr>
                    <td>{{ $row->Country_id }}</td>
                    <td>{{ $row->Country_name }}</td>
                    <td>{{ $row->extra33percent_country == 1 ? 'Yes' : 'No' }}</td>
                    <td>
                        <a href="{{ url('/edit_country.php') }}?id={{ $row->Country_id }}" class="btn btn-sm btn-primary">Edit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- PAGINATION -->
    <nav>
        <ul class="pagination justify-content-center">
            <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="?page={{ $page - 1 }}&search={{ urlencode($search) }}">Previous</a>
            </li>

            @for ($i = 1; $i <= $totalPages; $i++)
                <li class="page-item {{ $i == $page ? 'active' : '' }}">
                    <a class="page-link" href="?page={{ $i }}&search={{ urlencode($search) }}">{{ $i }}</a>
                </li>
            @endfor

            <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                <a class="page-link" href="?page={{ $page + 1 }}&search={{ urlencode($search) }}">Next</a>
            </li>
        </ul>
    </nav>
</div>
@endsection
