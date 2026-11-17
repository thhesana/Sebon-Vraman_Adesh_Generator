/*
 * Fiscal year BS (Bikram Sambat) dates, converted with the Nepali date picker library.
 *
 * Form pages: every AD date input [data-bs-pair="#id"] is paired with a BS text input.
 *   AD changed -> BS filled; BS typed/picked -> AD filled. BS values are YYYY-MM-DD (Latin digits).
 * List page:  rows missing BS dates ([data-fy-missing-bs]) are converted and saved through
 *   window.APP.fyBsSyncUrl so existing records get filled once.
 */
(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
    }

    function hasLib() { return typeof window.NepaliFunctions !== 'undefined'; }

    function adToBs(ad) {
        if (!ad || !hasLib()) { return ''; }
        try { return NepaliFunctions.AD2BS(ad, 'YYYY-MM-DD', 'YYYY-MM-DD') || ''; } catch (e) { return ''; }
    }

    function bsToAd(bs) {
        const clean = String(bs || '').trim().replace(/[\/.]/g, '-');
        if (!/^\d{4}-\d{1,2}-\d{1,2}$/.test(clean) || !hasLib()) { return ''; }
        const p = clean.split('-');
        try { return NepaliFunctions.BS2AD(p[0] + '-' + p[1].padStart(2, '0') + '-' + p[2].padStart(2, '0'), 'YYYY-MM-DD', 'YYYY-MM-DD') || ''; } catch (e) { return ''; }
    }

    function normaliseBs(bs) {
        const p = String(bs || '').trim().replace(/[\/.]/g, '-').split('-');
        return p.length === 3 ? p[0] + '-' + p[1].padStart(2, '0') + '-' + p[2].padStart(2, '0') : '';
    }

    // ── Forms: keep AD and BS inputs in step ─────────────────────────────────
    function initForm() {
        document.querySelectorAll('input[data-bs-pair]').forEach(function (adInput) {
            const bsInput = document.querySelector(adInput.dataset.bsPair);
            if (!bsInput) { return; }

            function fromAd() {
                bsInput.value = adToBs(adInput.value);
                bsInput.classList.remove('is-invalid');
            }
            function fromBs() {
                if (bsInput.value.trim() === '') { return; }
                const ad = bsToAd(bsInput.value);
                bsInput.classList.toggle('is-invalid', !ad);
                if (ad) {
                    bsInput.value = normaliseBs(bsInput.value);
                    adInput.value = ad;
                }
            }

            adInput.addEventListener('change', fromAd);
            bsInput.addEventListener('change', fromBs);
            if (typeof bsInput.NepaliDatePicker === 'function') {
                bsInput.NepaliDatePicker({ dateFormat: 'YYYY-MM-DD', ndpYear: true, ndpMonth: true, onSelect: fromBs });
            }

            // Fill a missing BS value from the stored AD date.
            if (bsInput.value.trim() === '' && adInput.value) { fromAd(); }
        });

        document.querySelectorAll('form[data-fy-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                const bad = form.querySelector('input.bs-date.is-invalid');
                if (bad) {
                    event.preventDefault();
                    alert('Please enter a valid BS date (YYYY-MM-DD), e.g. 2082-04-01.');
                    bad.focus();
                }
            });
        });
    }

    // ── List: convert and save BS dates that are still empty ────────────────
    function backfillList() {
        const rows = Array.from(document.querySelectorAll('[data-fy-missing-bs]'));
        const url = window.APP && window.APP.fyBsSyncUrl;
        if (!rows.length || !url || !hasLib()) { return; }

        const payload = rows.map(function (row) {
            return {
                id: row.dataset.fyId,
                fy_startdateBS: adToBs(row.dataset.adStart),
                fy_enddateBS: adToBs(row.dataset.adEnd)
            };
        }).filter(function (r) { return r.fy_startdateBS || r.fy_enddateBS; });
        if (!payload.length) { return; }

        const token = document.querySelector('meta[name="csrf-token"]');
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '' },
            body: JSON.stringify({ rows: payload })
        }).then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
            .then(function () {
                payload.forEach(function (r) {
                    const row = document.querySelector('[data-fy-id="' + r.id + '"]');
                    if (!row) { return; }
                    row.querySelector('[data-bs-cell="start"]').textContent = r.fy_startdateBS;
                    row.querySelector('[data-bs-cell="end"]').textContent = r.fy_enddateBS;
                    row.removeAttribute('data-fy-missing-bs');
                });
            })
            .catch(function (err) { console.error('Could not save BS dates:', err); });
    }

    ready(function () { initForm(); backfillList(); });
})();
