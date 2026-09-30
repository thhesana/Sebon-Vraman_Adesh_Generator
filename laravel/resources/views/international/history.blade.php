@extends('layouts.app')

@section('title', 'International TADA Employee Status')

@push('styles')
<style>
    .intl-history { font-family: Arial; padding: 0 12px; }
    .intl-history .search-box { margin-top: 20px; margin-bottom: 15px; }
    .intl-history .search-input { padding: 8px; width: 250px; border: 1px solid gray; border-radius: 5px; }
    .intl-history .search-btn { padding: 8px 15px; background: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; }
    .intl-history table { border-collapse: collapse; width: 100%; margin-top: 10px; font-family: Arial; }
    .intl-history th, .intl-history td { border: 1px solid #444; padding: 8px; text-align: left; }
    .intl-history th { background: #003366; color: white; }
    .intl-history .green-box { background: #c5f7c5; color: green; font-weight: bold; padding: 5px; border-radius: 5px; text-align: center; }
    .intl-history .red-box { background: #ffc5c5; color: red; font-weight: bold; padding: 5px; border-radius: 5px; text-align: center; }
</style>
@endpush

@section('content')
<div class="intl-history">
<h2><center>International Travel  Employee Status by Lastest Visit</center></h2>
<center>
<form method="GET" action="{{ route('international.tada.history') }}" class="search-box">
    <input type="text" name="search" class="search-input"
           placeholder="Search by Emp Code, Name, Country, Batch..."
           value="{{ $search }}">
    <button type="submit" class="search-btn">Search</button>
</form></center>

<table>
    <tr>
        <th>Employee Name</th>
        <th>Country</th>
        <th>Travel Start</th>
        <th>Travel End</th>
        <th>Days Since Travel Start</th>
    </tr>

@foreach ($rows as $row)
    @php $daysDiff = $row->DaysDifference; @endphp
    <tr>
        <td>{{ $row->EmpName }}</td>
        <td>{{ $row->Country_name ?? 'N/A' }}</td>
        <td>{{ \App\Support\Fmt::date($row->travelDateStart) }}</td>
        <td>{{ \App\Support\Fmt::date($row->travelDateEnd) }}</td>
        <td>
            @if ($daysDiff !== null)
                <div class="{{ $daysDiff > 700 ? 'green-box' : 'red-box' }}">{{ $daysDiff }} days</div>
            @endif
        </td>
    </tr>
@endforeach
</table>
</div>
@endsection
