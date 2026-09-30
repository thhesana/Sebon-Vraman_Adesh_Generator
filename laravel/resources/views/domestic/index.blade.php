@extends('layouts.app')

@section('title', 'DOMESTIC TRAVEL RECORDS')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .dt-page { padding: 30px 0; }
    .main-container { background: #ffffff; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); padding: 40px; animation: fadeIn 0.5s ease-in; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
    .page-title { color: #2d3748; font-size: 32px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 12px; }
    .btn-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); }
    .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); color: white; }
    .search-container { background: linear-gradient(135deg, #f0f4ff 0%, #e9f0ff 100%); padding: 20px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1); }
    .search-form { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
    .search-input-wrapper { flex: 1; min-width: 250px; position: relative; }
    .search-input { width: 100%; padding: 12px 45px 12px 20px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.3s ease; background: white; }
    .search-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .search-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #a0aec0; pointer-events: none; }
    .btn-search { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); white-space: nowrap; }
    .btn-search:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
    .btn-clear { background: #e2e8f0; color: #4a5568; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
    .btn-clear:hover { background: #cbd5e0; transform: translateY(-2px); }
    .search-results-info { margin-top: 15px; padding: 12px 20px; background: white; border-radius: 10px; color: #2d3748; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
    .table-container { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 25px; }
    .table-responsive { border-radius: 15px; overflow-x: auto; }
    .custom-table { margin: 0; width: 100%; border-collapse: collapse; }
    .custom-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .custom-table thead th { padding: 18px 16px; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; white-space: nowrap; border: none; position: sticky; top: 0; z-index: 10; }
    .custom-table tbody tr { border-bottom: 1px solid #e2e8f0; transition: all 0.3s ease; }
    .custom-table tbody tr:hover { background: linear-gradient(90deg, #f7fafc 0%, #edf2f7 100%); transform: scale(1.001); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); }
    .custom-table tbody tr.batch-group { border-top: 3px solid #667eea; background: linear-gradient(90deg, #f0f4ff 0%, #e9f0ff 100%); font-weight: 500; }
    .custom-table tbody tr.batch-group:hover { background: linear-gradient(90deg, #e6edff 0%, #dde7ff 100%); }
    .custom-table tbody td { padding: 16px; color: #2d3748; vertical-align: middle; font-size: 14px; border: none; }
    .custom-table tbody tr:nth-child(even):not(.batch-group) { background: #fafafa; }
    .batch-id-column { font-weight: 700; color: #667eea; font-size: 15px; }
    .employee-column { font-weight: 500; color: #2d3748; }
    .district-column { display: flex; align-items: center; gap: 6px; }
    .tada-column { font-weight: 700; color: #2f855a; font-size: 16px; }
    .badge-yes { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
    .badge-no { background: #e2e8f0; color: #718096; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
    .action-buttons { display: flex; flex-direction: column; gap: 8px; align-items: stretch; }
    .btn-action { padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.3s ease; border: none; white-space: nowrap; text-align: center; }
    .btn-edit { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
    .btn-edit:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4); color: white; }
    .btn-print { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
    .btn-print:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(79, 172, 254, 0.4); color: white; }
    .table-responsive::-webkit-scrollbar { height: 10px; }
    .table-responsive::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .table-responsive::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; }
    .table-responsive::-webkit-scrollbar-thumb:hover { background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); }
    .pagination-container { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; flex-wrap: wrap; gap: 15px; }
    .pagination-info { color: #4a5568; font-size: 14px; font-weight: 500; }
    .dt-page .pagination { display: flex; gap: 5px; list-style: none; margin: 0; padding: 0; }
    .pagination li a { padding: 8px 14px; background: #f7fafc; color: #667eea; border: 2px solid #e2e8f0; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; transition: all 0.3s ease; display: inline-block; }
    .pagination li a:hover { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: #667eea; transform: translateY(-2px); }
    .pagination li.active a { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: #667eea; }
    .pagination li.disabled a { background: #e2e8f0; color: #a0aec0; cursor: not-allowed; pointer-events: none; }
    @media (max-width: 768px) {
        .main-container { padding: 20px; border-radius: 15px; }
        .page-title { font-size: 24px; }
        .search-form { flex-direction: column; }
        .search-input-wrapper { width: 100%; }
        .btn-search, .btn-clear { width: 100%; }
        .custom-table thead th, .custom-table tbody td { padding: 12px 10px; font-size: 12px; }
        .btn-action { padding: 5px 10px; font-size: 11px; }
        .pagination-container { flex-direction: column; gap: 15px; }
        .dt-page .pagination { flex-wrap: wrap; justify-content: center; }
    }
</style>
@endpush

@section('content')
@php
    $formatDate = fn ($d) => $d ? strtoupper(\Carbon\Carbon::parse($d)->format('d-M-Y')) : '-';
    $formatNumber = fn ($n) => ((float) $n == floor((float) $n)) ? number_format((float) $n, 0) : number_format((float) $n, 2);
    $searchQs = $searchName !== '' ? '&search='.urlencode($searchName) : '';
    $pageUrl = fn ($p) => url('/DomesticTadaView.php').'?page='.$p.$searchQs;
@endphp
<div class="dt-page">
<div class="container-fluid px-lg-5">
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-house-fill"></i>
                DOMESTIC TRAVEL RECORDS
            </h1>
            <a href="{{ url('/AddDomesticTada.php') }}" class="btn-gradient">
                <i class="bi bi-plus-circle-fill"></i>
                Add New Batch
            </a>
        </div>

        <!-- Search Filter -->
        <div class="search-container">
            <form method="GET" action="{{ url('/DomesticTadaView.php') }}" class="search-form">
                <div class="search-input-wrapper">
                    <input type="text" name="search" class="search-input" placeholder="Search by Employee Name..." value="{{ $searchName }}">
                    <i class="bi bi-search search-icon"></i>
                </div>
                <button type="submit" class="btn-search">
                    <i class="bi bi-search"></i>
                    Search
                </button>
                @if ($searchName !== '')
                    <a href="{{ url('/DomesticTadaView.php') }}" class="btn-clear">
                        <i class="bi bi-x-circle"></i>
                        Clear
                    </a>
                @endif
            </form>

            @if ($searchName !== '')
                <div class="search-results-info">
                    <i class="bi bi-info-circle-fill" style="color: #667eea;"></i>
                    Showing results for: <strong>"{{ $searchName }}"</strong>
                    ({{ $totalRecords }} records in {{ $totalBatches }} batches)
                </div>
            @endif
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Employee</th>
                            <th>District</th>
                            <th>Travel Objective</th>
                            <th>Travel Date</th>
                            <th>Days</th>
                            <th>Total TADA</th>
                            <th>20% Extra</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (empty($records))
                            <tr id="noResults"><td colspan="9" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e0;"></i>
                                <p class="mt-3 text-muted">No records found</p>
                            </td></tr>
                        @else
                            @php $currentBatch = null; @endphp
                            @foreach ($records as $row)
                                @php
                                    $isFirstInBatch = $currentBatch !== $row->domestic_Batch_id;
                                    if ($isFirstInBatch) { $currentBatch = $row->domestic_Batch_id; }
                                @endphp
                                <tr class="{{ $isFirstInBatch ? 'batch-group' : '' }}">
                                    <td class="batch-id-column">{{ $row->domestic_Batch_id }}</td>
                                    <td class="employee-column">{{ $row->EmpName ?? $row->EmpPersonalCode }}</td>
                                    <td>
                                        <span class="district-column">
                                            <i class="bi bi-geo-alt-fill"></i>
                                            {{ $row->District_name }}
                                        </span>
                                    </td>
                                    <td>{{ $row->domestic_travel_objective }}</td>
                                    <td>{{ $formatDate($row->domestic_travelDateStart) }} - {{ $formatDate($row->domestic_travelDateEnd) }}</td>
                                    <td class="text-center"><strong>{{ $formatNumber($row->domestic_totalday) }}</strong></td>
                                    <td class="tada-column">NPR {{ $formatNumber($row->domestic_tada) }}</td>
                                    <td class="text-center">
                                        <span class="{{ $row->isTwentyPercentExtra == 'YES' ? 'badge-yes' : 'badge-no' }}">
                                            {{ $row->isTwentyPercentExtra }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            @if ($isFirstInBatch)
                                                <a href="{{ url('/EditDomesticTada.php') }}?batch_id={{ urlencode($row->domestic_Batch_id) }}" class="btn-action btn-edit">
                                                    <i class="bi bi-pencil-fill"></i> Edit
                                                </a>
                                                <a href="{{ url('/PrintDomesticTada.php') }}?batch_id={{ urlencode($row->domestic_Batch_id) }}" class="btn-action btn-print" target="_blank">
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

        @if ($totalPages > 1)
        <div class="pagination-container">
            <div class="pagination-info">
                Showing {{ count($currentPageBatches) }} batches
                ({{ $totalRecordsOnPage }} records)
                | Total: {{ $totalBatches }} batches ({{ $totalRecords }} records)
            </div>

            <ul class="pagination">
                <li class="{{ $currentPage == 1 ? 'disabled' : '' }}">
                    <a href="{{ $pageUrl($currentPage - 1) }}">
                        <i class="bi bi-chevron-left"></i> Prev
                    </a>
                </li>

                @php
                    $range = 2;
                    $startPage = max(1, $currentPage - $range);
                    $endPage = min($totalPages, $currentPage + $range);
                @endphp

                @if ($startPage > 1)
                    <li><a href="{{ $pageUrl(1) }}">1</a></li>
                    @if ($startPage > 2)
                        <li class="disabled"><a>...</a></li>
                    @endif
                @endif

                @for ($i = $startPage; $i <= $endPage; $i++)
                    <li class="{{ $i == $currentPage ? 'active' : '' }}">
                        <a href="{{ $pageUrl($i) }}">{{ $i }}</a>
                    </li>
                @endfor

                @if ($endPage < $totalPages)
                    @if ($endPage < $totalPages - 1)
                        <li class="disabled"><a>...</a></li>
                    @endif
                    <li><a href="{{ $pageUrl($totalPages) }}">{{ $totalPages }}</a></li>
                @endif

                <li class="{{ $currentPage == $totalPages ? 'disabled' : '' }}">
                    <a href="{{ $pageUrl($currentPage + 1) }}">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </div>
        @endif
    </div>
</div>
</div>
@endsection
