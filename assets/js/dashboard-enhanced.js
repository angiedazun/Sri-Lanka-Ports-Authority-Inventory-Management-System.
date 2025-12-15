/**
 * Enhanced Dashboard JavaScript
 * Integrates with analytics, notifications, and search
 */

class DashboardEnhanced {
    constructor() {
        this.charts = {};
        this.notificationManager = null;
        this.analyticsManager = null;
        this.init();
    }
    
    init() {
        this.loadNotifications();
        this.loadCharts();
        this.setupEventListeners();
        this.startAutoRefresh();
    }
    
    // Load notifications
    async loadNotifications() {
        try {
            const response = await fetch('api/notifications.php?action=list&unread_only=true&limit=5');
            const data = await response.json();
            
            if (data.success) {
                this.updateNotificationBell(data.unread_count);
                this.displayNotifications(data.notifications);
            }
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }
    
    updateNotificationBell(count) {
        const bell = document.querySelector('.notification-bell');
        if (bell) {
            const badge = bell.querySelector('.notification-badge');
            if (count > 0) {
                if (badge) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'block';
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = count > 99 ? '99+' : count;
                    bell.appendChild(newBadge);
                }
            } else if (badge) {
                badge.style.display = 'none';
            }
        }
    }
    
    displayNotifications(notifications) {
        const container = document.querySelector('.notification-list');
        if (!container) return;
        
        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-notification">
                    <i class="fas fa-bell-slash"></i>
                    <p>No new notifications</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = notifications.map(notif => `
            <div class="notification-item ${notif.type}" data-id="${notif.id}">
                <div class="notification-icon">
                    <i class="fas ${notif.icon}"></i>
                </div>
                <div class="notification-content">
                    <h4>${notif.title}</h4>
                    <p>${notif.message}</p>
                    <span class="notification-time">${this.formatTime(notif.created_at)}</span>
                </div>
                <button class="notification-dismiss" onclick="dashboardEnhanced.dismissNotification(${notif.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }
    
    async dismissNotification(id) {
        try {
            const formData = new FormData();
            formData.append('action', 'dismiss');
            formData.append('notification_id', id);
            
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (item) {
                    item.classList.add('fade-out');
                    setTimeout(() => item.remove(), 300);
                }
                this.loadNotifications(); // Refresh
            }
        } catch (error) {
            console.error('Failed to dismiss notification:', error);
        }
    }
    
    // Load analytics charts
    async loadCharts() {
        await this.loadInventoryChart();
        await this.loadStockDistribution();
        await this.loadActivityTrend();
    }
    
    async loadInventoryChart() {
        try {
            const response = await fetch('api/analytics.php?action=trend&type=papers&days=30');
            const data = await response.json();
            
            if (data.success && data.trend) {
                this.createLineChart('inventoryChart', data.trend);
            }
        } catch (error) {
            console.error('Failed to load inventory chart:', error);
        }
    }
    
    async loadStockDistribution() {
        try {
            const response = await fetch('api/analytics.php?action=distribution');
            const data = await response.json();
            
            if (data.success && data.distribution) {
                this.createPieChart('distributionChart', data.distribution);
            }
        } catch (error) {
            console.error('Failed to load distribution chart:', error);
        }
    }
    
    async loadActivityTrend() {
        try {
            const response = await fetch('api/analytics.php?action=heatmap&days=30');
            const data = await response.json();
            
            if (data.success && data.heatmap) {
                this.createHeatmap('activityHeatmap', data.heatmap);
            }
        } catch (error) {
            console.error('Failed to load activity heatmap:', error);
        }
    }
    
    createLineChart(canvasId, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }
        
        this.charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Issued',
                        data: data.issued,
                        borderColor: '#FF6384',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Received',
                        data: data.received,
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Inventory Trend (Last 30 Days)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    createPieChart(canvasId, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }
        
        this.charts[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: data.colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Stock Distribution'
                    }
                }
            }
        });
    }
    
    createHeatmap(canvasId, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }
        
        this.charts[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.date),
                datasets: [{
                    label: 'Activities',
                    data: data.map(d => d.count),
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Activity Heatmap'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    setupEventListeners() {
        // Export functionality
        const exportBtns = document.querySelectorAll('.export-btn');
        exportBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const format = btn.dataset.format;
                const table = btn.dataset.table;
                this.exportData(table, format);
            });
        });
        
        // Search functionality
        const searchInput = document.querySelector('.global-search-input');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 300);
            });
        }
        
        // Notification bell toggle
        const notificationBell = document.querySelector('.notification-bell');
        if (notificationBell) {
            notificationBell.addEventListener('click', () => {
                const panel = document.querySelector('.notification-panel');
                if (panel) {
                    panel.classList.toggle('open');
                }
            });
        }
    }
    
    async exportData(table, format) {
        try {
            Loading.show('Preparing export...');
            
            const url = `api/export.php?table=${table}&format=${format}`;
            window.location.href = url;
            
            setTimeout(() => {
                Loading.hide();
                Toast.success('Export started');
            }, 1000);
        } catch (error) {
            Loading.hide();
            Toast.error('Export failed');
            console.error('Export error:', error);
        }
    }
    
    async performSearch(query) {
        if (!query || query.length < 2) {
            this.hideSearchResults();
            return;
        }
        
        try {
            const response = await fetch(`api/search.php?action=global&q=${encodeURIComponent(query)}&limit=10`);
            const data = await response.json();
            
            if (data.success) {
                this.displaySearchResults(data.results);
            }
        } catch (error) {
            console.error('Search failed:', error);
        }
    }
    
    displaySearchResults(results) {
        const container = document.querySelector('.search-results-panel');
        if (!container) return;
        
        if (Object.keys(results).length === 0) {
            container.innerHTML = '<div class="no-results">No results found</div>';
            container.classList.add('open');
            return;
        }
        
        let html = '';
        for (const [table, tableResults] of Object.entries(results)) {
            html += `
                <div class="search-category">
                    <h4>${tableResults.display_name} (${tableResults.count})</h4>
                    <div class="search-items">
            `;
            
            tableResults.results.slice(0, 5).forEach(item => {
                const displayText = item.name || item.username || item.title || 'Item';
                html += `
                    <a href="${tableResults.view_link}" class="search-item">
                        <i class="fas fa-file"></i>
                        <span>${displayText}</span>
                    </a>
                `;
            });
            
            html += `</div></div>`;
        }
        
        container.innerHTML = html;
        container.classList.add('open');
    }
    
    hideSearchResults() {
        const container = document.querySelector('.search-results-panel');
        if (container) {
            container.classList.remove('open');
        }
    }
    
    startAutoRefresh() {
        // Refresh notifications every 2 minutes
        setInterval(() => this.loadNotifications(), 120000);
        
        // Check for low stock alerts every 5 minutes (admin only)
        if (window.userRole === 'admin') {
            setInterval(() => this.checkLowStock(), 300000);
        }
    }
    
    async checkLowStock() {
        try {
            await fetch('api/notifications.php?action=check_low_stock');
            await fetch('api/notifications.php?action=check_pending_returns');
        } catch (error) {
            console.error('Failed to check alerts:', error);
        }
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // seconds
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
        
        return date.toLocaleDateString();
    }
}

// Initialize when DOM is ready
let dashboardEnhanced;
document.addEventListener('DOMContentLoaded', () => {
    dashboardEnhanced = new DashboardEnhanced();
});
