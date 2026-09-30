/**
 * International TADA add / edit form.
 * Configuration comes from window.APP (set by the Blade view), see international/add|edit.blade.php.
 */
$(document).ready(function () {
    const cfg = window.APP;
    let addedEmployees = (cfg.initialEmployees || []).map(function (emp) {
        return {
            code: emp.code,
            name: emp.name,
            level: emp.level,
            usd: emp.usd,
            tadaId: emp.tadaId,
            dressAllowance: !!emp.dressAllowance
        };
    });

    function esc(v) {
        return $('<div>').text(v === undefined || v === null ? '' : v).html();
    }

    // ── Select2 init ──────────────────────────────────────────────────────────
    $('#countrySelect').select2({ placeholder: 'Select a country', allowClear: true, width: '100%' });
    $('#citySelect').select2({ placeholder: cfg.cityPlaceholder, allowClear: true, width: '100%' });
    $('#tadaVerifierSelect').select2({ placeholder: 'Select TADA Verifier', allowClear: true, width: '100%' });
    $('#employeeDropdown').select2({ placeholder: 'Search and select employee by name...', allowClear: true, width: '100%' });

    // ── Country → City AJAX ───────────────────────────────────────────────────
    $('#countrySelect').on('change', function () {
        const countryId = $(this).val();
        const citySelect = $('#citySelect');
        const cityLoading = $('#cityLoading');

        if (countryId) {
            cityLoading.show();
            citySelect.prop('disabled', true);
            citySelect.empty().append('<option value="">Loading cities...</option>');

            $.ajax({
                url: cfg.citiesUrl.replace('__ID__', countryId),
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
                        if (cfg.oldCityId) { citySelect.val(cfg.oldCityId); }
                    } else {
                        citySelect.append('<option value="">No cities available</option>');
                    }

                    citySelect.select2('destroy').select2({ placeholder: 'Select a city', allowClear: true, width: '100%' });
                    cityLoading.hide();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('Error loading cities. Please try again.');
                    citySelect.empty().append('<option value="">-- Error Loading Cities --</option>');
                    citySelect.prop('disabled', false);
                    cityLoading.hide();
                }
            });
        } else {
            citySelect.empty().append('<option value="">-- Select Country First --</option>');
            citySelect.prop('disabled', true);
            citySelect.select2('destroy').select2({ placeholder: 'Select country first', allowClear: true, width: '100%' });
        }
    });

    // ── Employee table ────────────────────────────────────────────────────────
    function renderEmployeeTable() {
        const tbody = $('#employeeTableBody');
        tbody.empty();

        if (addedEmployees.length === 0) {
            tbody.html(
                '<tr><td colspan="7" class="empty-state">' + esc(cfg.emptyText) + '</td></tr>'
            );
        } else {
            addedEmployees.forEach(function (emp, index) {
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${esc(emp.name)}</td>
                        <td>${esc(emp.code)}</td>
                        <td>${esc(emp.level)}</td>
                        <td class="num">${parseFloat(emp.usd).toFixed(2)}</td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input dress-allowance-check"
                                   id="dress-${index}" data-index="${index}"
                                   aria-label="Dress allowance for ${esc(emp.name)}"
                                   ${emp.dressAllowance ? 'checked' : ''}>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-index="${index}">Remove</button>
                        </td>
                    </tr>`);
            });
        }
        updateHiddenInput();
    }

    function updateHiddenInput() {
        $('#employeeData').val(addedEmployees.map(function (emp) {
            return emp.code + ':' + (emp.dressAllowance ? 1 : 0);
        }).join(','));
    }

    function updateSubmitButton() {
        $('#submitBtn').prop('disabled', addedEmployees.length === 0);
    }

    $('#btnAddEmployee').on('click', function () {
        const selectedOption = $('#employeeDropdown option:selected');
        const empCode = selectedOption.val();

        if (!empCode) { alert('Please select an employee first!'); return; }
        if (addedEmployees.some(function (emp) { return emp.code === empCode; })) { alert('This employee is already added!'); return; }

        addedEmployees.push({
            code: empCode,
            name: selectedOption.data('name'),
            level: selectedOption.data('level'),
            usd: selectedOption.data('usd'),
            tadaId: selectedOption.data('tada-id'),
            dressAllowance: false
        });

        renderEmployeeTable();
        $('#employeeDropdown').val('').trigger('change');
        updateSubmitButton();
    });

    $(document).on('change', '.dress-allowance-check', function () {
        addedEmployees[$(this).data('index')].dressAllowance = $(this).is(':checked');
        updateHiddenInput();
    });

    $(document).on('click', '.btn-remove', function () {
        if (confirm('Are you sure you want to remove this employee?')) {
            addedEmployees.splice($(this).data('index'), 1);
            renderEmployeeTable();
            updateSubmitButton();
        }
    });

    // ── Date validation ───────────────────────────────────────────────────────
    $('#startDate, #endDate').on('change', function () {
        const s = $('#startDate').val(), e = $('#endDate').val();
        if (s && e && new Date(e) < new Date(s)) {
            alert('End date cannot be before start date!');
            $('#endDate').val('');
        }
    });

    // ── Cancel button ─────────────────────────────────────────────────────────
    $('[data-href]').on('click', function () {
        window.location.href = $(this).data('href');
    });

    // ── Form submit guard ─────────────────────────────────────────────────────
    $('#tadaForm').on('submit', function (e) {
        if (addedEmployees.length === 0) {
            e.preventDefault();
            alert('Please add at least one employee before submitting!');
            return false;
        }
        $('#submitBtn').prop('disabled', true).html(cfg.submittingText);
    });

    // ── Restore state after a failed submit (add form) ────────────────────────
    if (cfg.oldEmployeeData) {
        cfg.oldEmployeeData.split(',').forEach(function (entry) {
            const parts = entry.split(':');
            if (parts.length !== 2) return;
            const opt = $('#employeeDropdown option').filter(function () { return this.value === parts[0].trim(); });
            if (!opt.length) return;
            addedEmployees.push({
                code: parts[0].trim(), name: opt.data('name'), level: opt.data('level'),
                usd: opt.data('usd'), tadaId: opt.data('tada-id'), dressAllowance: parts[1] === '1'
            });
        });
    }

    renderEmployeeTable();
    updateSubmitButton();

    if (cfg.loadCitiesOnInit && $('#countrySelect').val()) { $('#countrySelect').trigger('change'); }
});
