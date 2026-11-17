@extends('layouts.app')

@section('title', 'Fiscal Year Master')

@include('fiscal_year._bs-assets')

@section('content')
@php
    $ad = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : '';
@endphp
<div class="page">
    <x-page-header title="Fiscal Year Master" subtitle="Fiscal years and their status">
        <a href="{{ route('fiscal_years.create') }}" class="btn btn-primary">Add Fiscal Year</a>
    </x-page-header>

    <x-data-table :headings="['SN', 'Fiscal Year', 'Start Date', 'End Date', 'Status', 'Actions']">
        @forelse ($fiscalYears as $row)
            <tr data-fy-id="{{ $row->fiscal_year_master_id }}"
                data-ad-start="{{ $ad($row->fy_startdate) }}" data-ad-end="{{ $ad($row->fy_enddate) }}"
                @if (blank($row->fy_startdateBS) || blank($row->fy_enddateBS)) data-fy-missing-bs @endif>
                <td class="num">{{ $loop->iteration }}</td>
                <td>{{ $row->fy }}</td>
                @foreach (['start' => [$row->fy_startdateBS, $row->fy_startdate], 'end' => [$row->fy_enddateBS, $row->fy_enddate]] as $cell => [$bs, $adDate])
                    <td>
                        <span data-bs-cell="{{ $cell }}">{{ $bs ?: '-' }}</span> <small class="text-muted">(BS)</small><br>
                        <small class="text-muted">{{ $ad($adDate) }} (AD)</small>
                    </td>
                @endforeach
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

@push('scripts')
<script>
window.APP = window.APP || {};
window.APP.fyBsSyncUrl = @json(route('fiscal_years.bs_dates'));
</script>
@endpush
