/* Domestic travel order print page: Print button and AD -> BS date conversion (shown in Nepali digits). */
var NEPALI_DIGITS = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];

function toNepaliDigits(text) {
    return String(text).replace(/[0-9]/g, function (d) { return NEPALI_DIGITS[d]; });
}

document.getElementById('printAll').addEventListener('click', function () {
    window.print();
});

window.onload = function () {
    setTimeout(function () {
        document.querySelectorAll('.nepali-date').forEach(function (el) {
            var adDate = el.getAttribute('data-ad-date');
            if (adDate) {
                try {
                    el.textContent = toNepaliDigits(NepaliFunctions.AD2BS(adDate, 'YYYY-MM-DD', 'YYYY/MM/DD'));
                } catch (e) {
                    console.error('Date conversion error:', adDate, e);
                }
            }
        });
    }, 500);
};
