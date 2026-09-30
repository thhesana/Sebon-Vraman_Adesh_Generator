window.onload = function () {
    document.getElementById('printBtn')?.addEventListener('click', function () { window.print(); });

    setTimeout(function () {
        document.querySelectorAll('.nepali-date').forEach(function (el) {
            var adDate = el.getAttribute('data-ad-date');
            if (adDate) {
                try {
                    el.textContent = NepaliFunctions.AD2BS(adDate, "YYYY-MM-DD", "YYYY/MM/DD");
                } catch (e) {
                    console.error("Date conversion error:", adDate, e);
                }
            }
        });
    }, 500);
};
