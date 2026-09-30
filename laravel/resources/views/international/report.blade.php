@extends('layouts.app')

@section('title', 'International TADA Report '.$selectedFYName)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/international/report.css') }}">
@endpush

@section('content')
<div class="page intl-report">

<x-page-header class="screen-only" title="International TADA Report"
    :subtitle="($filterApplied && $selectedFYName) ? 'आ.व. '.$selectedFYName : 'Filter, review, export and print international travel allowances'">
    @if ($filterApplied && $records->isNotEmpty())
        <button type="button" class="btn btn-success" id="btnExcel">Export Excel</button>
        <button type="button" class="btn btn-secondary" id="btnPrint">Print</button>
    @endif
</x-page-header>

<!-- Filter card -->
<div class="card mb-3 screen-only">
    <div class="card-header">Filter Report</div>
    <form method="GET" action="{{ route('international.report') }}">
        <div class="card-body">
            <input type="hidden" name="filter_applied" value="1">

            <h2 class="h5 mb-3">Employee &amp; Fiscal Year</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label" for="sel-fy">Fiscal Year / <span class="nepali" lang="ne">आर्थिक वर्ष</span></label>
                    <select name="fy" id="sel-fy" class="form-select select2-filter">
                        <option value="">— All Years —</option>
                        @foreach ($fyList as $fy)
                            <option value="{{ $fy->fiscal_year_master_id }}" @selected($f['fy'] == $fy->fiscal_year_master_id)>
                                {{ $fy->fy }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sel-emp">Employee / <span class="nepali" lang="ne">कर्मचारी</span></label>
                    <select name="emp" id="sel-emp" class="form-select select2-filter">
                        <option value="">— All Employees —</option>
                        @foreach ($empList as $emp)
                            <option value="{{ $emp->EmpPersonalCode }}" @selected($f['emp'] == $emp->EmpPersonalCode)>
                                {{ $emp->EmpName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sel-desig">Designation / <span class="nepali" lang="ne">पद</span></label>
                    <select name="designation" id="sel-desig" class="form-select select2-filter">
                        <option value="">— All Designations —</option>
                        @foreach ($desigList as $designation)
                            <option value="{{ $designation }}" @selected($f['designation'] == $designation)>
                                {{ $designation }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h2 class="h5 mb-3">Destination</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="sel-country">Country / <span class="nepali" lang="ne">देश</span></label>
                    <select name="country" id="sel-country" class="form-select select2-filter">
                        <option value="">— All Countries —</option>
                        @foreach ($countryList as $c)
                            <option value="{{ $c->Country_id }}" @selected($f['country'] == $c->Country_id)>
                                {{ $c->Country_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="sel-city">City / <span class="nepali" lang="ne">शहर</span></label>
                    <select name="city" id="sel-city" class="form-select select2-filter">
                        <option value="">— All Cities —</option>
                        @foreach ($cityList as $c)
                            <option value="{{ $c->City_id }}" @selected($f['city'] == $c->City_id)>
                                {{ $c->City_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h2 class="h5 mb-3">Allowance &amp; Date</h2>
            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label class="form-label" for="sel-dress">Dress Allowance</label>
                    <select name="dress" id="sel-dress" class="form-select select2-filter">
                        <option value="">— All —</option>
                        <option value="1" @selected($f['dress'] === '1')>Yes (छ)</option>
                        <option value="0" @selected($f['dress'] === '0')>No (छैन)</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label" for="sel-extra">Extra 33% Country</label>
                    <select name="extra33" id="sel-extra" class="form-select select2-filter">
                        <option value="">— All —</option>
                        <option value="1" @selected($f['extra33'] === '1')>Yes — Extra 33%</option>
                        <option value="0" @selected($f['extra33'] === '0')>No — Standard</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label" for="date_from">Travel From (AD)</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $f['date_from'] }}">
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label" for="date_to">Travel To (AD)</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $f['date_to'] }}">
                </div>
            </div>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
            @if ($filterApplied)
                <a href="{{ route('international.report') }}" class="btn btn-secondary">Clear All</a>
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
@php
    $batchCount = $records->pluck('Batch_id')->unique()->count();
    $empCount = $records->pluck('EmpPersonalCode')->unique()->count();
    $countryCount = $records->pluck('country.Country_name')->filter()->unique()->count();
@endphp

<!-- Totals -->
<div class="row g-3 mb-3 screen-only">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card"><p class="stat-label">Total Records</p><p class="stat-value">{{ $records->count() }}</p></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card"><p class="stat-label">Batches</p><p class="stat-value">{{ $batchCount }}</p></div>
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
                    <th scope="col">SN</th>
                    <th scope="col">Batch ID</th>
                    <th scope="col">Chalani No</th>
                    <th scope="col">Employee</th>
                    <th scope="col">Designation</th>
                    <th scope="col">Country</th>
                    <th scope="col">City</th>
                    <th scope="col">Travel Objective</th>
                    <th scope="col">Travel Start (BS)</th>
                    <th scope="col">Travel End (BS)</th>
                    <th scope="col">Days</th>
                    <th scope="col">USD Rate</th>
                    <th scope="col">Total USD ($)</th>
                    <th scope="col">Dress Allow.</th>
                    <th scope="col">33% Extra</th>
                </tr>
            </thead>
            <tbody>
            @if ($records->isEmpty())
                <x-empty-row :colspan="15" message="No records found." />
            @else
                @foreach ($records as $row)
                @php
                    $startAD     = $row->travelDateStart?->format('Y-m-d');
                    $endAD       = $row->travelDateEnd?->format('Y-m-d');
                    $is33        = (int) $row->country?->extra33percent_country;
                    $displayRate = (float) $row->daily_rate;
                    $totalUSD    = $row->usd_total;
                    $desigNep    = $row->employee?->designationByName?->designationTypeInNepali ?? '-';
                    $isDress     = $row->DressAllowance;
                @endphp
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td>{{ $row->Batch_id }}</td>
                    <td class="text-nowrap">{{ ($row->fiscalYear?->fy ?? '').'-'.($row->Chalani_id ?? '') }}</td>
                    <td class="nepali" lang="ne">{{ $row->employee?->EmpNameInNepali ?? $row->employee?->EmpName ?? $row->EmpPersonalCode }}</td>
                    <td class="nepali" lang="ne">{{ $desigNep }}</td>
                    <td>{{ $row->country?->Country_name ?? '-' }}</td>
                    <td>{{ $row->city?->City_name ?? '-' }}</td>
                    <td class="intl-objective">{{ $row->travel_objective ?? '' }}</td>
                    <td class="text-nowrap"><span class="bs-date nepali" lang="ne" data-ad="{{ $startAD }}">{{ $startAD }}</span></td>
                    <td class="text-nowrap"><span class="bs-date nepali" lang="ne" data-ad="{{ $endAD }}">{{ $endAD }}</span></td>
                    <td class="num"><x-international.amount :value="$row->days" /></td>
                    <td class="num"><x-international.amount :value="$displayRate" /></td>
                    <td class="num text-nowrap">$ <x-international.amount :value="$totalUSD" /></td>
                    <td><span class="badge {{ $isDress ? 'badge-soft-success' : 'badge-soft-muted' }} nepali" lang="ne">{{ $isDress ? 'छ' : 'छैन' }}</span></td>
                    <td><span class="badge {{ $is33 ? 'badge-soft-warning' : 'badge-soft-muted' }}">{{ $is33 ? '+33%' : 'Standard' }}</span></td>
                </tr>
                @endforeach
            @endif
            </tbody>
            @if ($records->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="12" class="text-end"><span class="nepali" lang="ne">जम्मा</span> (Grand Total)</td>
                    <td class="num text-nowrap">$ {{ number_format($grandTotalUSD, 2) }}</td>
                    <td colspan="2"></td>
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
            @php
                $startAD2     = $row->travelDateStart?->format('Y-m-d');
                $endAD2       = $row->travelDateEnd?->format('Y-m-d');
                $is332        = (int) $row->country?->extra33percent_country;
                $displayRate2 = (float) $row->daily_rate;
                $totalUSD2    = $row->usd_total;
                $desigNep2    = $row->employee?->designationByName?->designationTypeInNepali ?? '-';
                $isDress2     = $row->DressAllowance;
            @endphp
            <tr>
                <td class="tc">{{ $loop->iteration }}</td>
                <td class="tc">{{ ($row->fiscalYear?->fy ?? '').'-'.($row->Chalani_id ?? '') }}</td>
                <td class="fw-semibold">{{ $row->employee?->EmpNameInNepali ?? $row->employee?->EmpName ?? $row->EmpPersonalCode }}</td>
                <td>{{ $desigNep2 }}</td>
                <td>{{ $row->country?->Country_name ?? '-' }}</td>
                <td class="print-objective">{{ $row->travel_objective ?? '' }}</td>
                <td class="tc"><span class="bs-date-print" data-ad="{{ $startAD2 }}">{{ $startAD2 }}</span></td>
                <td class="tc"><span class="bs-date-print" data-ad="{{ $endAD2 }}">{{ $endAD2 }}</span></td>
                <td class="tc"><x-international.amount :value="$row->days" /></td>
                <td class="tr"><x-international.amount :value="$displayRate2" /></td>
                <td class="tr"><x-international.amount :value="$totalUSD2" /></td>
                <td class="tc">{{ $isDress2 ? 'छ' : 'छैन' }}</td>
                <td class="tc">{{ $is332 ? '३३% छ' : 'छैन' }}</td>
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
<script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
window.APP = {{ Js::from([
    'fiscalYear' => $selectedFYName,
    'grandTotal' => '$ '.number_format($grandTotalUSD, 2),
]) }};
</script>
<script src="{{ asset('js/international/report.js') }}"></script>
@endpush
