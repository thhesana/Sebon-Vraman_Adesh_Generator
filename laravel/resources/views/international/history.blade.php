@extends('layouts.app')

@section('title', 'International TADA Employee Status')

@section('content')
<div class="page">
    <x-page-header title="International Travel Employee Status" subtitle="Employees by latest international visit" />

    <x-search-form :action="route('international.history')" :search="$search" placeholder="Search by Emp Code, Name, Country, Batch..." />

    <x-data-table :headings="['Employee Name', 'Country', 'Travel Start', 'Travel End', 'Days Since Travel Start']">
        @forelse ($rows as $row)
            @php $daysDiff = $row->days_since_travel; @endphp
            <tr>
                <td>{{ $row->employee->EmpName }}</td>
                <td>{{ $row->country?->Country_name ?? 'N/A' }}</td>
                <td>{{ $row->travelDateStart?->format('Y-m-d') }}</td>
                <td>{{ $row->travelDateEnd?->format('Y-m-d') }}</td>
                <td>
                    @if ($daysDiff !== null)
                        <span class="badge {{ $daysDiff > 700 ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $daysDiff }} days</span>
                    @endif
                </td>
            </tr>
        @empty
            <x-empty-row :colspan="5" message="No records found" />
        @endforelse
    </x-data-table>

    @if (method_exists($rows, 'hasPages') && $rows->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $rows->links() }}
        </div>
    @endif
</div>
@endsection
