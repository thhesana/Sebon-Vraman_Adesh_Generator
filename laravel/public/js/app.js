// Refresh the NRB rate in the background before opening the international module.
document.addEventListener('DOMContentLoaded', function () {
    const tab = document.getElementById('intlVramanTab');
    if (!tab) return;

    tab.addEventListener('click', function (e) {
        e.preventDefault();
        const target = this.href;
        const go = () => { window.location.href = target; };
        document.getElementById('loadingSpinner').classList.add('active');
        fetch(this.dataset.usdUrl).then(go).catch(go);
    });
});
