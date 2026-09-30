@extends('layouts.app')

@section('title', 'USD Forex Rate (Last 12 Records)')

@section('content')
<div class="page">
    <x-page-header title="USD Forex Conversion Table" subtitle="Latest 12 records" />

    <x-data-table :headings="['SN', 'Conversion Date', 'USD Amount', 'Updated Date At SEBON']">
        @forelse ($rates as $i => $row)
            @php
                $amount = (float) $row->amount;
                $formatted = $amount == floor($amount) ? number_format($amount) : number_format($amount, 2);
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->conversion_date ? \Carbon\Carbon::parse($row->conversion_date)->format('Y-M-d') : '' }}</td>
                <td class="num">{{ $formatted }}</td>
                <td>{{ $row->UpdatedDateBySebon ? \Carbon\Carbon::parse($row->UpdatedDateBySebon)->format('Y-M-d H:i:s') : '' }}</td>
            </tr>
        @empty
            <x-empty-row colspan="4" message="No records found." />
        @endforelse
    </x-data-table>
</div>
@endsection
