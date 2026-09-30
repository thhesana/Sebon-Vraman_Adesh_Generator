@props(['employees', 'emptyText', 'withExtra' => false, 'select2Placeholder' => 'Select...'])
<div class="employee-section">
    <div class="employee-selector">
        <label class="visually-hidden" for="employeeDropdown">Employee</label>
        <select id="employeeDropdown" class="form-select select2-single" data-placeholder="{{ $select2Placeholder }}">
            <option value="">-- Search and Select Employee --</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->EmpPersonalCode }}"
                        data-name="{{ $employee->EmpName }}"
                        data-level="{{ $employee->level_label }}"
                        data-rate="{{ $employee->domestic_rate }}">
                    {{ $employee->EmpName }} - {{ $employee->level_label }}
                    (NPR {{ number_format($employee->domestic_rate, 2) }}/day)
                </option>
            @endforeach
        </select>
        <button type="button" class="btn btn-outline-primary" id="btnAddEmployee">Add Employee</button>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="employeeTable">
                <thead>
                    <tr>
                        <th scope="col" class="col-index">#</th>
                        <th scope="col">Employee Name</th>
                        <th scope="col">Personal Code</th>
                        <th scope="col">TADA Level</th>
                        <th scope="col" class="num">NPR/Day</th>
                        @if ($withExtra)
                            <th scope="col" class="col-center">20% Extra</th>
                        @endif
                        <th scope="col" class="col-center">Action</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody"><!-- rendered by batch-form.js --></tbody>
            </table>
        </div>
    </div>

    <template id="employeeRowTemplate">
        <tr>
            <td data-field="index"></td>
            <td><strong data-field="name"></strong></td>
            <td data-field="code"></td>
            <td data-field="level"></td>
            <td class="num" data-field="rate"></td>
            @if ($withExtra)
                <td class="col-center"><input type="checkbox" class="form-check-input twenty-percent-check" aria-label="20% extra"></td>
            @endif
            <td class="col-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove">Remove</button></td>
        </tr>
    </template>

    <template id="employeeEmptyTemplate">
        <tr>
            <td colspan="{{ $withExtra ? 7 : 6 }}" class="empty-state">{{ $emptyText }}</td>
        </tr>
    </template>
</div>
