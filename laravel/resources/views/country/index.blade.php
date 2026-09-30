@extends('layouts.app')

@section('title', 'Country List')

@section('content')
<div class="page">
    <x-page-header title="Country List" subtitle="Countries and their Extra 33% eligibility" />

    <x-search-form :action="route('countries.index')" :search="$search" placeholder="Search Country..." />

    <x-data-table :headings="['Country ID', 'Country Name', 'Extra 33%?', 'Actions']">
        @forelse ($countries as $row)
            <tr>
                <td class="num">{{ $row->Country_id }}</td>
                <td>{{ $row->Country_name }}</td>
                <td>
                    @if ($row->extra33percent_country == 1)
                        <span class="badge badge-soft-success">Yes</span>
                    @else
                        <span class="badge badge-soft-muted">No</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('countries.edit', $row) }}" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>
        @empty
            <x-empty-row colspan="4" message="No countries found" />
        @endforelse
    </x-data-table>

    <x-pagination :paginator="$countries" />
</div>
@endsection
