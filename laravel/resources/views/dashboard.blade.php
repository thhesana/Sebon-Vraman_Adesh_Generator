@extends('layouts.app')

@section('title', 'Dashboard - SEBON MIS')

@section('content')
<div class="page">
    <x-page-header :title="'Welcome back, ' . $username" subtitle="Travel Allowance dashboard, last 12 months" />

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card">
                <p class="stat-label">Total Domestic</p>
                <p class="stat-value">{{ number_format($totalDom) }}</p>
                <span class="badge badge-soft-primary">Last 12 months</span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card">
                <p class="stat-label">Total International</p>
                <p class="stat-value">{{ number_format($totalInt) }}</p>
                <span class="badge badge-soft-warning">Last 12 months</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Monthly records: Domestic vs International</div>
        <div class="card-body">
            <div class="dashboard-chart">
                <canvas id="barChart" role="img" aria-label="Grouped bar chart: monthly domestic and international TADA records"
                        data-labels="{{ json_encode($labels) }}"
                        data-domestic="{{ json_encode($domData) }}"
                        data-international="{{ json_encode($intData) }}"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('css/dashboard/index.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/dashboard/index.js') }}"></script>
@endpush
