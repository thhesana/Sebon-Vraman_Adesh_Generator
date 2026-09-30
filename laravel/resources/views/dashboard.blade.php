@extends('layouts.app')

@section('title', 'Dashboard - SEBON MIS')

@push('styles')
<style>
.db-section { padding: 2rem 1rem; }
.metric-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.1rem 1.25rem; box-shadow:0 1px 4px rgba(0,0,0,.06); height:100%; }
.metric-label { font-size:.72rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin:0 0 4px; }
.metric-value { font-size:2rem; font-weight:700; margin:0; color:#111827; }
.metric-sub { font-size:.75rem; border-radius:20px; padding:2px 9px; display:inline-block; margin-top:6px; font-weight:500; }
.badge-dom { background:#dbeafe; color:#1d4ed8; }
.badge-int { background:#fef3c7; color:#b45309; }
.chart-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.25rem 1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); height:100%; }
.chart-card h6 { font-size:.9rem; font-weight:700; color:#111827; margin-bottom:2px; }
.chart-card p.sub { font-size:.75rem; color:#9ca3af; margin-bottom:1rem; }
.legend { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:10px; }
.legend-item { font-size:.75rem; color:#6b7280; display:flex; align-items:center; gap:5px; }
.legend-box { width:10px; height:10px; border-radius:2px; display:inline-block; }
.welcome-strip { background:linear-gradient(135deg,#0056b3,#007bff); color:#fff; border-radius:12px; padding:1.4rem 1.75rem; display:flex; align-items:center; gap:16px; margin-bottom:1.5rem; box-shadow:0 2px 8px rgba(0,123,255,.25); }
.welcome-avatar { width:52px; height:52px; background:rgba(255,255,255,.25); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; font-weight:700; flex-shrink:0; }
.welcome-strip h4 { margin:0; font-size:1.1rem; font-weight:700; }
.welcome-strip p { margin:0; font-size:.82rem; opacity:.85; }
</style>
@endpush

@section('content')
<div class="db-section container-fluid px-4">
    <div class="welcome-strip">
        <div class="welcome-avatar">{{ strtoupper(substr($username, 0, 1)) }}</div>
        <div>
            <h4>Welcome back, {{ $username }}!</h4>
            <p>SEBON Vraman Adesh Generator &mdash; Travel Allowance Dashboard &mdash; Last 12 months</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="metric-card">
                <p class="metric-label">Total Domestic</p>
                <p class="metric-value">{{ number_format($totalDom) }}</p>
                <span class="metric-sub badge-dom">Last 12 months</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="metric-card">
                <p class="metric-label">Total International</p>
                <p class="metric-value">{{ number_format($totalInt) }}</p>
                <span class="metric-sub badge-int">Last 12 months</span>
            </div>
        </div>
    </div>

    <div class="chart-card mb-4">
        <h6>Monthly records — Domestic vs International</h6>
        <p class="sub">Grouped bar chart &middot; last 12 months</p>
        <div class="legend">
            <span class="legend-item"><span class="legend-box" style="background:#2563eb"></span>Domestic</span>
            <span class="legend-item"><span class="legend-box" style="background:#f59e0b"></span>International</span>
        </div>
        <div style="position:relative;height:290px;">
            <canvas id="barChart" role="img" aria-label="Grouped bar chart: monthly domestic and international TADA records"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: @json($labels),
        datasets: [
            { label: 'Domestic',      data: @json($domData), backgroundColor: '#2563eb', borderRadius: 5, borderSkipped: false },
            { label: 'International', data: @json($intData), backgroundColor: '#f59e0b', borderRadius: 5, borderSkipped: false }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { autoSkip: false, maxRotation: 45, font: { size: 11 } }, grid: { display: false } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
        }
    }
});
</script>
@endpush
