/* Domestic travel order print page: Print button and AD -> BS date conversion. */
document.getElementById('printAll').addEventListener('click', function () {
    window.print();
});

window.onload = function () {
    setTimeout(function () {
        document.querySelectorAll('.nepali-date').forEach(function (el) {
            var adDate = el.getAttribute('data-ad-date');
            if (adDate) {
                try {
                    el.textContent = NepaliFunctions.AD2BS(adDate, 'YYYY-MM-DD', 'YYYY/MM/DD');
                } catch (e) {
                    console.error('Date conversion error:', adDate, e);
                }
            }
        });
    }, 500);
};
