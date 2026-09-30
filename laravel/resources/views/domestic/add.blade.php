@extends('layouts.app')

@section('title', 'Add Domestic TADA Batch')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #10B981 0%, #059669 100%); padding: 20px; min-height: 100vh; }
    .form-container { max-width: 1100px; margin: 20px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .form-container h2 { color: #065F46; margin-bottom: 25px; text-align: center; font-size: 28px; }
    .info-badges { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
    .info-badge { background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%); color: #065F46; padding: 12px 15px; border-radius: 8px; font-weight: 600; text-align: center; border: 2px solid #6EE7B7; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group.full-width { grid-column: 1 / -1; }
    .form-container label { display: block; margin-bottom: 8px; color: #1F2937; font-weight: 600; font-size: 14px; }
    label .required { color: #EF4444; }
    .form-container input[type="text"], .form-container input[type="date"], .form-container select, .form-container textarea { width: 100%; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; transition: border-color 0.3s, box-shadow 0.3s; box-sizing: border-box; }
    .form-container input:focus, .form-container select:focus, .form-container textarea:focus { outline: none; border-color: #10B981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
    .form-container textarea { resize: vertical; min-height: 90px; font-family: inherit; }
    .select2-container--default .select2-selection--single { border: 2px solid #e0e0e0; border-radius: 6px; height: 44px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 12px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
    .select2-container--default.select2-container--focus .select2-selection--single { border-color: #10B981; }
    .select2-container { width: 100% !important; }
    .checkbox-container { display: flex; align-items: center; gap: 10px; padding: 12px; background: #F3F4F6; border-radius: 6px; border: 2px solid #E5E7EB; cursor: pointer; transition: all 0.3s; }
    .checkbox-container:hover { background: #E5E7EB; border-color: #10B981; }
    .checkbox-container input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; accent-color: #10B981; }
    .checkbox-container label { margin: 0; cursor: pointer; flex: 1; }
    .employee-section { background: #F9FAFB; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px dashed #D1D5DB; }
    .employee-selector { display: flex; gap: 10px; margin-bottom: 15px; align-items: stretch; }
    .employee-selector select { flex: 1; }
    .btn-add-employee { background: #10B981; color: white; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: background 0.3s, transform 0.1s; }
    .btn-add-employee:hover { background: #059669; transform: translateY(-1px); }
    .employee-table-container { overflow-x: auto; margin-top: 15px; border-radius: 8px; border: 1px solid #E5E7EB; }
    .employee-table { width: 100%; border-collapse: collapse; background: white; }
    .employee-table thead { background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; }
    .employee-table th { padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .employee-table td { padding: 14px 12px; border-bottom: 1px solid #E5E7EB; font-size: 14px; }
    .employee-table tbody tr:hover { background: #F3F4F6; }
    .btn-remove { background: #EF4444; color: white; border: none; padding: 6px 14px; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: 600; transition: background 0.3s; }
    .btn-remove:hover { background: #DC2626; }
    .empty-state { text-align: center; padding: 40px 20px; color: #6B7280; }
    .empty-state-icon { font-size: 48px; margin-bottom: 10px; }
    .form-container .btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s; }
    .form-container .btn-primary { background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; width: 100%; padding: 14px; font-size: 16px; }
    .form-container .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); }
    .form-container .btn-primary:disabled { background: #9CA3AF; cursor: not-allowed; opacity: 0.6; }
    .form-container .btn-secondary { background-color: #6B7280; color: white; }
    .form-container .btn-secondary:hover { background-color: #4B5563; }
    .form-actions { margin-top: 30px; display: flex; gap: 12px; }
    .form-actions .btn-secondary { flex: 0 0 120px; }
    .form-actions .btn-primary { flex: 1; }
</style>
@endpush

@section('content')
<div class="form-container">
    <h2>➕ Add Domestic TADA Batch</h2>

    <div class="info-badges">
        <div class="info-badge">
            📋 Next Batch ID: <strong>{{ $nextBatch }}</strong>
        </div>
        <div class="info-badge">
            🔢 Next Chalani #: <strong>{{ $nextChalani }}</strong>
        </div>
    </div>

    <form method="POST" action="{{ route('domestic.store') }}" id="tadaForm">
        @csrf

        <!-- Row 1: Form Date + District -->
        <div class="form-row">
            <div class="form-group">
                <label>Form Date <span class="required">*</span></label>
                <input type="date" name="form_date" required value="{{ old('form_date', date('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label>District <span class="required">*</span></label>
                <select name="district_id" id="districtSelect" class="select2-single" required>
                    <option value="">-- Select District --</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->District_id }}" @selected(old('district_id') == $district->District_id)>{{ $district->District_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Row 2: TADA Type + 20% Extra -->
        <div class="form-row">
            <div class="form-group">
                <label>TADA Type <span class="required">*</span></label>
                <select name="tada_type_id" id="tadaTypeSelect" class="select2-single" required>
                    <option value="">-- Select TADA Type --</option>
                    @foreach ($tadaTypes as $tadaType)
                        <option value="{{ $tadaType->TadaTypeMaster_id }}" @selected(old('tada_type_id') == $tadaType->TadaTypeMaster_id)>{{ $tadaType->type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="checkbox-container">
                    <input type="checkbox" name="is_twenty_percent_extra" id="twentyPercentExtra" @checked(old('is_twenty_percent_extra'))>
                    <label for="twentyPercentExtra">Add 20% Extra TADA</label>
                </div>
            </div>
        </div>

        <!-- Row 3: TADA Verifier -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>भ्रमण आदेश दिने अधिकारी <span class="required">*</span></label>
                <select name="tadaverifier_id" id="tadaVerifierSelect" class="select2-single" required>
                    <option value="">-- Select TADA Verifier --</option>
                    @foreach ($verifiers as $verifier)
                        <option value="{{ (int) $verifier->tadaverifier_id }}" @selected(old('tadaverifier_id') == $verifier->tadaverifier_id)>{{ $verifier->tadaverifierPost }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Travel Objective -->
        <div class="form-group full-width">
            <label>Travel Objective <span class="required">*</span></label>
            <textarea name="travel_objective" required placeholder="Enter the purpose of domestic travel...">{{ old('travel_objective') }}</textarea>
        </div>

        <!-- Travel Dates -->
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

        <!-- Employees -->
        <div class="form-group full-width">
            <label>Add Employees <span class="required">*</span></label>
            <div class="employee-section">
                <div class="employee-selector">
                    <select id="employeeDropdown" class="select2-single">
                        <option value="">-- Search and Select Employee --</option>
                        @foreach ($employees as $emp)
                            @php
                                $levelDisplay = ! empty($emp->LevelName) ? $emp->LevelName : 'No Level';
                                $nepaliDisplay = (float) $emp->tadaInNepali;
                            @endphp
                            <option value="{{ $emp->EmpPersonalCode }}"
                                    data-name="{{ $emp->EmpName }}"
                                    data-level="{{ $levelDisplay }}"
                                    data-nepali="{{ $nepaliDisplay }}"
                                    data-tada-id="{{ $emp->DomesticTadaDefinerMasterBylevel_id }}">
                                {{ $emp->EmpName }} - {{ $levelDisplay }}
                                (NPR {{ number_format($nepaliDisplay, 2) }}/day)
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
                                <th>NPR/Day</th>
                                <th style="width: 100px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <tr class="empty-state">
                                <td colspan="6">
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
            <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('domestic.index') }}'">
                ← Cancel
            </button>
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                💾 Create Batch
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    let addedEmployees = [];

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    $('#districtSelect, #employeeDropdown, #tadaTypeSelect, #tadaVerifierSelect').select2({
        placeholder: "Select...",
        allowClear: true,
        width: '100%'
    });

    $('#btnAddEmployee').on('click', function () {
        const selectedOption = $('#employeeDropdown option:selected');
        const empCode = selectedOption.val();

        if (!empCode) { alert('⚠️ Please select an employee first!'); return; }
        if (addedEmployees.some(emp => emp.code === empCode)) {
            alert('⚠️ This employee is already added!');
            return;
        }

        addedEmployees.push({
            code:   empCode,
            name:   selectedOption.data('name'),
            level:  selectedOption.data('level'),
            nepali: selectedOption.data('nepali'),
            tadaId: selectedOption.data('tada-id')
        });

        renderEmployeeTable();
        $('#employeeDropdown').val('').trigger('change');
        updateSubmitButton();
    });

    // Restore employees after a validation failure
    @if (old('employee_data'))
    @foreach (array_filter(explode(',', old('employee_data'))) as $oldCode)
    (function () {
        const opt = $('#employeeDropdown option').filter(function () { return $(this).val() === @json(trim($oldCode)); });
        if (opt.length) {
            addedEmployees.push({
                code: opt.val(), name: opt.data('name'), level: opt.data('level'),
                nepali: opt.data('nepali'), tadaId: opt.data('tada-id')
            });
        }
    })();
    @endforeach
    @endif

    function renderEmployeeTable() {
        const tbody = $('#employeeTableBody');
        tbody.empty();

        if (addedEmployees.length === 0) {
            tbody.html(`
                <tr class="empty-state">
                    <td colspan="6">
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
                        <td>NPR ${parseFloat(emp.nepali).toFixed(2)}</td>
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

    $(document).on('click', '.btn-remove', function () {
        const index = $(this).data('index');
        if (confirm('Are you sure you want to remove this employee?')) {
            addedEmployees.splice(index, 1);
            renderEmployeeTable();
            updateSubmitButton();
        }
    });

    function updateHiddenInput() {
        $('#employeeData').val(addedEmployees.map(emp => emp.code).join(','));
    }

    function updateSubmitButton() {
        $('#submitBtn').prop('disabled', addedEmployees.length === 0);
    }

    renderEmployeeTable();
    updateSubmitButton();

    $('#startDate, #endDate').on('change', function () {
        const startDate = $('#startDate').val();
        const endDate   = $('#endDate').val();
        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
            alert('⚠️ End date cannot be before start date!');
            $('#endDate').val('');
        }
    });

    $('#tadaForm').on('submit', function (e) {
        if (addedEmployees.length === 0) {
            e.preventDefault();
            alert('⚠️ Please add at least one employee!');
            return false;
        }
        $('#submitBtn').prop('disabled', true).html('⏳ Creating Batch...');
    });
});
</script>
@endpush
