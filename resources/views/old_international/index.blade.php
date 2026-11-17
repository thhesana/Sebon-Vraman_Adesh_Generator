@extends('layouts.app')

@section('title', "Old Int'l Travel Records")

@section('content')
<div class="page old-intl-index">
    <x-page-header title="Old International Travel Records" subtitle="International travel orders issued before this system, entered manually (one record per employee).">
        <a href="{{ route('old_international.create') }}" class="btn btn-primary">Add Old Record</a>
    </x-page-header>

    <x-search-form :action="route('old_international.index')" :search="$search" placeholder="Search by employee, code, country, city, objective or chalani no..." />

    <x-data-table id="oldIntlTable"
        :headings="['Chalani / FY', 'Employee', 'Country / City', 'Travel Objective', 'Travel Date (BS)', 'Days', 'Total USD', 'Total NPR', 'Dress Allow.', 'Actions']">
        @forelse ($records as $row)
            <tr>
                <td class="text-nowrap">{{ $row->order_number }}</td>
                <td>{{ $row->employee?->EmpName ?? $row->EmpPersonalCode }}<br><small class="text-muted">{{ $row->EmpPersonalCode }}</small></td>
                <td>{{ $row->country?->Country_name }}@if ($row->city), {{ $row->city->City_name }}@endif</td>
                <td>{{ $row->travel_objective }}</td>
                <td class="text-nowrap">{{ $row->travelDateStartBS }} – {{ $row->travelDateEndBS }}</td>
                <td class="num"><x-international.amount :value="$row->total_days" /></td>
                <td class="num">$<x-international.amount :value="$row->total_usd" /></td>
                <td class="num">{{ number_format($row->grand_total, 2) }}</td>
                <td>
                    <span class="badge {{ $row->DressAllowance ? 'badge-soft-success' : 'badge-soft-muted' }}">{{ $row->DressAllowance ? 'Yes' : 'No' }}</span>
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('old_international.edit', $row) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="{{ route('old_international.print', $row) }}" class="btn btn-sm btn-secondary" target="_blank" rel="noopener">Print</a>
                    </div>
                </td>
            </tr>
        @empty
            <x-empty-row :colspan="10" message="No old travel records found" />
        @endforelse
    </x-data-table>

    <x-pagination :paginator="$records" />
</div>
@endsection
