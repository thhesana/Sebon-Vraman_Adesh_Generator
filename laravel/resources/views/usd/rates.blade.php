@extends('layouts.app')

@section('title', 'USD Forex Rate (Last 12 Records)')

@push('styles')
<style>
    .rate-wrap { padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100%; }
    .rate-wrap h2 { text-align: center; color: #fff; font-size: 28px; font-weight: 600; margin-bottom: 25px; }
    .rate-table { width: 70%; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,.2); border-collapse: collapse; }
    .rate-table th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; text-align: center; font-weight: 600; font-size: 14px; border: none; }
    .rate-table td { padding: 12px 15px; text-align: center; color: #2d3748; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
    .rate-table tr:nth-child(even) { background: #f8f9fa; }
    .rate-table tr:hover { background: #f0f4ff; }
    @media (max-width: 768px) { .rate-table { width: 95%; } .rate-table th, .rate-table td { padding: 10px; font-size: 12px; } .rate-wrap h2 { font-size: 22px; } }
</style>
@endpush

@section('content')
<div class="rate-wrap">
    <h2>USD Forex Conversion Table (Latest 12 Records)</h2>

    <table class="rate-table">
        <tr>
            <th>SN</th>
            <th>Conversion Date</th>
            <th>USD Amount</th>
            <th>Updated Date At SEBON</th>
        </tr>
        @foreach ($rates as $i => $row)
            @php
                $amount = (float) $row->amount;
                $formatted = $amount == floor($amount) ? number_format($amount) : number_format($amount, 2);
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->conversion_date ? \Carbon\Carbon::parse($row->conversion_date)->format('Y-M-d') : '' }}</td>
                <td>{{ $formatted }}</td>
                <td>{{ $row->UpdatedDateBySebon ? \Carbon\Carbon::parse($row->UpdatedDateBySebon)->format('Y-M-d H:i:s') : '' }}</td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
