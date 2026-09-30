@extends('layouts.app')

@section('title', 'Add International TADA Batch')

@push('styles')
@include('international._form_styles')
@endpush

@section('content')
<div class="intl-form-page">
<div class="form-container">
    <h2>➕ Add International TADA Batch</h2>

    <div class="info-badges">
        <div class="info-badge">
            📋 Next Batch ID: <strong>{{ $nextBatch }}</strong>
        </div>
        <div class="info-badge">
            🔢 Next Chalani #: <strong>{{ $nextChalani }}</strong>
        </div>
    </div>

    <form method="POST" action="{{ route('international.store') }}" id="tadaForm">
        @csrf

        <div class="form-row">
            <div class="form-group">
                <label>Form Date <span class="required">*</span></label>
                <input type="date" name="form_date" required value="{{ old('form_date', date('Y-m-d')) }}">
            </div>
            <div class="form-group"><!-- alignment spacer --></div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Country <span class="required">*</span></label>
                <select name="country_id" id="countrySelect" class="select2-single" required>
                    <option value="">-- Select Country --</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->Country_id }}" {{ (string) old('country_id') === (string) $country->Country_id ? 'selected' : '' }}>
                            {{ $country->Country_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>City <span class="required">*</span>
                    <span class="loading" id="cityLoading" style="display:none;">⏳ Loading...</span>
                </label>
                <select name="city_id" id="citySelect" class="select2-single" required disabled>
                    <option value="">-- Select Country First --</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group full-width">
                <label>TADA Verifier <span class="required">*</span></label>
                <select name="tadaverifier_id" id="tadaVerifierSelect" class="select2-single" required>
                    <option value="">-- Select TADA Verifier --</option>
                    @foreach ($verifiers as $verifier)
                        <option value="{{ (int) $verifier->tadaverifier_id }}" {{ (string) old('tadaverifier_id') === (string) $verifier->tadaverifier_id ? 'selected' : '' }}>
                            {{ $verifier->tadaverifierPost }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group full-width">
            <label>Travel Objective <span class="required">*</span></label>
            <textarea name="travel_objective" required placeholder="Enter the purpose of international travel...">{{ old('travel_objective') }}</textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Travel Start Date <span class="required">*</span></label>
                <input type="date" name="travelDateStart" id="startDate" required value="{{ old('travelDateStart') }}">
            </div>
            <div class="form-group">
                <label>Travel End Date <span class="required">*</span></label>
                <input type="date" name="travelDateEnd" id="endDate" required value="{{ old('travelDateEnd') }}">
            </div>
        </div>

        <div class="form-group full-width">
            <label>Add Employees <span class="required">*</span></label>
            <div class="employee-section">
                <div class="employee-selector">
                    <select id="employeeDropdown" class="select2-single">
                        <option value="">-- Search and Select Employee --</option>
                        @foreach ($employees as $emp)
                            @php
                                $levelDisplay = ! empty($emp->LevelName) ? $emp->LevelName : 'No TADA Level';
                                $usdDisplay = ! empty($emp->tadaInUSD) ? $emp->tadaInUSD : 0;
                            @endphp
                            <option value="{{ $emp->EmpPersonalCode }}"
                                    data-name="{{ $emp->EmpName }}"
                                    data-level="{{ $levelDisplay }}"
                                    data-usd="{{ $usdDisplay }}"
                                    data-tada-id="{{ $emp->TadaDefinerMasterBylevel_id ?? '' }}">
                                {{ $emp->EmpName }} - {{ $levelDisplay }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="btn-add-employee" id="btnAddEmployee">
                        ➕ Add Employee
                    </button>
                </div>

                <div class="employee-table-container">
                    <table class="employee-table" id="employeeTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Employee Name</th>
                                <th>Personal Code</th>
                                <th>TADA Level</th>
                                <th>USD/Day</th>
                                <th style="width: 150px; text-align: center;">Dress Allowance</th>
                                <th style="width: 100px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <tr class="empty-state">
                                <td colspan="7">
                                    <div class="empty-state-icon">👥</div>
                                    <div>No employees added yet. Select from dropdown above.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <input type="hidden" name="employee_data" id="employeeData" value="">

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('international.index') }}'">
                ← Cancel
            </button>
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                💾 Create Batch ({{ $nextBatch }} - Chalani #{{ $nextChalani }})
            </button>
        </div>
    </form>
</div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    let addedEmployees = [];
    // Values to restore after a failed submit (old input)
    const oldCityId = @json((string) old('city_id', ''));
    const oldEmployeeData = @json((string) old('employee_data', ''));

    function esc(v) {
        return $('<div>').text(v === undefined || v === null ? '' : v).html();
    }

    // ── Select2 init ──────────────────────────────────────────────────────────
    $('#countrySelect').select2({ placeholder: "Select a country",          allowClear: true, width: '100%' });
    $('#citySelect').select2({ placeholder: "Select country first",          allowClear: true, width: '100%' });
    $('#tadaVerifierSelect').select2({ placeholder: "Select TADA Verifier", allowClear: true, width: '100%' });
    $('#employeeDropdown').select2({ placeholder: "Search and select employee by name...", allowClear: true, width: '100%' });

    // ── Country → City AJAX ───────────────────────────────────────────────────
    $('#countrySelect').on('change', function () {
        const countryId  = $(this).val();
        const citySelect = $('#citySelect');
        const cityLoading = $('#cityLoading');

        if (countryId) {
            cityLoading.show();
            citySelect.prop('disabled', true);
            citySelect.empty().append('<option value="">Loading cities...</option>');

            $.ajax({
                url: @json(route('countries.cities', ['country' => '__ID__'])).replace('__ID__', countryId),
                type: 'GET',
                dataType: 'json',
                cache: false,
                success: function (response) {
                    citySelect.empty();
                    citySelect.append('<option value="">-- Select City --</option>');

                    if (response.success && response.data && response.data.length > 0) {
                        $.each(response.data, function (index, city) {
                            citySelect.append($('<option></option>').val(city.City_id).text(city.City_name));
                        });
                        citySelect.prop('disabled', false);
                        if (oldCityId) { citySelect.val(oldCityId); }
                    } else {
                        citySelect.append('<option value="">No cities available</option>');
                    }

                    citySelect.select2('destroy').select2({ placeholder: "Select a city", allowClear: true, width: '100%' });
                    cityLoading.hide();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('⚠️ Error loading cities. Please try again.');
                    citySelect.empty().append('<option value="">-- Error Loading Cities --</option>');
                    citySelect.prop('disabled', false);
                    cityLoading.hide();
                }
            });
        } else {
            citySelect.empty().append('<option value="">-- Select Country First --</option>');
            citySelect.prop('disabled', true);
            citySelect.select2('destroy').select2({ placeholder: "Select country first", allowClear: true, width: '100%' });
        }
    });

    // ── Add Employee ──────────────────────────────────────────────────────────
    $('#btnAddEmployee').on('click', function () {
        const selectedOption = $('#employeeDropdown option:selected');
        const empCode = selectedOption.val();

        if (!empCode) { alert('⚠️ Please select an employee first!'); return; }
        if (addedEmployees.some(emp => emp.code === empCode)) { alert('⚠️ This employee is already added!'); return; }

        addedEmployees.push({
            code:          empCode,
            name:          selectedOption.data('name'),
            level:         selectedOption.data('level'),
            usd:           selectedOption.data('usd'),
            tadaId:        selectedOption.data('tada-id'),
            dressAllowance: false
        });

        renderEmployeeTable();
        $('#employeeDropdown').val('').trigger('change');
        updateSubmitButton();
    });

    // ── Render Employee Table ─────────────────────────────────────────────────
    function renderEmployeeTable() {
        const tbody = $('#employeeTableBody');
        tbody.empty();

        if (addedEmployees.length === 0) {
            tbody.html(`
                <tr class="empty-state">
                    <td colspan="7">
                        <div class="empty-state-icon">👥</div>
                        <div>No employees added yet. Select from dropdown above.</div>
                    </td>
                </tr>`);
        } else {
            addedEmployees.forEach((emp, index) => {
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${esc(emp.name)}</strong></td>
                        <td>${esc(emp.code)}</td>
                        <td>${esc(emp.level)}</td>
                        <td>${parseFloat(emp.usd).toFixed(2)}</td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="dress-allowance-check"
                                   data-index="${index}"
                                   ${emp.dressAllowance ? 'checked' : ''}
                                   style="cursor: pointer; width: 18px; height: 18px;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-remove" data-index="${index}">
                                🗑️ Remove
                            </button>
                        </td>
                    </tr>`);
            });
        }
        updateHiddenInput();
    }

    // ── Dress Allowance toggle ────────────────────────────────────────────────
    $(document).on('change', '.dress-allowance-check', function () {
        addedEmployees[$(this).data('index')].dressAllowance = $(this).is(':checked');
        updateHiddenInput();
    });

    // ── Remove Employee ───────────────────────────────────────────────────────
    $(document).on('click', '.btn-remove', function () {
        if (confirm('Are you sure you want to remove this employee?')) {
            addedEmployees.splice($(this).data('index'), 1);
            renderEmployeeTable();
            updateSubmitButton();
        }
    });

    function updateHiddenInput() {
        $('#employeeData').val(addedEmployees.map(emp => `${emp.code}:${emp.dressAllowance ? 1 : 0}`).join(','));
    }

    function updateSubmitButton() {
        $('#submitBtn').prop('disabled', addedEmployees.length === 0);
    }

    // ── Date validation ───────────────────────────────────────────────────────
    $('#startDate, #endDate').on('change', function () {
        const s = $('#startDate').val(), e = $('#endDate').val();
        if (s && e && new Date(e) < new Date(s)) {
            alert('⚠️ End date cannot be before start date!');
            $('#endDate').val('');
        }
    });

    // ── Form submit guard ─────────────────────────────────────────────────────
    $('#tadaForm').on('submit', function (e) {
        if (addedEmployees.length === 0) {
            e.preventDefault();
            alert('⚠️ Please add at least one employee before submitting!');
            return false;
        }
        $('#submitBtn').prop('disabled', true).html('⏳ Creating Batch...');
    });

    // ── Restore state after a failed submit ───────────────────────────────────
    if (oldEmployeeData) {
        oldEmployeeData.split(',').forEach(function (entry) {
            const parts = entry.split(':');
            if (parts.length !== 2) return;
            const opt = $('#employeeDropdown option').filter(function () { return this.value === parts[0].trim(); });
            if (!opt.length) return;
            addedEmployees.push({
                code: parts[0].trim(), name: opt.data('name'), level: opt.data('level'),
                usd: opt.data('usd'), tadaId: opt.data('tada-id'), dressAllowance: parts[1] === '1'
            });
        });
        renderEmployeeTable();
        updateSubmitButton();
    }
    if ($('#countrySelect').val()) { $('#countrySelect').trigger('change'); }
});
</script>
@endpush
