@extends('layouts.app')

@section('title', "Summarized Int'l Report ".$selectedFYName)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="{{ asset('calender/nepali.datepicker.v5.0.6.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ \App\Support\Asset::v('css/international/report.css') }}">
@endpush

@section('content')
@php
    // $summary: one line per employee (see InternationalTadaReportController::summary()).
    $totalVisits = $summary->sum('visits');
    $totalOld = $summary->sum('oldVisits');
    $totalDays = $summary->sum('days');
    // Amounts are rupees received (see InternationalTadaReportController::currentRow / oldRow).
    $totalNpr = $summary->sum('totalNpr');
    $maxNpr = max((float) $summary->max('totalNpr'), 1);
    $missingRate = $summary->sum('nprMissing');
    $rs = fn ($amount) => 'Rs '.\App\Support\NepaliNumber::money($amount, true);

    // Header link: a new column starts Low → High; clicking the active column flips the order.
    $sortUrl = fn (string $key) => request()->fullUrlWithQuery([
        'sort' => $key,
        'dir' => $sort === $key ? ($dir === 'asc' ? 'desc' : 'asc') : 'asc',
    ]);
    $arrow = fn (string $key) => $sort === $key ? ($dir === 'asc' ? '▲' : '▼') : '↕';
@endphp
<div class="page page-wide intl-report intl-summary">

<x-page-header class="screen-only" title="Summarized International Report"
    :subtitle="($selectedFYName ? 'आ.व. '.$selectedFYName.' · ' : '').'One line per employee: visits, programme days and amount received'">
    @if ($summary->isNotEmpty())
        <button type="button" class="btn btn-success" id="btnExcel">Export Excel</button>
        <button type="button" class="btn btn-secondary" id="btnPrint">Print</button>
    @endif
    <a href="{{ route('international.report', request()->except(['sort', 'dir'])) }}" class="btn btn-outline-primary">Detailed report</a>
</x-page-header>

@include('international._report-filters', [
    'filterAction' => route('international.summary'),
    'showClear' => request()->query->count() > 0,
    'extraHidden' => ['sort' => $sort, 'dir' => $dir],
])

<!-- Totals -->
<div class="row g-3 mb-3 screen-only">
    <div class="col-6 col-md-4 col-xl"><div class="stat-card"><p class="stat-label">Employees</p><p class="stat-value">{{ $summary->count() }}</p></div></div>
    <div class="col-6 col-md-4 col-xl"><div class="stat-card"><p class="stat-label">Int'l Visits</p><p class="stat-value">{{ $totalVisits }}</p>@if ($totalOld)<p class="cell-sub mb-0">incl. {{ $totalOld }} old</p>@endif</div></div>
    <div class="col-6 col-md-4 col-xl"><div class="stat-card"><p class="stat-label">Programme Days</p><p class="stat-value"><x-international.amount :value="$totalDays" /></p></div></div>
    <div class="col-6 col-md-4 col-xl"><div class="stat-card"><p class="stat-label">Amount Received</p><p class="stat-value">{{ $rs($totalNpr) }}</p>@if ($missingRate)<p class="cell-sub mb-0 text-warning-emphasis">excl. {{ $missingRate }} {{ Str::plural('trip', $missingRate) }} without USD rate</p>@endif</div></div>
    <div class="col-6 col-md-4 col-xl"><div class="stat-card"><p class="stat-label">Fiscal Year</p><p class="stat-value">{{ $selectedFYName ?: 'All' }}</p></div></div>
</div>

<!-- Sort toolbar -->
<form method="GET" action="{{ route('international.summary') }}" class="summary-sort screen-only" id="sortForm">
    @foreach (request()->except(['sort', 'dir']) as $name => $value)
        @if (is_scalar($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <label for="sortSelect" class="form-label mb-0">Sort by</label>
    <select name="sort" id="sortSelect" class="form-select form-select-sm">
        @foreach ($sortOptions as $value => $label)
            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <div class="btn-group btn-group-sm" role="group" aria-label="Sort order">
        <input type="radio" class="btn-check" name="dir" id="dirDesc" value="desc" @checked($dir === 'desc')>
        <label class="btn btn-outline-primary" for="dirDesc">▼ High → Low</label>
        <input type="radio" class="btn-check" name="dir" id="dirAsc" value="asc" @checked($dir === 'asc')>
        <label class="btn btn-outline-primary" for="dirAsc">▲ Low → High</label>
    </div>
</form>

<div class="table-card">
    <div class="table-responsive">
        <table id="reportTable" class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col" class="col-sn">#</th>
                    <th scope="col"><a class="sort-link" href="{{ $sortUrl('name') }}">Employee (Position) <span>{{ $arrow('name') }}</span></a></th>
                    <th scope="col" class="num"><a class="sort-link" href="{{ $sortUrl('visits') }}">Int'l Visits <span>{{ $arrow('visits') }}</span></a></th>
                    <th scope="col">Countries Visited</th>
                    <th scope="col" class="num"><a class="sort-link" href="{{ $sortUrl('days') }}">Programme Days <span>{{ $arrow('days') }}</span></a></th>
                    <th scope="col" class="num col-amount"><a class="sort-link" href="{{ $sortUrl('amount') }}">Amount Received (Rs) <span>{{ $arrow('amount') }}</span></a></th>
                    <th scope="col" class="col-date"><a class="sort-link" href="{{ $sortUrl('last') }}">Last Travel<br><small>BS / AD</small> <span>{{ $arrow('last') }}</span></a></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($summary as $row)
                <tr>
                    <td class="text-muted col-sn">{{ $loop->iteration }}</td>
                    <td class="col-emp">
                        <a href="{{ route('international.report', ['filter_applied' => 1, 'emp' => $row->empCode] + request()->except(['sort', 'dir', 'emp', 'filter_applied'])) }}"
                           class="nepali fw-semibold emp-link" lang="ne" title="Open this employee's trips in the detailed report">{{ $row->empName }}</a>
                        <div class="cell-sub nepali" lang="ne">({{ $row->designation }})</div>
                    </td>
                    <td class="num">
                        <span class="visit-count">{{ $row->visits }}</span>
                        @if ($row->oldVisits)
                            <div class="cell-sub">{{ $row->oldVisits }} old</div>
                        @endif
                    </td>
                    <td class="col-countries">
                        @foreach ($row->countries as $country => $times)
                            <span class="badge badge-soft-muted">{{ $country }}@if ($times > 1) ×{{ $times }}@endif</span>
                        @endforeach
                    </td>
                    <td class="num"><x-international.amount :value="$row->days" /></td>
                    <td class="num col-amount">
                        @if ($row->nprMissing === $row->visits)
                            <span class="text-muted" title="No NRB USD rate is stored for the form date of this employee's trip(s), so the rupee amount cannot be worked out.">Rate missing</span>
                        @else
                            <span class="fw-semibold">{{ \App\Support\NepaliNumber::money($row->totalNpr, true) }}</span>
                        @endif
                        @if ($row->nprMissing && $row->nprMissing < $row->visits)
                            <div class="cell-sub text-warning-emphasis" title="No NRB USD rate is stored for the form date of {{ $row->nprMissing }} trip(s); they are not included in this amount.">
                                + {{ $row->nprMissing }} {{ Str::plural('trip', $row->nprMissing) }}: rate missing
                            </div>
                        @endif
                        <div class="amount-bar" aria-hidden="true"><span style="width: {{ round($row->totalNpr / $maxNpr * 100, 1) }}%"></span></div>
                    </td>
                    <td class="text-nowrap col-date">
                        @if ($row->lastTravelBS)
                            <span class="nepali" lang="ne">{{ str_replace('-', '/', $row->lastTravelBS) }}</span>
                            <div class="cell-sub">
                                @if ($row->lastTravelAD)
                                    {{ str_replace('-', '/', $row->lastTravelAD) }}
                                @else
                                    <span class="ad-from-bs" data-bs="{{ $row->lastTravelBS }}">-</span>
                                @endif
                                <span class="cell-tag">AD</span>
                            </div>
                        @else
                            {{ $row->lastTravelAD ? str_replace('-', '/', $row->lastTravelAD).' AD' : '-' }}
                        @endif
                    </td>
                </tr>
            @empty
                <x-empty-row :colspan="7" message="No international travel found for these filters." />
            @endforelse
            </tbody>
            @if ($summary->isNotEmpty())
            <tfoot>
                <tr class="report-total">
                    <td colspan="2" class="text-end">
                        <span class="nepali" lang="ne">जम्मा</span> (Grand Total)
                        <div class="cell-sub">{{ $summary->count() }} {{ Str::plural('employee', $summary->count()) }}</div>
                    </td>
                    <td class="num">{{ $totalVisits }}<div class="cell-sub">visits</div></td>
                    <td></td>
                    <td class="num"><x-international.amount :value="$totalDays" /><div class="cell-sub">days</div></td>
                    <td class="num text-nowrap total-usd">{{ $rs($totalNpr) }}<div class="cell-sub">NPR{{ $missingRate ? ' · excl. '.$missingRate.' without rate' : '' }}</div></td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

</div><!-- /page -->
@endsection

@push('scripts')
@use('Illuminate\Support\Js')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="{{ asset('calender/nepali.datepicker.v5.0.6.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
window.APP = {{ Js::from([
    'fiscalYear' => $selectedFYName ?: 'All',
    'grandTotal' => $rs($totalNpr),
    'grandTotalLabel' => 'Total Received (NPR):',
    'title' => 'विदेश भ्रमण सारांश (Summarized International Report)',
    'sheetName' => 'Intl Summary',
    'filePrefix' => 'IntlSummary_',
]) }};
// Sort toolbar applies immediately.
document.querySelectorAll('#sortForm select, #sortForm input[type=radio]').forEach(function (el) {
    el.addEventListener('change', function () { document.getElementById('sortForm').submit(); });
});
</script>
<script src="{{ \App\Support\Asset::v('js/international/report.js') }}"></script>
@endpush
