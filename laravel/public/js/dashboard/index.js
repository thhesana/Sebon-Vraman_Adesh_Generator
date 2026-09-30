// Chart data comes from data-* attributes on the canvas (see dashboard.blade.php).
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('barChart');
    const read = (key) => JSON.parse(canvas.dataset[key]);

    // Same font and palette as the rest of the application.
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 13;
    const css = getComputedStyle(document.documentElement);
    const domestic = css.getPropertyValue('--brand-600').trim() || '#0d56b3';
    const international = '#d97706';
    const gridColor = css.getPropertyValue('--gray-200').trim() || '#e2e7ef';
    const textColor = css.getPropertyValue('--gray-700').trim() || '#334155';
    Chart.defaults.color = textColor;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: read('labels'),
            datasets: [
                { label: 'Domestic', data: read('domestic'), backgroundColor: domestic, borderRadius: 4, borderSkipped: false },
                { label: 'International', data: read('international'), backgroundColor: international, borderRadius: 4, borderSkipped: false }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } },
            scales: {
                x: { ticks: { autoSkip: false, maxRotation: 45 }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } }
            }
        }
    });
});
