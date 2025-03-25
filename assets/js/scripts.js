// Toggle filter visibility
document.getElementById('toggleFilter').addEventListener('click', function() {
    const filterSection = document.getElementById('filterSection');
    if (filterSection.style.display === 'none' || filterSection.style.display === '') {
        filterSection.style.display = 'block';
        this.innerHTML = '<i class="fas fa-filter"></i> Hide Filters';
    } else {
        filterSection.style.display = 'none';
        this.innerHTML = '<i class="fas fa-filter"></i> Show Filters';
    }
});

// Update price display
document.getElementById('priceFilter').addEventListener('input', function() {
    document.getElementById('priceValue').textContent = this.value;
});
