/*
 * Old international travel record: add / edit form.
 *
 * Every value is typed in by the user exactly as on the old paper order; nothing is fetched or
 * calculated into the inputs. The script only provides: Select2 dropdowns, cities filtered by
 * country, the Nepali date picker on the BS date fields (+ format check), a read-only grand
 * total for checking, and the submit guard.
 */
$(function () {
    const el = function (id) { return document.getElementById(id); };
    const hasNepali = typeof window.NepaliFunctions !== 'undefined';

    // ── Select2 ───────────────────────────────────────────────────────────────
    $('#employeeSelect').select2({ placeholder: 'Search and select employee...', allowClear: true, width: '100%' });
    $('#designationSelect').select2({ placeholder: 'Select position', allowClear: true, width: '100%' });
    $('#countrySelect').select2({ placeholder: 'Select a country', allowClear: true, width: '100%' });
    $('#tadaVerifierSelect').select2({ placeholder: 'Select TADA Verifier', allowClear: true, width: '100%' });

    // ── Cities filtered by country (all cities are rendered; keep a copy) ─────
    const $city = $('#citySelect');
    const allCities = $city.find('option[data-country]').clone();

    function filterCities(keepSelection) {
        const countryId = $('#countrySelect').val();
        const current = keepSelection ? $city.val() : '';
        $city.find('option[data-country]').remove();
        allCities.filter(function () { return countryId && this.dataset.country === countryId; })
            .clone().appendTo($city);
        $city.val(current && $city.find('option[value="' + current + '"]').length ? current : '');
        if ($city.data('select2')) { $city.select2('destroy'); }
        $city.select2({ placeholder: countryId ? 'Select a city' : 'Select country first', allowClear: true, width: '100%' });
    }
    $('#countrySelect').on('change', function () { filterCities(false); });
    filterCities(true);

    // ── BS dates ──────────────────────────────────────────────────────────────
    const NEPALI_DIGITS = { '०': '0', '१': '1', '२': '2', '३': '3', '४': '4', '५': '5', '६': '6', '७': '7', '८': '8', '९': '9' };

    /** "2083/7/20" | "२०८३-०७-२०" -> "2083-07-20"; '' when not a BS date shape. */
    function normaliseBs(value) {
        const latin = String(value || '').trim().replace(/[०-९]/g, function (d) { return NEPALI_DIGITS[d]; });
        const p = latin.split(/[-\/.]/);
        if (p.length !== 3 || !/^\d{4}$/.test(p[0]) || !/^\d{1,2}$/.test(p[1]) || !/^\d{1,2}$/.test(p[2])) { return ''; }
        return p[0] + '-' + p[1].padStart(2, '0') + '-' + p[2].padStart(2, '0');
    }

    /** True when the library accepts the BS date (it throws / returns nothing for impossible dates). */
    function isRealBsDate(bs) {
        if (!hasNepali) { return true; }
        try { return !!NepaliFunctions.BS2AD(bs, 'YYYY-MM-DD', 'YYYY-MM-DD'); } catch (e) { return false; }
    }

    function validateBsInput(input) {
        const value = input.value.trim();
        if (value === '') { input.classList.remove('is-invalid'); return true; }
        const norm = normaliseBs(value);
        const ok = !!norm && isRealBsDate(norm);
        if (ok) { input.value = norm; }
        input.classList.toggle('is-invalid', !ok);
        return ok;
    }

    document.querySelectorAll('input.bs-date').forEach(function (input) {
        if (hasNepali && typeof input.NepaliDatePicker === 'function') {
            input.NepaliDatePicker({
                dateFormat: 'YYYY-MM-DD',
                ndpYear: true,
                ndpMonth: true,
                onSelect: function () { $(input).trigger('change'); }
            });
        }
        $(input).on('change blur', function () { validateBsInput(input); });
    });

    $('#startDate, #endDate').on('change', function () {
        const s = normaliseBs(el('startDate').value), e = normaliseBs(el('endDate').value);
        if (s && e && e < s) {
            alert('End date cannot be before start date!');
            el('endDate').value = '';
        }
    });

    // ── Read-only grand total (Total NPR + dress allowance), for checking only ─
    function num(id) {
        const v = parseFloat(String(el(id).value).replace(/[,\s]/g, ''));
        return isNaN(v) ? 0 : v;
    }
    function updateGrandTotal() {
        el('grandTotal').value = (num('totalNpr') + num('dressAmount'))
            .toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    $('#totalNpr, #dressAmount').on('input change', updateGrandTotal);
    updateGrandTotal();

    // ── Submit guard ──────────────────────────────────────────────────────────
    $('#oldIntlForm').on('submit', function (event) {
        const bad = Array.from(document.querySelectorAll('input.bs-date')).find(function (input) { return !validateBsInput(input); });
        if (bad) {
            event.preventDefault();
            alert('Please enter a valid BS date (YYYY-MM-DD), e.g. 2083-07-20.');
            bad.focus();
            return false;
        }
        $('#submitBtn').prop('disabled', true).text('Saving...');
    });
});
