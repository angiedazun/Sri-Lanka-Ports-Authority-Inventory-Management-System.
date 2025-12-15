// Modern Professional Dashboard JavaScript - Sri Lanka Ports Authority

document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

function initializeDashboard() {
    // Add smooth animations
    addModernAnimations();
    
    // Initialize activity filters
    initializeActivityFilters();
    
    // Add real-time updates simulation
    initializeRealTimeUpdates();
    
    // Add interactive hover effects
    addModernInteractiveEffects();
    
    // Add particle effect to header
    addHeaderParticles();
    
    // Add ripple effect to cards
    addRippleEffect();
    
    // Animate stat numbers
    animateStatNumbers();
    
    // Animate progress bars
    animateProgressBars();
    
    // Add typing effect to header
    addTypingEffect();
    
    console.log('Sri Lanka Ports Authority Dashboard initialized - Professional Mode with Enhanced Features');
}

// Modern entrance animations
function addModernAnimations() {
    // Stats cards with stagger animation
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(50px) scale(0.9)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
        }, index * 150);
    });
    
    // Action cards with cascade animation
    const actionCards = document.querySelectorAll('.action-card.modern');
    actionCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateX(-50px) rotateY(-20deg)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateX(0) rotateY(0)';
        }, (statCards.length + index) * 120);
    });
    
    // Activity items with slide-in animation
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-60px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.7s cubic-bezier(0.34, 1.56, 0.64, 1)';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, (statCards.length + actionCards.length + index) * 100);
    });
}

// Initialize activity filter functionality
function initializeActivityFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const activityItems = document.querySelectorAll('.activity-item');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            // Filter activity items with animation
            activityItems.forEach((item, index) => {
                const itemType = item.getAttribute('data-type');
                
                if (filter === 'all' || itemType.includes(filter)) {
                    item.style.display = 'flex';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0)';
                    }, index * 50);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
            
            // Show empty state if no items match filter
            const visibleItems = Array.from(activityItems).filter(item => 
                item.style.display !== 'none'
            );
            
            const emptyState = document.querySelector('.empty-state-modern');
            if (visibleItems.length === 0 && emptyState) {
                emptyState.style.display = 'block';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }
        });
    });
}

// Simulate real-time updates
function initializeRealTimeUpdates() {
    // Update live indicator pulse with modern animation
    const liveDot = document.querySelector('.live-dot');
    if (liveDot) {
        setInterval(() => {
            liveDot.style.animation = 'none';
            liveDot.style.transform = 'scale(1.5)';
            setTimeout(() => {
                liveDot.style.animation = 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite';
                liveDot.style.transform = 'scale(1)';
            }, 100);
        }, 10000);
    }
}

// Add interactive effects to cards
function addModernInteractiveEffects() {
    // Stat cards with parallax effect on mouse move
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px) scale(1.02)`;
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0) scale(1)';
        });
    });
    
    // Action cards with magnetic effect
    const actionCards = document.querySelectorAll('.action-card.modern');
    actionCards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            const icon = this.querySelector('.action-icon-wrapper');
            if (icon) {
                icon.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px) rotateY(360deg) scale(1.15)`;
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.action-icon-wrapper');
            if (icon) {
                icon.style.transform = 'translate(0, 0) rotateY(0) scale(1)';
            }
        });
    });
    
    // Activity cards with glow effect
    const activityCards = document.querySelectorAll('.activity-card');
    activityCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 10px 40px rgba(102, 126, 234, 0.3)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '';
        });
    });
}

// Simulate data refresh with modern effects
function refreshDashboardDataModern() {
    // Add modern refresh indicator
    const liveIndicator = document.querySelector('.live-indicator');
    if (liveIndicator) {
        liveIndicator.style.transform = 'scale(1.2)';
        liveIndicator.style.boxShadow = '0 0 20px rgba(16, 185, 129, 0.6)';
        
        setTimeout(() => {
            liveIndicator.style.transform = 'scale(1)';
            liveIndicator.style.boxShadow = '';
        }, 500);
    }
    
    // Simulate stat number updates with smooth animation
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach((num, index) => {
        setTimeout(() => {
            const currentValue = parseInt(num.textContent.replace(/,/g, ''));
            // Simulate small random changes
            const change = Math.floor(Math.random() * 5) - 2; // -2 to 2
            const newValue = Math.max(0, currentValue + change);
            
            if (newValue !== currentValue) {
                // Add flash effect
                num.style.transform = 'scale(1.2)';
                num.style.color = '#667eea';
                
                animateNumber(num, currentValue, newValue, 1500);
                
                setTimeout(() => {
                    num.style.transform = 'scale(1)';
                    num.style.color = '';
                }, 1500);
            }
        }, index * 200);
    });
    
    // Show toast notification
    showToast('Dashboard data refreshed', 'success');
    
    console.log('Dashboard data refreshed at: ' + new Date().toLocaleTimeString());
}

// Show modern toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'modern-toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #667eea, #764ba2)'};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        font-weight: 600;
        z-index: 10000;
        animation: slideInRight 0.5s ease-out;
        backdrop-filter: blur(10px);
    `;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span style="margin-left: 10px;">${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.5s ease-in';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
    
    // Add animation CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    if (!document.querySelector('#toast-styles')) {
        style.id = 'toast-styles';
        document.head.appendChild(style);
    }
}

// Animate number changes
function animateNumber(element, start, end) {
    const duration = 1000;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.floor(start + (end - start) * progress);
        element.textContent = current.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

// Smooth scroll to sections
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Add keyboard navigation
document.addEventListener('keydown', function(e) {
    // ESC key to clear filters
    if (e.key === 'Escape') {
        const allFilter = document.querySelector('.filter-btn[data-filter="all"]');
        if (allFilter) {
            allFilter.click();
        }
    }
});

// Export functions for external use
window.DashboardUtils = {
    refreshData: refreshDashboardDataModern,
    scrollToSection: scrollToSection,
    animateNumber: animateNumber,
    showToast: showToast
};

// Animate stat numbers on load
function animateStatNumbers() {
    const statNumbers = document.querySelectorAll('.stat-number[data-count]');
    statNumbers.forEach((element, index) => {
        const targetValue = parseInt(element.getAttribute('data-count'));
        setTimeout(() => {
            animateNumber(element, 0, targetValue);
        }, index * 200);
    });
}

// Animate progress bars
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-bar[data-width]');
    progressBars.forEach((bar, index) => {
        const targetWidth = parseFloat(bar.getAttribute('data-width'));
        setTimeout(() => {
            bar.style.width = targetWidth + '%';
        }, 500 + (index * 200));
    });
}

// Add typing effect to dashboard subtitle
function addTypingEffect() {
    const subtitle = document.querySelector('.dashboard-subtitle');
    if (!subtitle) return;
    
    const text = subtitle.textContent;
    subtitle.textContent = '';
    subtitle.style.opacity = '1';
    
    let index = 0;
    const typeInterval = setInterval(() => {
        if (index < text.length) {
            subtitle.textContent += text.charAt(index);
            index++;
        } else {
            clearInterval(typeInterval);
            // Add cursor blink effect
            subtitle.innerHTML += '<span class="typing-cursor"></span>';
        }
    }, 50);
}

// Add particle effects
function addHeaderParticles() {
    const header = document.querySelector('.dashboard-header');
    if (!header) return;
    
    for (let i = 0; i < 20; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.cssText = `
            position: absolute;
            width: ${Math.random() * 4 + 2}px;
            height: ${Math.random() * 4 + 2}px;
            background: rgba(255, 255, 255, ${Math.random() * 0.5 + 0.2});
            border-radius: 50%;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation: particleFloat ${Math.random() * 10 + 10}s linear infinite;
            animation-delay: ${Math.random() * 5}s;
        `;
        header.appendChild(particle);
    }
    
    // Add particle animation CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes particleFloat {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(${Math.random() * 100 - 50}px);
                opacity: 0;
            }
        }
        .typing-cursor {
            display: inline-block;
            width: 2px;
            height: 1em;
            background: white;
            margin-left: 2px;
            animation: cursorBlink 1s infinite;
        }
        @keyframes cursorBlink {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0; }
        }
    `;
    if (!document.querySelector('#particle-styles')) {
        style.id = 'particle-styles';
        document.head.appendChild(style);
    }
}

// Add ripple effect to cards
function addRippleEffect() {
    const cards = document.querySelectorAll('.stat-card, .action-card.modern, .activity-card');
    
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                border-radius: 50%;
                background: rgba(102, 126, 234, 0.3);
                left: ${x}px;
                top: ${y}px;
                transform: scale(0);
                animation: rippleEffect 0.6s ease-out;
                pointer-events: none;
            `;
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // Add ripple animation CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes rippleEffect {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
    `;
    if (!document.querySelector('#ripple-styles')) {
        style.id = 'ripple-styles';
        document.head.appendChild(style);
    }
}
