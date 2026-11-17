@extends('layouts.app')

@section('title', 'Domestic Travel Records')

@push('styles')
<link href="{{ asset('css/domestic/index.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="page">
    <x-page-header title="Domestic Travel Records" subtitle="Domestic TADA batches and their employees.">
        <a href="{{ route('domestic.create') }}" class="btn btn-primary">Add New Batch</a>
    </x-page-header>

    <x-search-form :action="route('domestic.index')" :search="$searchName" placeholder="Search by Employee Name..." />

    @if ($searchName !== '')
        <p class="text-muted">
            Showing results for: <strong>"{{ $searchName }}"</strong>
            ({{ $totalRecords }} records in {{ $batches->total() }} batches)
        </p>
    @endif

    <x-data-table class="domestic-list"
                  :headings="['Batch ID', 'Employee', 'District', 'Travel Objective', 'Travel Date', 'Days', 'Total TADA', '20% Extra', 'Actions']">
        @forelse ($records->groupBy('domestic_Batch_id') as $batchRows)
            @foreach ($batchRows as $row)
                @include('domestic.partials.batch-row', ['row' => $row, 'isFirstInBatch' => $loop->first])
            @endforeach
        @empty
            <x-empty-row colspan="9" message="No records found." id="noResults" />
        @endforelse
    </x-data-table>

    @if ($batches->hasPages())
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
            <span class="pagination-info">
                Showing {{ $batches->count() }} batches ({{ $totalRecordsOnPage }} records)
                | Total: {{ $batches->total() }} batches ({{ $totalRecords }} records)
            </span>
            {{ $batches->links() }}
        </div>
    @endif
</div>
@endsection
