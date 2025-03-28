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

function handleOrganizerAction(userId, action) {
    const reason = action === 'reject' ? prompt('Please provide a reason for rejection (optional):') : '';
    
    if (action === 'reject' && !confirm('Are you sure you want to reject this organizer?')) {
        return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);
    if (reason) formData.append('reason', reason);

    fetch('approve_organizer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '1050';
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
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

function confirmUserDelete(userName) {
    return confirm(
        `⚠️ WARNING: Delete User\n\n` +
        `You are about to delete user "${userName}"\n\n` +
        `This will permanently delete:\n` +
        `• The user account\n` +
        `• All event registrations\n` +
        `• All events created by this user\n` +
        `• All associated data\n\n` +
        `This action CANNOT be undone!\n\n` +
        `Are you sure you want to continue?`
    );
}

// Add event handler for delete buttons
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('form[onsubmit*="confirmUserDelete"]');
    deleteButtons.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!e.target.checkValidity()) {
                e.preventDefault();
                return false;
            }
        });
    });
});