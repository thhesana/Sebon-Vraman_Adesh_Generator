@extends('layouts.app')

@section('title', 'International TADA Report '.$selectedFYName)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="{{ asset('calender/nepali.datepicker.v5.0.6.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ \App\Support\Asset::v('css/international/report.css') }}">
@endpush

@section('content')
<div class="page page-wide intl-report">

<x-page-header class="screen-only" title="International TADA Report"
    :subtitle="($filterApplied && $selectedFYName) ? 'आ.व. '.$selectedFYName : 'Filter, review, export and print international travel allowances'">
    @if ($filterApplied && $records->isNotEmpty())
        <button type="button" class="btn btn-success" id="btnExcel">Export Excel</button>
        <button type="button" class="btn btn-secondary" id="btnPrint">Print</button>
    @endif
</x-page-header>

@include('international._report-filters', ['filterAction' => route('international.report'), 'showClear' => $filterApplied])

@if (! $filterApplied)
<div class="card screen-only">
    <div class="empty-state">Please select filter options above and click <strong>Apply Filter</strong> to load the report.</div>
</div>

@else
@php
    // $records: report lines from International_tada and Old_International_tada (see InternationalTadaReportController).
    $batchCount = $records->pluck('batch')->filter()->unique()->count();
    $oldCount = $records->where('isOld', true)->count();
    $empCount = $records->pluck('empCode')->unique()->count();
    $countryCount = $records->pluck('country')->filter()->unique()->count();
    // BS date cell: the stored BS value, or the AD date for report.js to convert when BS is missing.
    $bsCell = fn (?string $bs, ?string $ad, string $class) => $bs
        ? '<span class="'.$class.'" data-bs-val="'.e(str_replace('-', '/', $bs)).'">'.e(str_replace('-', '/', $bs)).'</span>'
        : '<span class="'.$class.'" data-ad="'.e($ad).'">'.e($ad ?? '-').'</span>';
@endphp

<!-- Totals -->
<div class="row g-3 mb-3 screen-only">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card"><p class="stat-label">Total Records</p><p class="stat-value">{{ $records->count() }}</p></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card"><p class="stat-label">Batches / Old Records</p><p class="stat-value">{{ $batchCount }} / {{ $oldCount }}</p></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card"><p class="stat-label">Employees</p><p class="stat-value">{{ $empCount }}</p></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card"><p class="stat-label">Countries</p><p class="stat-value">{{ $countryCount }}</p></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card"><p class="stat-label">Grand Total USD</p><p class="stat-value">$ {{ number_format($grandTotalUSD, 2) }}</p></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card"><p class="stat-label">Fiscal Year</p><p class="stat-value">{{ $selectedFYName ?: 'All' }}</p></div>
    </div>
</div>

<!-- Screen table (same markup as x-data-table, plus a totals footer) -->
<div class="table-card screen-only">
    <div class="table-responsive">
        <table id="reportTable" class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col" class="col-sn">SN</th>
                    <th scope="col">Batch / Chalani</th>
                    <th scope="col">Employee (Position)</th>
                    <th scope="col">Destination</th>
                    <th scope="col">Travel Objective</th>
                    <th scope="col" class="col-date">Travel Start<br><small>BS / AD</small></th>
                    <th scope="col" class="col-date">Travel End<br><small>BS / AD</small></th>
                    <th scope="col" class="num">Days</th>
                    <th scope="col" class="num">Rate ($)</th>
                    <th scope="col" class="num">Total ($)</th>
                    <th scope="col" class="text-center">Dress / 33%</th>
                </tr>
            </thead>
            <tbody>
            @if ($records->isEmpty())
                <x-empty-row :colspan="11" message="No records found." />
            @else
                @foreach ($records as $row)
                <tr @class(['old-record' => $row->isOld])>
                    <td class="text-muted col-sn">{{ $loop->iteration }}</td>
                    <td class="text-nowrap">
                        @if ($row->isOld)
                            <span class="badge badge-soft-warning">Old record</span>
                        @else
                            <span class="fw-semibold">{{ $row->batch }}</span>
                        @endif
                        <div class="cell-sub">{{ $row->chalani }}</div>
                    </td>
                    <td class="col-emp">
                        <span class="nepali fw-semibold" lang="ne">{{ $row->empName }}</span>
                        <div class="cell-sub nepali" lang="ne">({{ $row->designation }})</div>
                    </td>
                    <td class="col-dest">{{ collect([$row->city, $row->country])->filter()->implode(', ') ?: '-' }}</td>
                    <td class="intl-objective"><span class="clamp-2" title="{{ $row->objective }}">{{ $row->objective ?? '' }}</span></td>
                    @foreach ([[$row->startBS, $row->startAD], [$row->endBS, $row->endAD]] as [$bs, $ad])
                        <td class="text-nowrap col-date">
                            <span class="nepali" lang="ne">{!! $bsCell($bs, $ad, 'bs-date') !!}</span>
                            <div class="cell-sub">
                                @if ($ad)
                                    {{ str_replace('-', '/', $ad) }}
                                @else
                                    <span class="ad-from-bs" data-bs="{{ $bs }}">-</span>
                                @endif
                                <span class="cell-tag">AD</span>
                            </div>
                        </td>
                    @endforeach
                    <td class="num"><x-international.amount :value="$row->days" /></td>
                    <td class="num"><x-international.amount :value="$row->rate" /></td>
                    <td class="num text-nowrap fw-semibold"><x-international.amount :value="$row->totalUsd" /></td>
                    <td class="text-center col-flags">
                        <span class="badge {{ $row->dress ? 'badge-soft-success' : 'badge-soft-muted' }} nepali" lang="ne" title="Dress allowance">पोशाक {{ $row->dress ? 'छ' : 'छैन' }}</span>
                        @if ($row->extra33)
                            <span class="badge badge-soft-warning" title="Extra 33% country">+33%</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            @endif
            </tbody>
            @if ($records->isNotEmpty())
            <tfoot>
                <tr class="report-total">
                    <td colspan="7" class="text-end">
                        <span class="nepali" lang="ne">जम्मा</span> (Grand Total)
                        <div class="cell-sub">{{ $records->count() }} {{ Str::plural('record', $records->count()) }}</div>
                    </td>
                    <td class="num text-nowrap">
                        <x-international.amount :value="$totalDays" />
                        <div class="cell-sub">days</div>
                    </td>
                    <td class="num text-nowrap">
                        <x-international.amount :value="round($avgRate, 2)" />
                        <div class="cell-sub">avg / day</div>
                    </td>
                    <td class="num text-nowrap total-usd">
                        $ {{ number_format($grandTotalUSD, 2) }}
                        <div class="cell-sub">USD</div>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div><!-- /table-card -->


<!-- PRINT SECTION -->
<div id="printSection" class="nepali" lang="ne">

    <div class="print-letterhead">
        <div class="print-org-name">नेपाल धितोपत्र बोर्ड</div>
        <div>खुमत्लर, ललितपुर</div>
    </div>

    <div class="print-doc-title h4">विदेश भ्रमण भत्ता (International TADA) विवरण</div>

    <table id="printTable" class="small">
        <thead>
            <tr>
                <th class="col-sn">सि.नं.</th>
                <th>चलानी नं.</th>
                <th>कर्मचारीको नाम</th>
                <th>पद</th>
                <th>देश</th>
                <th>भ्रमणको उद्देश्य</th>
                <th>भ्रमण सुरु</th>
                <th>भ्रमण अन्त्य</th>
                <th class="col-days">दिन</th>
                <th>दर ($)</th>
                <th>जम्मा ($)</th>
                <th class="col-dress">पोशाक</th>
                <th class="col-extra">३३% थप</th>
            </tr>
        </thead>
        <tbody>
        @if ($records->isEmpty())
            <tr><td colspan="13" class="tc print-empty">कुनै रेकर्ड भेटिएन।</td></tr>
        @else
            @foreach ($records as $row)
            <tr>
                <td class="tc">{{ $loop->iteration }}</td>
                <td class="tc">{{ $row->chalani }}@if ($row->isOld)<br><small>(पुरानो)</small>@endif</td>
                <td class="fw-semibold">{{ $row->empName }}</td>
                <td>{{ $row->designation }}</td>
                <td>{{ $row->country ?? '-' }}</td>
                <td class="print-objective">{{ $row->objective ?? '' }}</td>
                <td class="tc">{!! $bsCell($row->startBS, $row->startAD, 'bs-date-print') !!}</td>
                <td class="tc">{!! $bsCell($row->endBS, $row->endAD, 'bs-date-print') !!}</td>
                <td class="tc"><x-international.amount :value="$row->days" /></td>
                <td class="tr"><x-international.amount :value="$row->rate" /></td>
                <td class="tr"><x-international.amount :value="$row->totalUsd" /></td>
                <td class="tc">{{ $row->dress ? 'छ' : 'छैन' }}</td>
                <td class="tc">{{ $row->extra33 ? '३३% छ' : 'छैन' }}</td>
            </tr>
            @endforeach
        @endif
        </tbody>
    </table>

    @if ($records->isNotEmpty())
    <div class="print-grand-total">
        <span>जम्मा (Grand Total)</span>
        <span>$ {{ number_format($grandTotalUSD, 2) }}</span>
    </div>
    @endif

</div><!-- /printSection -->

@endif
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
    'fiscalYear' => $selectedFYName,
    'grandTotal' => '$ '.number_format($grandTotalUSD, 2),
]) }};
</script>
<script src="{{ \App\Support\Asset::v('js/international/report.js') }}"></script>
@endpush
