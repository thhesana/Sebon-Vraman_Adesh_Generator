@props(['employees', 'label' => 'Employees'])
<div class="card mb-3 intl-employees">
    <div class="card-header">{{ $label }} <span class="text-danger" aria-hidden="true">*</span></div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md">
                <label class="form-label" for="employeeDropdown">Employee</label>
                <select id="employeeDropdown" class="form-select select2-single">
                    <option value="">-- Search and Select Employee --</option>
                    @foreach ($employees as $emp)
                        @php
                            $levelDisplay = $emp->LevelName ?: 'No TADA Level';
                            $usdDisplay = $emp->tadaLevel?->tadaInUSD ?: 0;
                        @endphp
                        <option value="{{ $emp->EmpPersonalCode }}"
                                data-name="{{ $emp->EmpName }}"
                                data-level="{{ $levelDisplay }}"
                                data-usd="{{ $usdDisplay }}"
                                data-tada-id="{{ $emp->tadaLevel?->TadaDefinerMasterBylevel_id }}">
                            {{ $emp->EmpName }} - {{ $levelDisplay }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="button" class="btn btn-success" id="btnAddEmployee">Add Employee</button>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="employeeTable">
                    <thead>
                        <tr>
                            <th scope="col" class="intl-col-sn">#</th>
                            <th scope="col">Employee Name</th>
                            <th scope="col">Personal Code</th>
                            <th scope="col">TADA Level</th>
                            <th scope="col" class="num">USD/Day</th>
                            <th scope="col" class="text-center">Dress Allowance</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody"><!-- Rendered by form.js --></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
