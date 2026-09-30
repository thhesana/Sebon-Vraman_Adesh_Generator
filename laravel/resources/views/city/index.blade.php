@extends('layouts.app')

@section('title', 'City List')

@section('content')
@php $sq = $hasSearch ? '&search='.urlencode($search) : ''; @endphp
<h2 class="text-center mt-4">City List</h2>
<div class="container mt-3">
    <a href="{{ url('/city_add.php') }}" class="btn btn-success mb-3 float-end">Add New City</a>

    <!-- SEARCH FORM -->
    <form method="GET" action="{{ url('/cityLIst.php') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control"
                   placeholder="Search city, country..."
                   value="{{ $search }}">
            <button class="btn btn-primary" type="submit">Search</button>
            @if ($hasSearch)
                <a href="{{ url('/cityLIst.php') }}" class="btn btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    @if ($hasSearch)
        <p class="text-muted">Showing results for: <strong>{{ $search }}</strong> ({{ $totalRows }} found)</p>
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
                    <td>{{ $offset + $loop->iteration }}</td>
                    <td style="display:none;">{{ $row->City_id }}</td>
                    <td>{{ $row->City_name }}</td>
                    <td>{{ $row->Country_name }}</td>
                    <td>
                        <a href="{{ url('/city_edit.php') }}?id={{ $row->City_id }}" class="btn btn-primary btn-sm">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No cities found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- PAGINATION LINKS -->
    @if ($totalPages > 1)
    @php
        $startPage = max(1, $page - 5);
        $endPage = min($totalPages, $page + 4);
    @endphp
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="?page={{ $page - 1 }}{{ $sq }}">Previous</a>
            </li>

            @if ($startPage > 1)
                <li class="page-item">
                    <a class="page-link" href="?page=1{{ $sq }}">1</a>
                </li>
                @if ($startPage > 2)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
            @endif

            @for ($i = $startPage; $i <= $endPage; $i++)
                <li class="page-item {{ $i == $page ? 'active' : '' }}">
                    <a class="page-link" href="?page={{ $i }}{{ $sq }}">{{ $i }}</a>
                </li>
            @endfor

            @if ($endPage < $totalPages)
                @if ($endPage < $totalPages - 1)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
                <li class="page-item">
                    <a class="page-link" href="?page={{ $totalPages }}{{ $sq }}">{{ $totalPages }}</a>
                </li>
            @endif

            <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                <a class="page-link" href="?page={{ $page + 1 }}{{ $sq }}">Next</a>
            </li>
        </ul>
    </nav>
    @endif
</div>
@endsection
