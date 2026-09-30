@extends('layouts.app')

@section('title', 'International TADA Report '.$selectedFYName)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kalimati&display=swap" rel="stylesheet">
<style>
.intl-report, .intl-report *, .intl-report *::before, .intl-report *::after {
    box-sizing: border-box;
    font-family: 'Kalimati', sans-serif !important;
}
.intl-report {
    --navy:    #1e3a5f;
    --navy-lt: #dbeafe;
    --navy-dk: #152c47;
    --gold:    #b45309;
    --gold-lt: #fef3c7;
    --ink:     #0f172a;
    --muted:   #64748b;
    --border:  #e2e8f0;
    --bg:      #f8fafc;
    --white:   #ffffff;
    --green:   #166534;
    --green-lt:#dcfce7;
    background: var(--bg); color: var(--ink); min-height: 100vh; padding-bottom: 60px; font-size: 14px;
}

/* ── Top bar ── */
.intl-report .topbar { background: var(--white); border-bottom: 1px solid var(--border); padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 8px rgba(0,0,0,.05); }
.intl-report .topbar-title { font-size: 19px; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--ink); }
.intl-report .topbar-title .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--navy); flex-shrink: 0; }
.intl-report .topbar-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

/* ── Buttons ── */
.intl-report .btn-navy, .intl-report .btn-outline, .intl-report .btn-excel, .intl-report .btn-print { border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: background .2s, transform .15s; padding: 9px 18px; border: none; }
.intl-report .btn-navy   { background: var(--navy); color: #fff; }
.intl-report .btn-navy:hover { background: var(--navy-dk); transform: translateY(-1px); color: #fff; }
.intl-report .btn-outline { background: transparent; color: var(--navy); border: 1.5px solid var(--navy); padding: 8px 16px; }
.intl-report .btn-outline:hover { background: var(--navy-lt); color: var(--navy-dk); }
.intl-report .btn-excel  { background: #166534; color: #fff; }
.intl-report .btn-excel:hover { background: #14532d; }
.intl-report .btn-print  { background: var(--navy); color: #fff; }
.intl-report .btn-print:hover { background: var(--navy-dk); }

/* ── Filter card ── */
.intl-report .filter-card { background: var(--white); border: 1px solid var(--border); border-radius: 14px; padding: 22px 28px; margin: 22px 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04); }
.intl-report .filter-card h6 { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 16px; display: flex; align-items: center; gap: 6px; }
.intl-report .filter-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--navy); margin: 16px 0 10px; border-bottom: 1px solid var(--border); padding-bottom: 6px; }
.intl-report .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
.intl-report .filter-grid label { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 5px; display: block; }
.intl-report .filter-native { width: 100%; height: 40px; border: 1.5px solid var(--border); border-radius: 8px; background: var(--bg); padding: 0 12px; font-size: 13px; color: var(--ink); outline: none; }
.intl-report .filter-native:focus { border-color: var(--navy); box-shadow: 0 0 0 3px rgba(30,58,95,.12); }

/* Select2 */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single { height: 40px; border: 1.5px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; background: #f8fafc; }
.select2-container--default.select2-container--focus .select2-selection--single { border-color: #1e3a5f; box-shadow: 0 0 0 3px rgba(30,58,95,.12); }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px; color: #0f172a; font-size: 13px; padding-left: 12px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px; }
.select2-dropdown { border: 1.5px solid #1e3a5f; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.select2-container--default .select2-results__option--highlighted[aria-selected] { background: #1e3a5f; }

/* ── Stats bar ── */
.intl-report .stats-bar { display: flex; gap: 14px; margin: 0 32px 18px; flex-wrap: wrap; }
.intl-report .stat-pill { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 12px 20px; display: flex; flex-direction: column; gap: 3px; min-width: 150px; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
.intl-report .stat-pill .val { font-size: 22px; font-weight: 700; color: var(--navy); line-height: 1; }
.intl-report .stat-pill .lbl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); }
.intl-report .stat-pill.gold .val { color: var(--gold); }

/* ── Prompt box ── */
.intl-report .prompt-box { margin: 0 32px; background: var(--white); border: 1.5px dashed var(--border); border-radius: 14px; padding: 60px 20px; text-align: center; color: var(--muted); }
.intl-report .prompt-box i { font-size: 48px; margin-bottom: 14px; display: block; color: #cbd5e1; }
.intl-report .prompt-box p { font-size: 15px; }

/* ── Screen table ── */
.intl-report .table-wrap { margin: 0 32px; background: var(--white); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.05); }
#reportTable { width: 100%; border-collapse: collapse; font-size: 12.5px; }
#reportTable thead tr { background: var(--navy); color: #fff; }
#reportTable thead th { padding: 11px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; border: none; background: transparent; color: #fff; }
#reportTable tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
#reportTable tbody tr:hover { background: #eff6ff; }
#reportTable tbody td { padding: 11px 12px; color: var(--ink); vertical-align: middle; }
.intl-report .td-emp  { font-weight: 600; }
.intl-report .td-usd  { font-weight: 700; color: var(--green); white-space: nowrap; }
.intl-report .td-days { text-align: center; }
.intl-report .badge-yes  { background: var(--green-lt); color: var(--green); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.intl-report .badge-no   { background: #f1f5f9; color: var(--muted); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.intl-report .badge-extra { background: #fef3c7; color: var(--gold); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
#reportTable tfoot tr { background: #eff6ff; border-top: 2px solid var(--navy); }
#reportTable tfoot td { padding: 12px; font-weight: 700; font-size: 13px; }
.intl-report .tfoot-total { font-size: 14px; color: var(--navy); }
.intl-report .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
.intl-report .empty-state i { font-size: 48px; margin-bottom: 12px; }

@media (max-width: 768px) {
    .intl-report .filter-card, .intl-report .stats-bar, .intl-report .table-wrap, .intl-report .prompt-box { margin-left: 16px; margin-right: 16px; }
    .intl-report .topbar { padding: 14px 16px; }
    #reportTable { font-size: 11px; }
    #reportTable thead th, #reportTable tbody td { padding: 8px; }
}

/* ══════════ PRINT STYLES ══════════ */
#printSection { display: none; }

@media print {
    .intl-report .topbar, .intl-report .filter-card, .intl-report .stats-bar, .intl-report .table-wrap,
    .intl-report .prompt-box, .screen-only,
    .app-header, .nav-tabs, .app-footer, .spinner-overlay { display: none !important; }

    #printSection { display: block !important; }

    body { background: #fff; padding: 0; margin: 0; font-size: 11pt; }
    .intl-report { background: #fff; padding: 0; min-height: 0; }

    .print-letterhead { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 12px; }
    .print-org-name { font-size: 18pt; font-weight: 700; letter-spacing: 0.5px; line-height: 1.3; }
    .print-org-sub  { font-size: 11pt; margin-top: 3px; }
    .print-doc-title { font-size: 14pt; font-weight: 700; text-align: center; margin: 10px 0 4px; text-decoration: underline; text-underline-offset: 4px; }

    #printTable { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-top: 4px; }
    #printTable thead tr { background: #1e3a5f !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    #printTable thead th { padding: 7px 8px; font-size: 8.5pt; font-weight: 700; text-align: center; border: 1px solid #1e3a5f; white-space: nowrap; color: #fff !important; }
    #printTable tbody tr:nth-child(even) { background: #f0f4ff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    #printTable tbody td { padding: 6px 8px; border: 1px solid #ccc; vertical-align: middle; color: #000; }
    #printTable tbody td.tc { text-align: center; }
    #printTable tbody td.tr { text-align: right; }

    .print-grand-total { display: flex; justify-content: flex-end; align-items: center; gap: 40px; border-top: 2px solid #1e3a5f; border-bottom: 2px solid #1e3a5f; padding: 7px 12px; margin-top: 0; font-size: 11pt; font-weight: 700; background: #e8f0fe !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; page-break-inside: avoid; break-inside: avoid; }

    @page { size: A4 landscape; margin: 12mm 10mm; }
}
</style>
@endpush

@section('content')
@use('App\Support\Fmt')
<div class="intl-report">

<!-- Top bar -->
<div class="topbar screen-only">
    <div class="topbar-title">
        <span class="dot"></span>
        International TADA Report
        @if ($filterApplied && $selectedFYName)
            <span style="font-size:13px;font-weight:500;color:var(--navy);margin-left:4px;">
                — आ.व. {{ $selectedFYName }}
            </span>
        @endif
    </div>
    <div class="topbar-actions">
        @if ($filterApplied && $records->isNotEmpty())
        <button class="btn-excel" onclick="exportExcel()">
            <i class="bi bi-file-earmark-excel-fill"></i> Excel
        </button>
        <button class="btn-print" onclick="triggerPrint()">
            <i class="bi bi-printer-fill"></i> Print
        </button>
        @endif
    </div>
</div>

<!-- Filter card -->
<div class="filter-card screen-only">
    <h6><i class="bi bi-funnel-fill"></i> Filter Report</h6>
    <form method="GET" action="{{ route('international.tada.report') }}">
        <input type="hidden" name="filter_applied" value="1">

        <div class="filter-section-title"><i class="bi bi-person-lines-fill"></i> Employee &amp; Fiscal Year</div>
        <div class="filter-grid">
            <div>
                <label>Fiscal Year / आर्थिक वर्ष</label>
                <select name="fy" id="sel-fy" class="select2-filter">
                    <option value="">— All Years —</option>
                    @foreach ($fyList as $fy)
                        <option value="{{ $fy->fiscal_year_master_id }}" {{ $f['fy'] == $fy->fiscal_year_master_id ? 'selected' : '' }}>
                            {{ $fy->fy }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Employee / कर्मचारी</label>
                <select name="emp" id="sel-emp" class="select2-filter">
                    <option value="">— All Employees —</option>
                    @foreach ($empList as $emp)
                        <option value="{{ $emp->EmpPersonalCode }}" {{ $f['emp'] == $emp->EmpPersonalCode ? 'selected' : '' }}>
                            {{ $emp->EmpName }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Designation / पद</label>
                <select name="designation" id="sel-desig" class="select2-filter">
                    <option value="">— All Designations —</option>
                    @foreach ($desigList as $d)
                        <option value="{{ $d->Designation }}" {{ $f['designation'] == $d->Designation ? 'selected' : '' }}>
                            {{ $d->Designation }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="filter-section-title"><i class="bi bi-globe2"></i> Destination</div>
        <div class="filter-grid">
            <div>
                <label>Country / देश</label>
                <select name="country" id="sel-country" class="select2-filter">
                    <option value="">— All Countries —</option>
                    @foreach ($countryList as $c)
                        <option value="{{ $c->Country_id }}" {{ $f['country'] == $c->Country_id ? 'selected' : '' }}>
                            {{ $c->Country_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>City / शहर</label>
                <select name="city" id="sel-city" class="select2-filter">
                    <option value="">— All Cities —</option>
                    @foreach ($cityList as $c)
                        <option value="{{ $c->City_id }}" {{ $f['city'] == $c->City_id ? 'selected' : '' }}>
                            {{ $c->City_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="filter-section-title"><i class="bi bi-sliders"></i> Allowance &amp; Date</div>
        <div class="filter-grid">
            <div>
                <label>Dress Allowance</label>
                <select name="dress" id="sel-dress" class="select2-filter">
                    <option value="">— All —</option>
                    <option value="1" {{ $f['dress'] === '1' ? 'selected' : '' }}>Yes (छ)</option>
                    <option value="0" {{ $f['dress'] === '0' ? 'selected' : '' }}>No (छैन)</option>
                </select>
            </div>
            <div>
                <label>Extra 33% Country</label>
                <select name="extra33" id="sel-extra" class="select2-filter">
                    <option value="">— All —</option>
                    <option value="1" {{ $f['extra33'] === '1' ? 'selected' : '' }}>Yes — Extra 33%</option>
                    <option value="0" {{ $f['extra33'] === '0' ? 'selected' : '' }}>No — Standard</option>
                </select>
            </div>
            <div>
                <label>Travel From (AD)</label>
                <input type="date" name="date_from" class="filter-native" value="{{ $f['date_from'] }}">
            </div>
            <div>
                <label>Travel To (AD)</label>
                <input type="date" name="date_to" class="filter-native" value="{{ $f['date_to'] }}">
            </div>
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn-navy">
                <i class="bi bi-search"></i> Apply Filter
            </button>
            @if ($filterApplied)
                <a href="{{ route('international.tada.report') }}" class="btn-outline"><i class="bi bi-x-circle"></i> Clear All</a>
            @endif
        </div>
    </form>
</div>

@if (! $filterApplied)
<div class="prompt-box screen-only">
    <i class="bi bi-airplane"></i>
    <p>Please select filter options above and click <strong>Apply Filter</strong> to load the report.</p>
</div>

@else
@php
    $batchCount = $records->pluck('Batch_id')->unique()->count();
    $empCount = $records->pluck('EmpPersonalCode')->unique()->count();
    $countryCount = $records->pluck('Country_name')->filter()->unique()->count();
@endphp

<!-- Stats -->
<div class="stats-bar screen-only">
    <div class="stat-pill">
        <span class="val">{{ $records->count() }}</span>
        <span class="lbl">Total Records</span>
    </div>
    <div class="stat-pill">
        <span class="val">{{ $batchCount }}</span>
        <span class="lbl">Batches</span>
    </div>
    <div class="stat-pill">
        <span class="val">{{ $empCount }}</span>
        <span class="lbl">Employees</span>
    </div>
    <div class="stat-pill">
        <span class="val">{{ $countryCount }}</span>
        <span class="lbl">Countries</span>
    </div>
    <div class="stat-pill gold">
        <span class="val" style="font-size:16px;">$ {{ number_format($grandTotalUSD, 2) }}</span>
        <span class="lbl">Grand Total USD</span>
    </div>
    <div class="stat-pill">
        <span class="val" style="font-size:16px;">{{ $selectedFYName ?: 'All' }}</span>
        <span class="lbl">Fiscal Year</span>
    </div>
</div>

<!-- Screen table -->
<div class="table-wrap screen-only">
<div style="overflow-x:auto;">
<table id="reportTable">
    <thead>
        <tr>
            <th>SN</th>
            <th>Batch ID</th>
            <th>Chalani No</th>
            <th>Employee</th>
            <th>Designation</th>
            <th>Country</th>
            <th>City</th>
            <th>Travel Objective</th>
            <th>Travel Start (BS)</th>
            <th>Travel End (BS)</th>
            <th>Days</th>
            <th>USD Rate</th>
            <th>Total USD ($)</th>
            <th>Dress Allow.</th>
            <th>33% Extra</th>
        </tr>
    </thead>
    <tbody>
    @if ($records->isEmpty())
        <tr><td colspan="15"><div class="empty-state"><i class="bi bi-inbox d-block"></i>No records found.</div></td></tr>
    @else
        @foreach ($records as $row)
        @php
            $startAD     = Fmt::date($row->travelDateStart);
            $endAD       = Fmt::date($row->travelDateEnd);
            $tadaRate    = (float) ($row->tadaInUSD ?? 0);
            $is33        = (int) ($row->extra33percent_country ?? 0);
            $displayRate = $is33 ? $tadaRate * 1.33 : $tadaRate;
            $totalUSD    = (float) ($row->totalUSdrecevid ?? $row->tadaInUSD_Calc ?? 0);
            $desigNep    = $row->designationTypeInNepali ?? '-';
            $isDress     = (int) ($row->DressAllowance ?? 0);
        @endphp
        <tr>
            <td class="text-muted" style="font-size:11px;">{{ $loop->iteration }}</td>
            <td style="font-weight:700;color:var(--navy);">{{ $row->Batch_id }}</td>
            <td style="font-size:12px;">{{ ($row->fiscal_year ?? '').'-'.($row->Chalani_id ?? '') }}</td>
            <td class="td-emp">{{ $row->EmpNameInNepali ?? $row->EmpName ?? $row->EmpPersonalCode }}</td>
            <td style="font-size:12px;">{{ $desigNep }}</td>
            <td><i class="bi bi-globe2" style="color:var(--navy);margin-right:4px;"></i>{{ $row->Country_name ?? '-' }}</td>
            <td>{{ $row->City_name ?? '-' }}</td>
            <td style="max-width:180px;font-size:12px;">{{ $row->travel_objective ?? '' }}</td>
            <td class="td-days"><span class="bs-date" data-ad="{{ $startAD }}">{{ $startAD }}</span></td>
            <td class="td-days"><span class="bs-date" data-ad="{{ $endAD }}">{{ $endAD }}</span></td>
            <td class="td-days"><strong>{{ Fmt::num($row->totalday) }}</strong></td>
            <td class="td-usd">{{ Fmt::num($displayRate) }}</td>
            <td class="td-usd">$ {{ Fmt::num($totalUSD) }}</td>
            <td class="text-center"><span class="{{ $isDress ? 'badge-yes' : 'badge-no' }}">{{ $isDress ? 'छ' : 'छैन' }}</span></td>
            <td class="text-center"><span class="{{ $is33 ? 'badge-extra' : 'badge-no' }}">{{ $is33 ? '+33%' : 'Standard' }}</span></td>
        </tr>
        @endforeach
    @endif
    </tbody>
    @if ($records->isNotEmpty())
    <tfoot>
        <tr>
            <td colspan="12" class="text-end" style="font-size:12px;font-weight:700;">जम्मा (Grand Total)</td>
            <td class="tfoot-total">$ {{ number_format($grandTotalUSD, 2) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
</div><!-- /table-wrap -->


<!-- PRINT SECTION -->
<div id="printSection">

    <div class="print-letterhead">
        <div class="print-org-sub">नेपाल धितोपत्र बोर्ड</div>
        <div class="print-org-sub">खुमत्लर, ललितपुर</div>
    </div>

    <div class="print-doc-title">विदेश भ्रमण भत्ता (International TADA) विवरण</div>

    <table id="printTable">
        <thead>
            <tr>
                <th style="width:28px;">सि.नं.</th>
                <th>चलानी नं.</th>
                <th>कर्मचारीको नाम</th>
                <th>पद</th>
                <th>देश</th>
                <th>भ्रमणको उद्देश्य</th>
                <th>भ्रमण सुरु</th>
                <th>भ्रमण अन्त्य</th>
                <th style="width:34px;">दिन</th>
                <th>दर ($)</th>
                <th>जम्मा ($)</th>
                <th style="width:44px;">पोशाक</th>
                <th style="width:50px;">३३% थप</th>
            </tr>
        </thead>
        <tbody>
        @if ($records->isEmpty())
            <tr><td colspan="13" style="text-align:center;padding:20px;">कुनै रेकर्ड भेटिएन।</td></tr>
        @else
            @foreach ($records as $row)
            @php
                $startAD2     = Fmt::date($row->travelDateStart);
                $endAD2       = Fmt::date($row->travelDateEnd);
                $tadaRate2    = (float) ($row->tadaInUSD ?? 0);
                $is332        = (int) ($row->extra33percent_country ?? 0);
                $displayRate2 = $is332 ? $tadaRate2 * 1.33 : $tadaRate2;
                $totalUSD2    = (float) ($row->totalUSdrecevid ?? $row->tadaInUSD_Calc ?? 0);
                $desigNep2    = $row->designationTypeInNepali ?? '-';
                $isDress2     = (int) ($row->DressAllowance ?? 0);
            @endphp
            <tr>
                <td class="tc">{{ $loop->iteration }}</td>
                <td class="tc">{{ ($row->fiscal_year ?? '').'-'.($row->Chalani_id ?? '') }}</td>
                <td style="font-weight:600;">{{ $row->EmpNameInNepali ?? $row->EmpName ?? $row->EmpPersonalCode }}</td>
                <td>{{ $desigNep2 }}</td>
                <td>{{ $row->Country_name ?? '-' }}</td>
                <td style="max-width:130px;">{{ $row->travel_objective ?? '' }}</td>
                <td class="tc"><span class="bs-date-print" data-ad="{{ $startAD2 }}">{{ $startAD2 }}</span></td>
                <td class="tc"><span class="bs-date-print" data-ad="{{ $endAD2 }}">{{ $endAD2 }}</span></td>
                <td class="tc"><strong>{{ Fmt::num($row->totalday) }}</strong></td>
                <td class="tr">{{ Fmt::num($displayRate2) }}</td>
                <td class="tr">{{ Fmt::num($totalUSD2) }}</td>
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
</div><!-- /intl-report -->
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    $('.select2-filter').select2({ placeholder: 'Search...', allowClear: true, width: '100%' });
});

/* ── BS date conversion ── */
window.addEventListener('load', function () {
    setTimeout(function () {
        document.querySelectorAll('.bs-date').forEach(function (el) {
            var ad = el.getAttribute('data-ad');
            if (!ad) return;
            try {
                var bs = NepaliFunctions.AD2BS(ad, "YYYY-MM-DD", "YYYY/MM/DD");
                el.textContent = bs;
                el.setAttribute('data-bs-val', bs);
            } catch(e) {}
        });
        document.querySelectorAll('.bs-date-print').forEach(function (el) {
            var ad = el.getAttribute('data-ad');
            if (!ad) return;
            try {
                var bs = NepaliFunctions.AD2BS(ad, "YYYY-MM-DD", "YYYY/MM/DD");
                el.textContent = bs;
            } catch(e) {}
        });
    }, 450);
});

/* ── Print ── */
function triggerPrint() { window.print(); }

/* ── Excel export ── */
function exportExcel() {
    var table = document.getElementById('reportTable');
    var fyName = @json($selectedFYName);
    var grandTotal = @json('$ '.number_format($grandTotalUSD, 2));
    var rows  = [];

    rows.push(['विदेश भ्रमण भत्ता (International TADA) विवरण']);
    rows.push(['आर्थिक वर्ष:', fyName, '', 'Grand Total USD:', grandTotal]);
    rows.push(['मुद्रण मिति:', new Date().toLocaleDateString()]);
    rows.push([]);

    var headers = [];
    table.querySelectorAll('thead th').forEach(function(th){ headers.push(th.innerText.trim()); });
    rows.push(headers);

    table.querySelectorAll('tbody tr').forEach(function(tr) {
        if (tr.querySelector('.empty-state')) return;
        var row = [];
        tr.querySelectorAll('td').forEach(function(td) {
            var bsEl = td.querySelector('.bs-date');
            row.push(bsEl ? (bsEl.getAttribute('data-bs-val') || bsEl.innerText.trim()) : td.innerText.trim());
        });
        rows.push(row);
    });

    var tfoot = table.querySelector('tfoot tr');
    if (tfoot) {
        var fRow = new Array(headers.length).fill('');
        fRow[11] = 'जम्मा (Grand Total)';
        var fc   = tfoot.querySelectorAll('td');
        fRow[12] = fc[1] ? fc[1].innerText.trim() : '';
        rows.push(fRow);
    }

    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [4,10,14,22,18,16,14,28,13,13,5,12,14,9,10].map(function(w){ return {wch:w}; });
    ws['!merges'] = [{ s:{r:0,c:0}, e:{r:0,c:14} }];

    XLSX.utils.book_append_sheet(wb, ws, 'International TADA');
    XLSX.writeFile(wb, 'IntlTADA_' + fyName + '_' + new Date().toISOString().slice(0,10) + '.xlsx');
}
</script>
@endpush
