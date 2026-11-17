/*
 * International TADA: BS (Bikram Sambat) travel dates, converted with the Nepali date picker library.
 *
 * Add / edit form: every AD date input [data-bs-pair="#hiddenId"] keeps its hidden BS input
 *   (travelDateStartBS / travelDateEndBS) and the "BS: ..." hint [data-bs-text="#hiddenId"] in step.
 * List page: AD dates of rows still missing BS ([data-intl-missing-bs]) are converted and sent to
 *   window.APP.intlBsSyncUrl so older or script-less saves get filled.
 */
(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
    }

    function adToBs(ad) {
        if (!ad || typeof window.NepaliFunctions === 'undefined') { return ''; }
        try { return NepaliFunctions.AD2BS(ad, 'YYYY-MM-DD', 'YYYY-MM-DD') || ''; } catch (e) { return ''; }
    }

    function initForm() {
        const pairs = Array.from(document.querySelectorAll('input[data-bs-pair]'));
        if (!pairs.length) { return; }

        function sync(adInput) {
            const hidden = document.querySelector(adInput.dataset.bsPair);
            if (!hidden) { return; }
            hidden.value = adToBs(adInput.value);
            document.querySelectorAll('[data-bs-text="' + adInput.dataset.bsPair + '"]').forEach(function (el) {
                el.textContent = hidden.value || '-';
            });
        }

        pairs.forEach(function (adInput) {
            adInput.addEventListener('change', function () { sync(adInput); });
            adInput.addEventListener('input', function () { sync(adInput); });
            sync(adInput);
        });

        // form.js may clear the end date without firing events: convert again right before sending.
        pairs.forEach(function (adInput) {
            if (adInput.form) {
                adInput.form.addEventListener('submit', function () { pairs.forEach(sync); });
            }
        });
    }

    function backfillList() {
        const url = window.APP && window.APP.intlBsSyncUrl;
        const rows = document.querySelectorAll('[data-intl-missing-bs]');
        if (!url || !rows.length || typeof window.NepaliFunctions === 'undefined') { return; }

        const map = {};
        rows.forEach(function (row) {
            [row.dataset.adStart, row.dataset.adEnd].forEach(function (ad) {
                if (ad && !map[ad]) { map[ad] = adToBs(ad); }
            });
        });
        Object.keys(map).forEach(function (ad) { if (!map[ad]) { delete map[ad]; } });
        if (!Object.keys(map).length) { return; }

        const token = document.querySelector('meta[name="csrf-token"]');
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '' },
            body: JSON.stringify({ dates: map })
        }).catch(function (err) { console.error('Could not save BS dates:', err); });
    }

    ready(function () { initForm(); backfillList(); });
})();
