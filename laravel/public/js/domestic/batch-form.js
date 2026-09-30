/*
 * Domestic TADA add / edit batch form: employee picker table + form guards.
 * Server values arrive through window.APP.domesticForm (see the views):
 *   withExtra          edit form only - every employee has a "20% extra" checkbox
 *   existing           [{code, name, level, rate, extra}] employees already in the batch
 *   oldEmployeeData    the submitted employee_data after a validation failure (or null)
 *   submittingText     label of the submit button while the form is being sent
 *   emptySubmitMessage alert shown when submitting without employees
 */
$(function () {
    const config = window.APP.domesticForm;
    const $dropdown = $('#employeeDropdown');
    const $tbody = $('#employeeTableBody');
    const $submit = $('#submitBtn');
    const rowTemplate = document.getElementById('employeeRowTemplate');
    const emptyTemplate = document.getElementById('employeeEmptyTemplate');

    $('.select2-single').select2({ allowClear: true, width: '100%' });

    // Employee option (with its data-* attributes) -> table entry
    function entryFromOption(code, extra) {
        const option = $dropdown.find('option').filter(function () { return this.value === code; })[0];
        if (!option) { return null; }
        return { code: code, name: option.dataset.name, level: option.dataset.level, rate: option.dataset.rate, extra: extra };
    }

    // After a validation failure restore what the user had submitted ("code" or "code:flag", comma separated)
    function restoreOldEntries(data) {
        return data.split(',')
            .filter(function (item) { return item.trim() !== ''; })
            .map(function (item) {
                const parts = item.trim().split(':');
                return entryFromOption(parts[0], parts[1] === '1');
            })
            .filter(Boolean);
    }

    let employees = config.oldEmployeeData ? restoreOldEntries(config.oldEmployeeData) : config.existing.slice();

    function render() {
        $tbody.empty();

        if (employees.length === 0) {
            $tbody.append(emptyTemplate.content.cloneNode(true));
        } else {
            employees.forEach(function (emp, index) {
                const row = rowTemplate.content.cloneNode(true);
                row.querySelector('[data-field="index"]').textContent = index + 1;
                row.querySelector('[data-field="name"]').textContent = emp.name == null ? '' : emp.name;
                row.querySelector('[data-field="code"]').textContent = emp.code;
                row.querySelector('[data-field="level"]').textContent = emp.level == null ? '' : emp.level;
                row.querySelector('[data-field="rate"]').textContent = 'NPR ' + parseFloat(emp.rate).toFixed(2);
                row.querySelector('.btn-remove').dataset.index = index;

                const check = row.querySelector('.twenty-percent-check');
                if (check) {
                    check.dataset.index = index;
                    check.checked = !!emp.extra;
                }
                $tbody.append(row);
            });
        }

        syncForm();
    }

    function syncForm() {
        $('#employeeData').val(employees.map(function (emp) {
            return config.withExtra ? emp.code + ':' + (emp.extra ? 1 : 0) : emp.code;
        }).join(','));
        $submit.prop('disabled', employees.length === 0);
    }

    $('#btnAddEmployee').on('click', function () {
        const code = $dropdown.val();

        if (!code) { alert('Please select an employee first.'); return; }
        if (employees.some(function (emp) { return emp.code === code; })) {
            alert('This employee is already added.');
            return;
        }

        employees.push(entryFromOption(code, false));
        $dropdown.val('').trigger('change');
        render();
    });

    $(document).on('click', '.btn-remove', function () {
        if (confirm('Are you sure you want to remove this employee?')) {
            employees.splice($(this).data('index'), 1);
            render();
        }
    });

    $(document).on('change', '.twenty-percent-check', function () {
        employees[$(this).data('index')].extra = $(this).is(':checked');
        syncForm();
    });

    $('#startDate, #endDate').on('change', function () {
        const start = $('#startDate').val();
        const end = $('#endDate').val();
        if (start && end && new Date(end) < new Date(start)) {
            alert('End date cannot be before start date.');
            $('#endDate').val('');
        }
    });

    $('#tadaForm').on('submit', function (event) {
        if (employees.length === 0) {
            event.preventDefault();
            alert(config.emptySubmitMessage);
            return false;
        }
        $submit.prop('disabled', true).html(config.submittingText);
    });

    $('[data-href]').on('click', function () {
        window.location.href = this.dataset.href;
    });

    render();
});
