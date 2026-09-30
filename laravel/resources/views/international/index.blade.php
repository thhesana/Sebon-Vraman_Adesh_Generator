@extends('layouts.app')

@section('title', 'International Travel Records')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/international/index.css') }}">
@endpush

@section('content')
@php
    $formatDate = fn ($d) => $d ? strtoupper($d->format('d-M-Y')) : '-';
@endphp
<div class="page intl-index">
    <x-page-header title="International Travel Records" subtitle="All international TADA batches, grouped by batch ID">
        <a href="{{ route('international.create') }}" class="btn btn-primary">Add New Batch</a>
    </x-page-header>

    <x-data-table id="tadaTable" class="intl-records"
        :headings="['Batch ID', 'Employee', 'Country', 'Travel Objective', 'Travel Date', 'Days', 'Total USD', 'Dress Allow.', 'Extra 33%', 'Actions']">
        @if ($records->isEmpty())
            <x-empty-row id="noResults" :colspan="10" message="No records found" />
        @else
            @php $currentBatch = null; $editedBatches = []; @endphp
            @foreach ($records as $row)
                @php
                    $batchClass = '';
                    if ($currentBatch !== $row->Batch_id) {
                        $currentBatch = $row->Batch_id;
                        $batchClass = 'batch-group';
                    }
                    $extra33 = $row->country?->extra33percent_country == 1;
                @endphp
                <tr class="{{ $batchClass }}">
                    <td>{{ $row->Batch_id }}</td>
                    <td>{{ $row->employee?->EmpName ?? $row->EmpPersonalCode }}</td>
                    <td>{{ $row->country?->Country_name }}</td>
                    <td>{{ $row->travel_objective }}</td>
                    <td class="text-nowrap">{{ $formatDate($row->travelDateStart) }} - {{ $formatDate($row->travelDateEnd) }}</td>
                    <td class="num"><x-international.amount :value="$row->days" /></td>
                    <td class="num">$<x-international.amount :value="$row->usd_amount" /></td>
                    <td>
                        <span class="badge {{ $row->DressAllowance ? 'badge-soft-success' : 'badge-soft-muted' }}">{{ $row->DressAllowance ? 'Yes' : 'No' }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $extra33 ? 'badge-soft-warning' : 'badge-soft-muted' }}">{{ $extra33 ? 'Extra 33%' : 'No Extra' }}</span>
                    </td>
                    <td>
                        @if ($row->Batch_id && ! in_array($row->Batch_id, $editedBatches))
                            @php $editedBatches[] = $row->Batch_id; @endphp
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('international.edit', ['batch' => $row->Batch_id]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="{{ route('international.print', ['batch' => $row->Batch_id]) }}" class="btn btn-sm btn-secondary" target="_blank" rel="noopener">Print</a>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endif
    </x-data-table>

    @if ($records->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $records->links() }}
        </div>
    @endif
</div>
@endsection
