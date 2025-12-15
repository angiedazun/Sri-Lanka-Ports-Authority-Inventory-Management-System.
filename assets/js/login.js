// Sri Lanka Ports Authority - Login Page JavaScript

// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Quick login for demo
function quickLogin() {
    document.getElementById('username').value = 'admin';
    document.getElementById('password').value = 'admin123';
    showToast('Demo credentials filled! Click "Sign In" to continue.');
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!');
    });
}

// Show toast notification
function showToast(message) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    toastMessage.textContent = message;
    
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Show forgot password dialog
function showForgotPassword() {
    alert('Password reset feature will be available soon.\n\nFor demo purposes, use:\nUsername: admin\nPassword: admin123');
}

// Update time and date
function updateDateTime() {
    const now = new Date();
    
    // Update time
    const timeString = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit',
        hour12: true 
    });
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
    
    // Update date
    const dateString = now.toLocaleDateString('en-US', { 
        weekday: 'short',
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        dateElement.textContent = dateString;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update time every second
    updateDateTime();
    setInterval(updateDateTime, 1000);
    
    // Add loading state to login button
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            if (loginBtn) {
                const btnText = loginBtn.querySelector('.btn-text');
                const btnLoading = loginBtn.querySelector('.btn-loading');
                
                if (btnText && btnLoading) {
                    btnText.style.display = 'none';
                    btnLoading.style.display = 'inline-flex';
                }
                loginBtn.disabled = true;
            }
        });
    }

    // Focus on username field
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.focus();
    }

    // Add keyboard shortcut (Alt + L for quick login)
    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.key === 'l') {
            e.preventDefault();
            quickLogin();
        }
    });
});
