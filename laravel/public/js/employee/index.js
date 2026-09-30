document.getElementById('searchInput').addEventListener('keyup', function () {
    const value = this.value.toLowerCase();

    document.querySelectorAll('#empTable tbody tr').forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});
