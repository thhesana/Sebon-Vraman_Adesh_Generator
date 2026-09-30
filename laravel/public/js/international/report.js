/**
 * International TADA report page. window.APP = { fiscalYear, grandTotal } comes from the Blade view.
 */
$(document).ready(function () {
    $('.select2-filter').select2({ placeholder: 'Search...', allowClear: true, width: '100%' });

    $('#btnExcel').on('click', exportExcel);
    $('#btnPrint').on('click', function () { window.print(); });
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

    rows.push(['विदेश भ्रमण भत्ता (International TADA) विवरण']);
    rows.push(['आर्थिक वर्ष:', fyName, '', 'Grand Total USD:', grandTotal]);
    rows.push(['मुद्रण मिति:', new Date().toLocaleDateString()]);
    rows.push([]);

    var headers = [];
    table.querySelectorAll('thead th').forEach(function (th) { headers.push(th.innerText.trim()); });
    rows.push(headers);

    table.querySelectorAll('tbody tr').forEach(function (tr) {
        if (tr.querySelector('.empty-state')) return;
        var row = [];
        tr.querySelectorAll('td').forEach(function (td) {
            var bsEl = td.querySelector('.bs-date');
            row.push(bsEl ? (bsEl.getAttribute('data-bs-val') || bsEl.innerText.trim()) : td.innerText.trim());
        });
        rows.push(row);
    });

    var tfoot = table.querySelector('tfoot tr');
    if (tfoot) {
        var fRow = new Array(headers.length).fill('');
        fRow[11] = 'जम्मा (Grand Total)';
        var fc = tfoot.querySelectorAll('td');
        fRow[12] = fc[1] ? fc[1].innerText.trim() : '';
        rows.push(fRow);
    }

    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [4, 10, 14, 22, 18, 16, 14, 28, 13, 13, 5, 12, 14, 9, 10].map(function (w) { return { wch: w }; });
    ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 14 } }];

    XLSX.utils.book_append_sheet(wb, ws, 'International TADA');
    XLSX.writeFile(wb, 'IntlTADA_' + fyName + '_' + new Date().toISOString().slice(0, 10) + '.xlsx');
}
