@extends('layouts.app')

@section('title', 'INTERNATIONAL TRAVEL RECORDS')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .intl-page { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 30px 0; }
    .intl-page .main-container { background: #ffffff; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); padding: 40px; animation: fadeIn 0.5s ease-in; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
    .page-title { color: #2d3748; font-size: 32px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 12px; }
    .btn-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); }
    .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); color: white; }
    .table-container { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 25px; }
    .table-responsive { border-radius: 15px; overflow-x: auto; }
    .custom-table { margin: 0; width: 100%; border-collapse: collapse; }
    .custom-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .custom-table thead th { padding: 18px 16px; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; white-space: nowrap; border: none; position: sticky; top: 0; z-index: 10; background: transparent; color: white; }
    .custom-table tbody tr { border-bottom: 1px solid #e2e8f0; transition: all 0.3s ease; }
    .custom-table tbody tr:hover { background: linear-gradient(90deg, #f7fafc 0%, #edf2f7 100%); transform: scale(1.001); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); }
    .custom-table tbody tr.batch-group { border-top: 3px solid #667eea; background: linear-gradient(90deg, #f0f4ff 0%, #e9f0ff 100%); font-weight: 500; }
    .custom-table tbody tr.batch-group:hover { background: linear-gradient(90deg, #e6edff 0%, #dde7ff 100%); }
    .custom-table tbody td { padding: 16px; color: #2d3748; vertical-align: middle; font-size: 14px; border: none; }
    .custom-table tbody tr:nth-child(even):not(.batch-group) { background: #fafafa; }
    .batch-id-column { font-weight: 700; color: #667eea; font-size: 15px; }
    .employee-column { font-weight: 500; color: #2d3748; }
    .country-column { display: flex; align-items: center; gap: 6px; }
    .usd-column { font-weight: 700; color: #2f855a; font-size: 16px; }
    .badge-yes { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
    .badge-no, .badge-no-extra { background: #e2e8f0; color: #718096; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
    .badge-extra { background: linear-gradient(135deg, #f6ad55 0%, #dd6b20 100%); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
    .action-buttons { display: flex; flex-direction: column; gap: 8px; align-items: stretch; }
    .btn-action { padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.3s ease; border: none; white-space: nowrap; text-align: center; }
    .btn-edit { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
    .btn-edit:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4); color: white; }
    .btn-print { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
    .btn-print:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(79, 172, 254, 0.4); color: white; }
    .table-responsive::-webkit-scrollbar { height: 10px; }
    .table-responsive::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .table-responsive::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; }
    .pagination-container { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; flex-wrap: wrap; gap: 15px; }
    .intl-page .pagination { display: flex; gap: 5px; list-style: none; margin: 0; padding: 0; }
    .intl-page .pagination .page-link { padding: 8px 14px; background: #f7fafc; color: #667eea; border: 2px solid #e2e8f0; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; transition: all 0.3s ease; display: inline-block; }
    .intl-page .pagination .page-link:hover { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: #667eea; transform: translateY(-2px); }
    .intl-page .pagination .active .page-link { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: #667eea; }
    .intl-page .pagination .disabled .page-link { background: #e2e8f0; color: #a0aec0; cursor: not-allowed; pointer-events: none; }
    @media (max-width: 768px) {
        .intl-page .main-container { padding: 20px; border-radius: 15px; }
        .page-title { font-size: 24px; }
        .custom-table thead th, .custom-table tbody td { padding: 12px 10px; font-size: 12px; }
        .btn-action { padding: 5px 10px; font-size: 11px; }
    }
</style>
@endpush

@section('content')
@php
    $formatDate = fn ($d) => $d ? strtoupper(\Carbon\Carbon::parse($d)->format('d-M-Y')) : '-';
@endphp
<div class="intl-page">
<div class="container-fluid px-lg-5">
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-airplane-fill"></i>
                INTERNATIONAL TRAVEL RECORDS
            </h1>
            <a href="{{ route('international.create') }}" class="btn-gradient">
                <i class="bi bi-plus-circle-fill"></i>
                Add New Batch
            </a>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="custom-table" id="tadaTable">
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Employee</th>
                            <th>Country</th>
                            <th>Travel Objective</th>
                            <th>Travel Date</th>
                            <th>Days</th>
                            <th>Total USD</th>
                            <th>Dress Allow.</th>
                            <th>Extra 33%</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($records->isEmpty())
                            <tr id="noResults"><td colspan="11" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e0;"></i>
                                <p class="mt-3 text-muted">No records found</p>
                            </td></tr>
                        @else
                            @php $currentBatch = null; $editedBatches = []; @endphp
                            @foreach ($records as $row)
                                @php
                                    $batchClass = '';
                                    if ($currentBatch !== $row->Batch_id) {
                                        $currentBatch = $row->Batch_id;
                                        $batchClass = 'batch-group';
                                    }
                                @endphp
                                <tr class="{{ $batchClass }}">
                                    <td class="batch-id-column">{{ $row->Batch_id }}</td>
                                    <td class="employee-column">{{ $row->EmployeeName ?? $row->EmpPersonalCode }}</td>
                                    <td>
                                        <span class="country-column">
                                            <i class="bi bi-geo-alt-fill"></i>
                                            {{ $row->Country }}
                                        </span>
                                    </td>
                                    <td>{{ $row->travel_objective }}</td>
                                    <td>{{ $formatDate($row->travelDateStart) }}-{{ $formatDate($row->travelDateEnd) }}</td>
                                    <td class="text-center"><strong>{{ \App\Support\Fmt::num($row->totalday) }}</strong></td>
                                    <td class="usd-column">${{ \App\Support\Fmt::num($row->tadaInUSD_Final) }}</td>
                                    <td class="text-center">
                                        <span class="{{ $row->DressAllowance == 'Yes' ? 'badge-yes' : 'badge-no' }}">{{ $row->DressAllowance }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="{{ str_contains($row->Extra33Percent, 'Receives') ? 'badge-extra' : 'badge-no-extra' }}">{{ $row->Extra33Percent }}</span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            @if ($row->Batch_id && ! in_array($row->Batch_id, $editedBatches))
                                                @php $editedBatches[] = $row->Batch_id; @endphp
                                                <a href="{{ route('international.edit', ['batch' => $row->Batch_id]) }}" class="btn-action btn-edit">
                                                    <i class="bi bi-pencil-fill"></i> Edit
                                                </a>
                                                <a href="{{ route('international.print', ['batch' => $row->Batch_id]) }}" class="btn-action btn-print" target="_blank">
                                                    <i class="bi bi-printer-fill"></i> Print
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if ($records->hasPages())
        <div class="pagination-container">
            {{ $records->links() }}
        </div>
        @endif
    </div>
</div>
</div>
@endsection
