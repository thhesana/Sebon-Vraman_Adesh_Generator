@extends('layouts.app')

@section('title', 'Fiscal Year Master')

@push('styles')
<style>
    .fy-page h2 { text-align: center; margin-bottom: 30px; color: #000000; }
    .fy-page .button-container { margin-bottom: 20px; text-align: center; }
    .fy-page .add-button { background-color: #28a745; color: white; font-size: 16px; padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; transition: background-color 0.3s ease; }
    .fy-page .add-button:hover { background-color: #218838; }
    .fy-page .edit-button { background-color: #007bff; color: white; font-size: 16px; padding: 8px 16px; border-radius: 5px; text-decoration: none; text-align: center; display: inline-block; transition: background-color 0.3s ease, transform 0.2s ease; }
    .fy-page .edit-button:hover { background-color: #0056b3; transform: scale(1.05); }
    .fy-page .edit-button:active { background-color: #004085; }
    .fy-page table { width: 100%; margin: 0 auto; border-collapse: collapse; border: 1px solid #ddd; }
    .fy-page th, .fy-page td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
    .fy-page th { background-color: #007bff; color: white; font-weight: bold; }
    .fy-page tr:nth-child(even) { background-color: #f2f2f2; }
    .fy-page tr:hover { background-color: #f1f1f1; cursor: pointer; }
    .fy-page .no-records { text-align: center; font-size: 18px; color: #888; }
</style>
@endpush

@section('content')
<div class="container fy-page" style="margin-top: 50px; font-family: 'Century Gothic', sans-serif;">
    <h2>FISCAL YEAR MASTER</h2>

    <div class="button-container">
        <button class="add-button" onclick="window.location.href='{{ url('/add_fiscal_year.php') }}'">Add New Fiscal Year</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>SN</th>
                <th>Fiscal Year</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($fiscalYears as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row->fy }}</td>
                    <td>{{ $row->fy_startdate ? \Carbon\Carbon::parse($row->fy_startdate)->format('Y-m-d') : '' }}</td>
                    <td>{{ $row->fy_enddate ? \Carbon\Carbon::parse($row->fy_enddate)->format('Y-m-d') : '' }}</td>
                    <td>
                        <a href="{{ url('/edit_fiscal_year.php') }}?id={{ $row->fiscal_year_master_id }}" class="edit-button">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="no-records">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
