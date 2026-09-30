/*
 * Domestic TADA report: filter selects, AD -> BS date conversion, print and Excel export.
 * The fiscal year name and chalani range come from data-* attributes on .dtr.
 */
$(function () {
    $('.select2-filter').select2({ placeholder: 'Search...', allowClear: true, width: '100%' });
});

const reportMeta = document.querySelector('.dtr').dataset;

function adToBs(ad) {
    return NepaliFunctions.AD2BS(ad, 'YYYY-MM-DD', 'YYYY/MM/DD');
}

/* ── BS date conversion (screen table, print table, print footer) ── */
window.addEventListener('load', function () {
    setTimeout(function () {
        document.querySelectorAll('.bs-date').forEach(function (el) {
            const ad = el.getAttribute('data-ad');
            if (!ad) return;
            try {
                const bs = adToBs(ad);
                el.textContent = bs;
                el.setAttribute('data-bs-val', bs);
            } catch (e) {}
        });

        document.querySelectorAll('.bs-date-print').forEach(function (el) {
            const ad = el.getAttribute('data-ad');
            if (!ad) return;
            try {
                el.textContent = adToBs(ad);
            } catch (e) {}
        });

        const footerDate = document.getElementById('printFooterDate');
        try {
            const todayBS = adToBs(new Date().toISOString().slice(0, 10));
            if (footerDate) footerDate.textContent = 'मुद्रण मिति: ' + todayBS;
        } catch (e) {
            if (footerDate) footerDate.textContent = 'Printed: ' + new Date().toLocaleDateString();
        }
    }, 450);
});

/* ── Print trigger ── */
function triggerPrint() {
    window.print();
}

/* ── Excel export ── */
function exportExcel() {
    const table = document.getElementById('reportTable');
    const rows = [];

    rows.push(['घरेलु भ्रमण भत्ता (Domestic TADA) विवरण']);
    rows.push(['आर्थिक वर्ष:', reportMeta.fyName, '', 'चलानी नं.:', reportMeta.chalaniRange]);
    rows.push(['मुद्रण मिति:', new Date().toLocaleDateString()]);
    rows.push([]);

    const headers = [];
    table.querySelectorAll('thead th').forEach(function (th) { headers.push(th.innerText.trim()); });
    rows.push(headers);

    table.querySelectorAll('tbody tr').forEach(function (tr) {
        if (tr.querySelector('.empty-state')) return;
        const row = [];
        tr.querySelectorAll('td').forEach(function (td) {
            const bsEl = td.querySelector('.bs-date');
            row.push(bsEl ? (bsEl.getAttribute('data-bs-val') || bsEl.innerText.trim()) : td.innerText.trim());
        });
        rows.push(row);
    });

    const tfoot = table.querySelector('tfoot tr');
    if (tfoot) {
        const footerRow = new Array(headers.length).fill('');
        footerRow[10] = 'जम्मा (Grand Total)';
        const cells = tfoot.querySelectorAll('td');
        footerRow[11] = cells[1] ? cells[1].innerText.trim() : '';
        rows.push(footerRow);
    }

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [4, 14, 14, 22, 18, 16, 30, 13, 13, 5, 14, 16, 9, 14].map(function (w) { return { wch: w }; });
    ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 13 } }];

    XLSX.utils.book_append_sheet(wb, ws, 'Domestic TADA');
    XLSX.writeFile(wb, 'DomesticTADA_' + reportMeta.fyName + '_' + new Date().toISOString().slice(0, 10) + '.xlsx');
}

document.getElementById('btnExcel')?.addEventListener('click', exportExcel);
document.getElementById('btnPrint')?.addEventListener('click', triggerPrint);
