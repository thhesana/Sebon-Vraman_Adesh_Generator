{{-- Report filter card shared by the detailed and the summarized international reports.
     Expects: $filterAction (form URL), $showClear, optional $extraHidden [name => value], plus the controller's filter lists, $f and $dateErrors. --}}
<!-- Filter card -->
<div class="card mb-3 screen-only">
    <div class="card-header">Filter Report</div>
    <form method="GET" action="{{ $filterAction }}">
        @foreach ($extraHidden ?? [] as $hiddenName => $hiddenValue)
            <input type="hidden" name="{{ $hiddenName }}" value="{{ $hiddenValue }}">
        @endforeach
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
                    <label class="form-label" for="date_from">Travel From (BS)</label>
                    <input type="text" name="date_from" id="date_from" class="form-control bs-filter-date @if ($dateErrors['date_from'] ?? false) is-invalid @endif"
                           value="{{ $f['date_from'] }}" placeholder="YYYY-MM-DD" autocomplete="off">
                    @if ($dateErrors['date_from'] ?? false)
                        <div class="invalid-feedback">Use a BS date like 2083-07-20.</div>
                    @endif
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label" for="date_to">Travel To (BS)</label>
                    <input type="text" name="date_to" id="date_to" class="form-control bs-filter-date @if ($dateErrors['date_to'] ?? false) is-invalid @endif"
                           value="{{ $f['date_to'] }}" placeholder="YYYY-MM-DD" autocomplete="off">
                    @if ($dateErrors['date_to'] ?? false)
                        <div class="invalid-feedback">Use a BS date like 2083-07-20.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
            @if ($showClear)
                <a href="{{ $filterAction }}" class="btn btn-secondary">Clear All</a>
            @endif
            <button type="submit" class="btn btn-primary">Apply Filter</button>
        </div>
    </form>
</div>
