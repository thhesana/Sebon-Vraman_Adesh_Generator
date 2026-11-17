@extends('layouts.app')

@section('title', 'City List')

@section('content')
<div class="page">
    <x-page-header title="City List" subtitle="Cities and the country they belong to">
        <a href="{{ route('cities.create') }}" class="btn btn-primary">Add City</a>
    </x-page-header>

    <x-search-form :action="route('cities.index')" :search="$search" placeholder="Search city, country..." />

    @if ($search !== '')
        <p class="text-muted">Showing results for: <strong>{{ $search }}</strong> ({{ $cities->total() }} found)</p>
    @endif

    <x-data-table id="cityTable" :headings="['S.N.', 'City Name', 'Country', 'Action']">
        @forelse ($cities as $row)
            <tr>
                <td class="num">{{ $cities->firstItem() + $loop->index }}</td>
                <td>{{ $row->City_name }}</td>
                <td>{{ $row->country?->Country_name }}</td>
                <td>
                    <a href="{{ route('cities.edit', $row) }}" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>
        @empty
            <x-empty-row colspan="4" message="No cities found" />
        @endforelse
    </x-data-table>

    <x-pagination :paginator="$cities" />
</div>
@endsection
