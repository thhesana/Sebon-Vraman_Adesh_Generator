@extends('layouts.app')

@section('title', 'Domestic TADA Report '.$selectedFYName)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="{{ asset('css/domestic/report.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="page dtr" data-fy-name="{{ $selectedFYName }}" data-chalani-range="{{ $chalaniRange }}">

    <x-page-header class="screen-only" title="Domestic TADA Report"
                   :subtitle="($filterApplied && $selectedFYName) ? 'Fiscal year '.$selectedFYName : 'Filter and export domestic travel records.'">
        @if ($filterApplied && $records->isNotEmpty())
            <button class="btn btn-success" id="btnExcel" type="button">Export Excel</button>
            <button class="btn btn-secondary" id="btnPrint" type="button">Print</button>
        @endif
    </x-page-header>

    <div class="card mb-3 screen-only">
        <div class="card-header">Filter Report</div>
        <form method="GET" action="{{ route('domestic.report') }}">
            <input type="hidden" name="filter_applied" value="1">
            <div class="card-body">
                <div class="row g-3">
                    <x-domestic.filter-select label="Fiscal Year" nepali="आर्थिक वर्ष" name="fy" id="sel-fy" :options="$fyList"
                                              :selected="$filterFY" all="All Years" />
                    <x-domestic.filter-select label="Employee" nepali="कर्मचारी" name="emp" id="sel-emp" :options="$empList"
                                              :selected="$filterEmp" all="All Employees" />
                    <x-domestic.filter-select label="District" nepali="जिल्ला" name="district" id="sel-dist" :options="$districtList"
                                              :selected="$filterDistrict" all="All Districts" />
                    <x-domestic.filter-select label="Designation" nepali="पद" name="designation" id="sel-desig" :options="$desigList"
                                              :selected="$filterDesig" all="All Designations" />
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                @if ($filterApplied)
                    <a href="{{ route('domestic.report') }}" class="btn btn-secondary">Clear</a>
                @endif
                <button type="submit" class="btn btn-primary">Apply Filter</button>
            </div>
        </form>
    </div>

    @if (! $filterApplied)
        <div class="card screen-only">
            <div class="empty-state">Please select filter options above and click <strong>Apply Filter</strong> to load the report.</div>
        </div>
    @else
        <div class="row g-3 mb-3 screen-only">
            <x-domestic.stat-pill :value="$records->count()" label="Total Records" />
            <x-domestic.stat-pill :value="$batchCount" label="Batches" />
            <x-domestic.stat-pill :value="$empCount" label="Employees" />
            <x-domestic.stat-pill :value="'NPR '.number_format($grandTotal, 2)" label="Grand Total" />
        </div>

        <div class="table-card screen-only">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="reportTable">
                    <thead>
                        <tr>
                            <th scope="col">SN</th>
                            <th scope="col">Travel Order</th>
                            <th scope="col">Chalani No</th>
                            <th scope="col">Employee</th>
                            <th scope="col">Designation</th>
                            <th scope="col">District</th>
                            <th scope="col">Travel Objective</th>
                            <th scope="col">Travel Start (BS)</th>
                            <th scope="col">Travel End (BS)</th>
                            <th scope="col" class="num">Days</th>
                            <th scope="col" class="num">Daily Rate (रु)</th>
                            <th scope="col" class="num">Total TADA (रु)</th>
                            <th scope="col">20% Extra</th>
                            <th scope="col">Travel Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($records as $row)
                        @include('domestic.partials.report-screen-row', ['row' => $row, 'sn' => $loop->iteration])
                    @empty
                        <x-empty-row colspan="14" message="No records found." />
                    @endforelse
                    </tbody>
                    @if ($records->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="11" class="text-end"><strong><span class="nepali" lang="ne">जम्मा</span> (Grand Total)</strong></td>
                            <td class="num"><strong><span class="nepali" lang="ne">रु.</span> {{ number_format($grandTotal, 2) }}</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Official printable document: hidden on screen, shown by @media print --}}
        <div id="printSection" lang="ne">
            <div class="print-letterhead">
                <div class="print-org-sub">नेपाल धितोपत्र बोर्ड</div>
                <div class="print-org-sub">खुमत्लर, ललितपुर</div>
            </div>

            <div class="print-doc-title">स्वदेश  भ्रमण  विवरण</div>

            <table id="printTable">
                <thead>
                    <tr>
                        <th class="col-sn">सि.नं.</th>
                        <th>आदेश मिति</th>
                        <th>चलानी नं.</th>
                        <th>कर्मचारीको नाम</th>
                        <th>पद</th>
                        <th>जिल्ला</th>
                        <th>भ्रमणको उद्देश्य</th>
                        <th>भ्रमण सुरु</th>
                        <th>भ्रमण अन्त्य</th>
                        <th class="col-days">दिन</th>
                        <th>दर (रु.)</th>
                        <th>जम्मा (रु.)</th>
                        <th class="col-extra">२०% थप</th>
                        <th>यात्रा</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($records as $row)
                    @include('domestic.partials.report-print-row', ['row' => $row, 'sn' => $loop->iteration])
                @empty
                    <tr><td colspan="14" class="print-empty">कुनै रेकर्ड भेटिएन।</td></tr>
                @endforelse
                </tbody>
                @if ($records->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="11" class="tr">जम्मा (Grand Total)</td>
                        <td class="tr">रु. {{ number_format($grandTotal, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>

            <div class="print-footer">
                <span>यो विवरण कम्प्युटरबाट उत्पन्न गरिएको हो।</span>
                <span id="printFooterDate"></span>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/domestic/report.js') }}"></script>
@endpush
