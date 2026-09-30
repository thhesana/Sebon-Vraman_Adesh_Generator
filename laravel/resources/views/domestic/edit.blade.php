@extends('layouts.app')

@section('title', 'Edit Domestic TADA Batch')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
    .form-container { max-width: 1100px; margin: 20px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .form-container h2 { color: #1E3A8A; margin-bottom: 25px; text-align: center; font-size: 28px; }
    .info-badge { background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); color: #92400E; padding: 12px 15px; border-radius: 8px; font-weight: 600; text-align: center; border: 2px solid #FCD34D; margin-bottom: 25px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group.full-width { grid-column: 1 / -1; }
    .form-container label { display: block; margin-bottom: 8px; color: #1F2937; font-weight: 600; font-size: 14px; }
    label .required { color: #EF4444; }
    .form-container input[type="text"], .form-container input[type="date"], .form-container input[type="number"], .form-container select, .form-container textarea { width: 100%; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; transition: border-color 0.3s, box-shadow 0.3s; box-sizing: border-box; }
    .form-container input:focus, .form-container select:focus, .form-container textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .form-container textarea { resize: vertical; min-height: 90px; font-family: inherit; }
    .select2-container--default .select2-selection--single { border: 2px solid #e0e0e0; border-radius: 6px; height: 44px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 12px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
    .select2-container--default.select2-container--focus .select2-selection--single { border-color: #667eea; }
    .select2-container { width: 100% !important; }
    .employee-section { background: #F9FAFB; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px dashed #D1D5DB; }
    .employee-selector { display: flex; gap: 10px; margin-bottom: 15px; align-items: stretch; }
    .employee-selector select { flex: 1; }
    .btn-add-employee { background: #10B981; color: white; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: background 0.3s, transform 0.1s; }
    .btn-add-employee:hover { background: #059669; transform: translateY(-1px); }
    .employee-table-container { overflow-x: auto; margin-top: 15px; border-radius: 8px; border: 1px solid #E5E7EB; }
    .employee-table { width: 100%; border-collapse: collapse; background: white; }
    .employee-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .employee-table th { padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .employee-table td { padding: 14px 12px; border-bottom: 1px solid #E5E7EB; font-size: 14px; }
    .employee-table tbody tr:hover { background: #F3F4F6; }
    .employee-table tbody tr:last-child td { border-bottom: none; }
    .btn-remove { background: #EF4444; color: white; border: none; padding: 6px 14px; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: 600; transition: background 0.3s; }
    .btn-remove:hover { background: #DC2626; }
    .empty-state { text-align: center; padding: 40px 20px; color: #6B7280; }
    .empty-state-icon { font-size: 48px; margin-bottom: 10px; }
    .form-container .btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s; }
    .form-container .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 100%; padding: 14px; font-size: 16px; }
    .form-container .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
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
    <h2>✏️ Edit Domestic TADA Batch</h2>

    @include('domestic._errors')

    <div class="info-badge">
        📦 Batch ID: <strong>{{ $batchId }}</strong>
    </div>

    <form method="POST" action="{{ url('/EditDomesticTada.php') }}?batch_id={{ urlencode($batchId) }}" id="tadaForm">
        @csrf

        <!-- Row 1: Form Date + TADA Type -->
        <div class="form-row">
            <div class="form-group">
                <label>Form Date <span class="required">*</span></label>
                <input type="date" name="form_date" required
                       value="{{ \Carbon\Carbon::parse($batch->domestic_form_date)->format('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label>TADA Type <span class="required">*</span></label>
                <select name="tada_type_id" id="tadaTypeSelect" class="select2-single" required>
                    <option value="">-- Select TADA Type --</option>
                    @foreach ($tadaTypes as $tadaType)
                        <option value="{{ $tadaType->TadaTypeMaster_id }}" @selected($tadaType->TadaTypeMaster_id == $batch->TadaTypeMaster_id)>{{ $tadaType->type }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Row 2: District + spacer -->
        <div class="form-row">
            <div class="form-group">
                <label>District <span class="required">*</span></label>
                <select name="district_id" id="districtSelect" class="select2-single" required>
                    <option value="">-- Select District --</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->District_id }}" @selected($district->District_id == $batch->District_id)>{{ $district->District_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><!-- alignment spacer --></div>
        </div>

        <!-- Row 3: TADA Verifier -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>TADA Verifier <span class="required">*</span></label>
                <select name="tadaverifier_id" id="tadaVerifierSelect" class="select2-single" required>
                    <option value="">-- Select TADA Verifier --</option>
                    @foreach ($verifiers as $verifier)
                        <option value="{{ (int) $verifier->tadaverifier_id }}" @selected($verifier->tadaverifier_id == $batch->tadaverifier_id)>{{ $verifier->tadaverifierPost }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Travel Objective -->
        <div class="form-group full-width">
            <label>Travel Objective <span class="required">*</span></label>
            <textarea name="travel_objective" required
                      placeholder="Enter the purpose of domestic travel...">{{ $batch->domestic_travel_objective }}</textarea>
        </div>

        <!-- Travel Dates -->
        <div class="form-row">
            <div class="form-group">
                <label>Travel Start Date <span class="required">*</span></label>
                <input type="date" name="travelDateStart" id="startDate" required
                       value="{{ \Carbon\Carbon::parse($batch->domestic_travelDateStart)->format('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label>Travel End Date <span class="required">*</span></label>
                <input type="date" name="travelDateEnd" id="endDate" required
                       value="{{ \Carbon\Carbon::parse($batch->domestic_travelDateEnd)->format('Y-m-d') }}">
            </div>
        </div>

        <!-- Employee Section -->
        <div class="form-group full-width">
            <label>Employees <span class="required">*</span></label>
            <div class="employee-section">
                <div class="employee-selector">
                    <select id="employeeDropdown" class="select2-single">
                        <option value="">-- Search and Select Employee --</option>
                        @foreach ($allEmployees as $emp)
                            @php
                                $levelDisplay = ! empty($emp->LevelName) ? $emp->LevelName : 'No Level';
                                $nprDisplay = (float) $emp->tadaInNepali;
                            @endphp
                            <option value="{{ $emp->EmpPersonalCode }}"
                                    data-name="{{ $emp->EmpName }}"
                                    data-level="{{ $levelDisplay }}"
                                    data-npr="{{ $nprDisplay }}">
                                {{ $emp->EmpName }} - {{ $levelDisplay }}
                                (NPR {{ number_format($nprDisplay, 2) }}/day)
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
                                <th style="width:40px">#</th>
                                <th>Employee Name</th>
                                <th>Personal Code</th>
                                <th>TADA Level</th>
                                <th>NPR/Day</th>
                                <th style="width:150px;text-align:center">20% Extra</th>
                                <th style="width:100px;text-align:center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <input type="hidden" name="employee_data" id="employeeData" value="">

        <div class="form-actions">
            <button type="button" class="btn btn-secondary"
                    onclick="window.location.href='{{ url('/DomesticTadaView.php') }}'">← Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                💾 Update Batch {{ $batchId }}
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

    // Pre-populate from existing batch employees
    const existingEmployees = @json($batchEmployees);
    existingEmployees.forEach(emp => {
        addedEmployees.push({
            code:               emp.EmpPersonalCode,
            name:               emp.EmpName,
            level:              emp.DomesticTadaDefinerMasterBylevel_name || emp.LevelName || 'No Level',
            npr:                emp.tadaInNepali || 2400,
            twentyPercentExtra: emp.domestic_isTwentyPercentExtra == 1
        });
    });

    $('#districtSelect').select2({ placeholder: "Select a district",              allowClear: true, width: '100%' });
    $('#tadaTypeSelect').select2({ placeholder: "Select TADA type",               allowClear: true, width: '100%' });
    $('#tadaVerifierSelect').select2({ placeholder: "Select TADA Verifier",       allowClear: true, width: '100%' });
    $('#employeeDropdown').select2({ placeholder: "Search and select employee...", allowClear: true, width: '100%' });

    function renderEmployeeTable() {
        const tbody = $('#employeeTableBody');
        tbody.empty();

        if (addedEmployees.length === 0) {
            tbody.html('<tr class="empty-state"><td colspan="7"><div class="empty-state-icon">👥</div><div>No employees added yet.</div></td></tr>');
        } else {
            addedEmployees.forEach((emp, index) => {
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${esc(emp.name)}</strong></td>
                        <td>${esc(emp.code)}</td>
                        <td>${esc(emp.level)}</td>
                        <td>NPR ${parseFloat(emp.npr).toFixed(2)}</td>
                        <td style="text-align:center">
                            <input type="checkbox" class="twenty-percent-check"
                                   data-index="${index}" ${emp.twentyPercentExtra ? 'checked' : ''}
                                   style="cursor:pointer;width:18px;height:18px;accent-color:#667eea">
                        </td>
                        <td style="text-align:center">
                            <button type="button" class="btn-remove" data-index="${index}">🗑️ Remove</button>
                        </td>
                    </tr>`);
            });
        }
        updateHiddenInput();
    }

    function updateHiddenInput() {
        $('#employeeData').val(
            addedEmployees.map(e => `${e.code}:${e.twentyPercentExtra ? 1 : 0}`).join(',')
        );
    }

    function updateSubmitButton() {
        $('#submitBtn').prop('disabled', addedEmployees.length === 0);
    }

    $('#btnAddEmployee').on('click', function () {
        const opt     = $('#employeeDropdown option:selected');
        const empCode = opt.val();
        if (!empCode) { alert('⚠️ Please select an employee first!'); return; }
        if (addedEmployees.some(e => e.code === empCode)) { alert('⚠️ This employee is already added!'); return; }

        addedEmployees.push({
            code: empCode, name: opt.data('name'), level: opt.data('level'),
            npr: opt.data('npr'), twentyPercentExtra: false
        });
        renderEmployeeTable();
        $('#employeeDropdown').val('').trigger('change');
        updateSubmitButton();
    });

    $(document).on('change', '.twenty-percent-check', function () {
        addedEmployees[$(this).data('index')].twentyPercentExtra = $(this).is(':checked');
        updateHiddenInput();
    });

    $(document).on('click', '.btn-remove', function () {
        if (confirm('Are you sure you want to remove this employee?')) {
            addedEmployees.splice($(this).data('index'), 1);
            renderEmployeeTable();
            updateSubmitButton();
        }
    });

    $('#startDate, #endDate').on('change', function () {
        const s = $('#startDate').val(), e = $('#endDate').val();
        if (s && e && new Date(e) < new Date(s)) {
            alert('⚠️ End date cannot be before start date!');
            $('#endDate').val('');
        }
    });

    $('#tadaForm').on('submit', function (e) {
        if (addedEmployees.length === 0) {
            e.preventDefault();
            alert('⚠️ Please add at least one employee before submitting!');
            return false;
        }
        $('#submitBtn').prop('disabled', true).html('⏳ Updating Batch...');
    });

    // Initial render
    renderEmployeeTable();
    updateSubmitButton();
});
</script>
@endpush
