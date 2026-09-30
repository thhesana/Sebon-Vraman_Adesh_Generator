@extends('layouts.app')

@section('title', 'District List')

@push('styles')
<style>
    body { background-color: #f5f7fa; }
    .district-box { width: 75%; margin: 30px auto; background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    .district-box .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .district-box .header-row h2 { margin: 0; color: #004080; }
    .district-box .add-btn { background-color: #004080; color: #ffffff; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-size: 14px; }
    .district-box .add-btn:hover { background-color: #003060; }
    .district-box .search-bar { margin-bottom: 10px; display: flex; justify-content: flex-start; gap: 6px; }
    .district-box #searchBox { width: 250px; padding: 8px; }
    .district-box table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .district-box th, .district-box td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    .district-box th { background-color: #004080; color: white; }
    .district-box tr:nth-child(even) { background-color: #f2f2f2; }
    .district-box .pager { margin-top: 15px; text-align: center; }
    .district-box .pager a, .district-box .pager span { padding: 6px 12px; margin: 2px; text-decoration: none; border: 1px solid #004080; color: #004080; border-radius: 3px; }
    .district-box .pager .active { background-color: #004080; color: white; }
</style>
@endpush

@section('content')
<div class="district-box">

    <div class="header-row">
        <h2>District List</h2>
        <a href="{{ url('/add_district.php') }}" class="add-btn">+ Add New District</a>
    </div>

    <form method="GET" action="{{ url('/DistrictList.php') }}" class="search-bar">
        <input type="text" name="search" id="searchBox"
               placeholder="Search district..."
               value="{{ $search }}">
        <input type="submit" value="Search">
    </form>

    <table>
        <tr>
            <th>SN</th>
            <th>District Name (English)</th>
            <th>District Name (Nepali)</th>
        </tr>

        @foreach ($districts as $row)
            <tr>
                <td>{{ $offset + $loop->iteration }}</td>
                <td>{{ $row->District_name }}</td>
                <td>{{ $row->District_name_nepali }}</td>
            </tr>
        @endforeach

        @if ($totalRows == 0)
            <tr>
                <td colspan="3" style="text-align:center;">No records found.</td>
            </tr>
        @endif
    </table>

    <div class="pager">
        @if ($page > 1)
            <a href="?page={{ $page - 1 }}&search={{ urlencode($search) }}">Prev</a>
        @endif

        @for ($i = 1; $i <= $totalPages; $i++)
            @if ($i == $page)
                <span class="active">{{ $i }}</span>
            @else
                <a href="?page={{ $i }}&search={{ urlencode($search) }}">{{ $i }}</a>
            @endif
        @endfor

        @if ($page < $totalPages)
            <a href="?page={{ $page + 1 }}&search={{ urlencode($search) }}">Next</a>
        @endif
    </div>

</div>
@endsection
