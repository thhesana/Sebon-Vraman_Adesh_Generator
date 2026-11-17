@extends('layouts.app')

@section('title', 'District List')

@section('content')
<div class="page">
    <x-page-header title="District List" subtitle="Districts with English and Nepali names">
        <a href="{{ route('districts.create') }}" class="btn btn-primary">Add District</a>
    </x-page-header>

    <x-search-form :action="route('districts.index')" :search="$search" placeholder="Search district..." />

    <x-data-table :headings="['SN', 'District Name (English)', 'District Name (Nepali)']">
        @forelse ($districts as $row)
            <tr>
                <td class="num">{{ $districts->firstItem() + $loop->index }}</td>
                <td>{{ $row->District_name }}</td>
                <td><span class="nepali" lang="ne">{{ $row->District_name_nepali }}</span></td>
            </tr>
        @empty
            <x-empty-row colspan="3" message="No records found." />
        @endforelse
    </x-data-table>

    <x-pagination :paginator="$districts" />
</div>
@endsection
