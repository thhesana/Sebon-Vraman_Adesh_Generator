/**
 * International TADA report page. window.APP = { fiscalYear, grandTotal } comes from the Blade view.
 */
$(document).ready(function () {
    $('.select2-filter').select2({ placeholder: 'Search...', allowClear: true, width: '100%' });

    $('#btnExcel').on('click', exportExcel);
    $('#btnPrint').on('click', function () { window.print(); });

    // Travel From / To filters are BS dates picked with the Nepali date picker.
    if (typeof NepaliFunctions !== 'undefined') {
        document.querySelectorAll('input.bs-filter-date').forEach(function (input) {
            if (typeof input.NepaliDatePicker === 'function') {
                input.NepaliDatePicker({ dateFormat: 'YYYY-MM-DD', ndpYear: true, ndpMonth: true });
            }
        });
    }
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
            } catch (e) {}
        });
        // Old records store only BS dates: show their AD equivalent under them.
        document.querySelectorAll('.ad-from-bs').forEach(function (el) {
            var bs = el.getAttribute('data-bs');
            if (!bs) return;
            try { el.textContent = NepaliFunctions.BS2AD(bs, "YYYY-MM-DD", "YYYY/MM/DD"); } catch (e) {}
        });
        document.querySelectorAll('.bs-date-print').forEach(function (el) {
            var ad = el.getAttribute('data-ad');
            if (!ad) return;
            try {
                var bs = NepaliFunctions.AD2BS(ad, "YYYY-MM-DD", "YYYY/MM/DD");
                el.textContent = bs;
            } catch (e) {}
        });
    }, 450);
});

/* ── Excel export ── */
function exportExcel() {
    var table = document.getElementById('reportTable');
    var fyName = window.APP.fiscalYear;
    var grandTotal = window.APP.grandTotal;
    var rows = [];

    rows.push([window.APP.title || 'विदेश भ्रमण भत्ता (International TADA) विवरण']);
    rows.push(['आर्थिक वर्ष:', fyName, '', window.APP.grandTotalLabel || 'Grand Total USD:', grandTotal]);
    rows.push(['मुद्रण मिति:', new Date().toLocaleDateString()]);
    rows.push([]);

    // Cells stack a main value over a sub-line (batch / chalani, name / position, BS / AD): join them with " | ".
    function cellText(el) {
        return el.innerText.split('\n').map(function (s) { return s.trim(); }).filter(Boolean).join(' | ');
    }

    var headers = [];
    table.querySelectorAll('thead th').forEach(function (th) { headers.push(cellText(th)); });
    rows.push(headers);

    table.querySelectorAll('tbody tr').forEach(function (tr) {
        if (tr.querySelector('.empty-state')) return;
        var row = [];
        tr.querySelectorAll('td').forEach(function (td) {
            var clamp = td.querySelector('.clamp-2');
            row.push(clamp ? (clamp.getAttribute('title') || '').trim() : cellText(td));
        });
        rows.push(row);
    });

    // Footer: place each cell under its column (honouring colspan); keep only the main value of each cell.
    var tfoot = table.querySelector('tfoot tr');
    if (tfoot) {
        var fRow = new Array(headers.length).fill('');
        var col = 0;
        tfoot.querySelectorAll('td').forEach(function (td) {
            var span = parseInt(td.getAttribute('colspan') || '1', 10);
            var main = (td.innerText.split('\n')[0] || '').trim();
            var label = td.querySelector('.cell-sub') ? td.querySelector('.cell-sub').innerText.trim() : '';
            fRow[col + span - 1] = span > 1 ? main : (main + (label && label !== 'USD' ? ' ' + label : ''));
            col += span;
        });
        rows.push(fRow);
    }

    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(rows);
    // Column widths from the longest value in each column (rows 0-3 are the title block).
    ws['!cols'] = headers.map(function (_, c) {
        var longest = rows.slice(4).reduce(function (max, r) { return Math.max(max, String(r[c] == null ? '' : r[c]).length); }, 4);
        return { wch: Math.min(longest + 2, 50) };
    });
    ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: headers.length - 1 } }];

    XLSX.utils.book_append_sheet(wb, ws, window.APP.sheetName || 'International TADA');
    XLSX.writeFile(wb, (window.APP.filePrefix || 'IntlTADA_') + fyName + '_' + new Date().toISOString().slice(0, 10) + '.xlsx');
}
