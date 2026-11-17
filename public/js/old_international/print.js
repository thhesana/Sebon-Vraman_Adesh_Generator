/*
 * Old international travel print: dates are stored and printed in BS. Only the
 * "(तदनुसार ...)" AD equivalent of the travel period is converted here, for display.
 */
window.addEventListener('load', function () {
    document.getElementById('printBtn')?.addEventListener('click', function () { window.print(); });

    document.querySelectorAll('.bs-to-ad').forEach(function (el) {
        const bs = el.dataset.bs;
        if (!bs || typeof window.NepaliFunctions === 'undefined') { return; }
        try {
            el.textContent = NepaliFunctions.BS2AD(bs, 'YYYY-MM-DD', 'YYYY/MM/DD');
        } catch (e) {
            console.error('Date conversion error:', bs, e);
        }
    });
});
