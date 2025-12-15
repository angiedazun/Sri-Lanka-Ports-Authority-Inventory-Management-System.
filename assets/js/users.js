// User Management JavaScript

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('User Management Page Initialized');
    console.log('Users Data:', typeof usersData !== 'undefined' ? usersData.length : 0);
});

// Modal Management
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        const form = modal.querySelector('form');
        if (form) form.reset();
    }
}

// Table filtering
function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const table = document.getElementById('usersTable');
    const tbody = table.getElementsByTagName('tbody')[0];
    const rows = tbody.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let showRow = true;
        
        // Search filter
        if (searchTerm) {
            let rowText = '';
            for (let j = 0; j < cells.length - 1; j++) {
                rowText += cells[j].textContent.toLowerCase() + ' ';
            }
            if (!rowText.includes(searchTerm)) {
                showRow = false;
            }
        }
        
        // Role filter
        if (showRow && roleFilter) {
            const roleCell = cells[3];
            if (roleCell && !roleCell.textContent.toLowerCase().includes(roleFilter)) {
                showRow = false;
            }
        }
        
        // Status filter
        if (showRow && statusFilter) {
            const statusCell = cells[6];
            if (statusCell && !statusCell.textContent.toLowerCase().includes(statusFilter)) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    }
}

// View user details
function viewUser(userId) {
    const user = findUserById(userId);
    if (!user) {
        alert('User not found!');
        return;
    }
    
    const content = document.getElementById('viewUserContent');
    content.innerHTML = '<div class="view-details"><div class="detail-section"><h4><i class="fas fa-user"></i> Personal Information</h4><div class="detail-grid"><div class="detail-item"><label>User ID:</label><span>' + user.user_id + '</span></div><div class="detail-item"><label>Username:</label><span>' + user.username + '</span></div><div class="detail-item"><label>Full Name:</label><span>' + user.full_name + '</span></div><div class="detail-item"><label>Email:</label><span>' + user.email + '</span></div><div class="detail-item"><label>Phone:</label><span>' + (user.phone || 'N/A') + '</span></div><div class="detail-item"><label>Department:</label><span>' + (user.department || 'N/A') + '</span></div></div></div><div class="detail-section"><h4><i class="fas fa-shield-alt"></i> Access Information</h4><div class="detail-grid"><div class="detail-item"><label>Role:</label><span class="role-badge role-' + user.role + '">' + user.role.charAt(0).toUpperCase() + user.role.slice(1) + '</span></div><div class="detail-item"><label>Status:</label><span class="status-badge status-' + user.status + '">' + user.status.charAt(0).toUpperCase() + user.status.slice(1) + '</span></div><div class="detail-item"><label>Last Login:</label><span>' + (user.last_login ? formatDate(user.last_login) : 'Never') + '</span></div><div class="detail-item"><label>Created:</label><span>' + formatDate(user.created_at) + '</span></div></div></div></div>';
    
    openModal('viewUserModal');
}

// Edit user
function editUser(userId) {
    const user = findUserById(userId);
    if (!user) {
        alert('User not found!');
        return;
    }
    
    document.getElementById('editUserId').value = user.user_id;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editFullName').value = user.full_name;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editPhone').value = user.phone || '';
    document.getElementById('editDepartment').value = user.department || '';
    document.getElementById('editRole').value = user.role;
    document.getElementById('editStatus').value = user.status;
    
    openModal('editUserModal');
}

// Delete confirmation
function confirmDelete(userId) {
    const user = findUserById(userId);
    if (!user) {
        alert('User not found!');
        return;
    }
    
    const confirmMessage = 'Are you sure you want to delete this user?\n\nUsername: ' + user.username + '\nFull Name: ' + user.full_name;
    
    if (confirm(confirmMessage)) {
        window.location.href = '?delete=' + userId;
    }
}

// Utility functions
function findUserById(userId) {
    if (typeof usersData !== 'undefined') {
        return usersData.find(user => user.user_id == userId);
    }
    return null;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Form validation
function validateForm(formId) {
    return true;
}

// Event listeners
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal[style*="display: block"]');
        if (openModal) {
            closeModal(openModal.id);
        }
    }
});