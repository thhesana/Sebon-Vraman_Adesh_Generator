@extends('layouts.app')

@section('title', 'Fiscal Year Master')

@section('content')
<div class="page">
    <x-page-header title="Fiscal Year Master" subtitle="Fiscal years and their status">
        <a href="{{ route('fiscal_years.create') }}" class="btn btn-primary">Add Fiscal Year</a>
    </x-page-header>

    <x-data-table :headings="['SN', 'Fiscal Year', 'Start Date', 'End Date', 'Status', 'Actions']">
        @forelse ($fiscalYears as $row)
            <tr>
                <td class="num">{{ $loop->iteration }}</td>
                <td>{{ $row->fy }}</td>
                <td>{{ $row->fy_startdate ? \Carbon\Carbon::parse($row->fy_startdate)->format('Y-m-d') : '' }}</td>
                <td>{{ $row->fy_enddate ? \Carbon\Carbon::parse($row->fy_enddate)->format('Y-m-d') : '' }}</td>
                <td>
                    @if (strtoupper((string) $row->fy_status) === 'ACTIVE')
                        <span class="badge badge-soft-success">ACTIVE</span>
                    @else
                        <span class="badge badge-soft-muted">INACTIVE</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('fiscal_years.edit', $row) }}" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>
        @empty
            <x-empty-row colspan="6" message="No records found." />
        @endforelse
    </x-data-table>
</div>
@endsection
