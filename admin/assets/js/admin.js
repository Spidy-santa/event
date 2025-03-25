/* Admin Panel JavaScript */

// Organizer Approval Handler
function approveOrganizer(userId) {
    if (confirm('Are you sure you want to approve this organizer?')) {
        fetch('approve_organizer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}`
        })
        .then(response => response.text())
        .then(() => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to approve organizer. Please try again.');
        });
    }
}

// Initialize Bootstrap Tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

document.addEventListener('DOMContentLoaded', function () {
    console.log('Admin panel loaded.');

    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('click', function () {
            alert('Button clicked: ' + this.textContent);
        });
    });
});