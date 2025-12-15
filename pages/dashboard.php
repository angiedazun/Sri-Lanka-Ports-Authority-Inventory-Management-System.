<?php
require_once '../includes/db.php';
require_login();

$page_title = "Dashboard - SLPA System";

// Fetch real-time data from database
try {
    // Get total counts
    $toner_result = $conn->query("SELECT COUNT(*) as count FROM toner_master");
    $toner_count = $toner_result ? $toner_result->fetch_assoc()['count'] : 0;
    
    // Get today's activities
    $today = date('Y-m-d');
    $received_result = $conn->query("SELECT COUNT(*) as count FROM toner_receiving WHERE DATE(receive_date) = '$today'");
    $received_today = $received_result ? $received_result->fetch_assoc()['count'] : 0;
    
    $issued_result = $conn->query("SELECT COUNT(*) as count FROM toner_issuing WHERE DATE(issue_date) = '$today'");
    $issued_today = $issued_result ? $issued_result->fetch_assoc()['count'] : 0;
    
    // Get total received and issued
    $total_received_result = $conn->query("SELECT COUNT(*) as count FROM toner_receiving");
    $total_received = $total_received_result ? $total_received_result->fetch_assoc()['count'] : 0;
    
    $total_issued_result = $conn->query("SELECT COUNT(*) as count FROM toner_issuing");
    $total_issued = $total_issued_result ? $total_issued_result->fetch_assoc()['count'] : 0;
    
    // Get low stock items (assuming total stock < 10 is low)
    $low_stock_toner_result = $conn->query("SELECT COUNT(*) as count FROM toner_master WHERE (jct_stock + uct_stock) < 10");
    $low_stock_toner = $low_stock_toner_result ? $low_stock_toner_result->fetch_assoc()['count'] : 0;
    
    // Get recent activities (last 10 transactions)
    $recent_query = "
        (SELECT 'Toner Received' as activity_type, receive_date as date, toner_model as item_name, 
         (jct_quantity + uct_quantity) as quantity, supplier_name as user_name 
         FROM toner_receiving 
         ORDER BY receive_date DESC LIMIT 5)
        UNION ALL
        (SELECT 'Toner Issued' as activity_type, issue_date as date, toner_model as item_name, 
         quantity, receiver_name as user_name 
         FROM toner_issuing 
         ORDER BY issue_date DESC LIMIT 5)
        ORDER BY date DESC LIMIT 10
    ";
    $recent_activities = $conn->query($recent_query);
    
} catch (Exception $e) {
    // Fallback values if database query fails
    error_log("Dashboard query error: " . $e->getMessage());
    $toner_count = 0;
    $received_today = 0;
    $issued_today = 0;
    $total_received = 0;
    $total_issued = 0;
    $low_stock_toner = 0;
    $recent_activities = false;
}

// Include dashboard-specific CSS and JS
$additional_css = ['../assets/css/dashboard.css'];
$additional_js = ['../assets/js/dashboard.js'];

include '../includes/header.php';

// Debug output (remove this after testing)
if (isset($_GET['debug'])) {
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "Debug Info:<br>";
    echo "Toner Count: " . $toner_count . "<br>";
    echo "Received Today: " . $received_today . "<br>";
    echo "Issued Today: " . $issued_today . "<br>";
    echo "Total Received: " . $total_received . "<br>";
    echo "Total Issued: " . $total_issued . "<br>";
    echo "</div>";
}
?>

<!-- Modern Professional Dashboard -->
<div class="dashboard-container">
    <!-- Enhanced Header -->
    <div class="dashboard-header">
        <video autoplay muted loop playsinline>
            <source src="../assets/Videos/web_video_2.mp4" type="video/mp4">
        </video>
        <div class="header-content">
            <h1 class="dashboard-title">Sri Lanka Ports Authority</h1>
            <p class="dashboard-subtitle">Professional inventory management solution for Sri Lanka Ports Authority. Streamlining operations with cutting-edge technology.</p>
            <div class="header-meta">
                <div class="live-indicator">
                    <span class="live-dot"></span>
                    Live Data
                </div>
                <div class="dashboard-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span id="current-date"><?php echo date('l, F j, Y'); ?></span>
                </div>
                <div class="dashboard-time">
                    <i class="fas fa-clock"></i>
                    <span id="current-time"><?php echo date('h:i:s A'); ?></span>
                </div>
            </div>
        </div>
        <button class="refresh-btn" onclick="refreshDashboard()">
            <i class="fas fa-sync-alt"></i>
            <span>Refresh</span>
        </button>
    </div>

    <!-- Statistics Overview with Real Data -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">INVENTORY</div>
                <div class="stat-number" data-count="<?php echo $toner_count; ?>">0</div>
                <div class="stat-description">Total Toner Items</div>
                <div class="stat-progress">
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar primary" style="width: 0%" data-width="<?php echo min(100, ($toner_count / 100) * 100); ?>"></div>
                    </div>
                    <span class="progress-text"><?php echo $low_stock_toner > 0 ? $low_stock_toner . ' Low Stock' : 'Stock OK'; ?></span>
                </div>
                <div class="stat-badge <?php echo $low_stock_toner > 0 ? 'badge-warning' : 'badge-success'; ?>">
                    <i class="fas <?php echo $low_stock_toner > 0 ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <?php echo $low_stock_toner > 0 ? 'Stock Levels Good' : 'Stock Levels Good'; ?>
                </div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">RECEIVING</div>
                <div class="stat-number" data-count="<?php echo $received_today; ?>">0</div>
                <div class="stat-description">Items Received Today</div>
                <div class="stat-progress">
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar success" style="width: 0%" data-width="<?php echo min(100, ($received_today / max(1, $received_today + $issued_today)) * 100); ?>"></div>
                    </div>
                    <span class="progress-text"><?php echo number_format(($total_received > 0) ? ($received_today / $total_received) * 100 : 0, 1); ?>% of total</span>
                </div>
                <div class="stat-badge badge-info">
                    <i class="fas fa-chart-line"></i>
                    Total: <?php echo number_format($total_received); ?> items
                </div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">ISSUING</div>
                <div class="stat-number" data-count="<?php echo $issued_today; ?>">0</div>
                <div class="stat-description">Items Issued Today</div>
                <div class="stat-progress">
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar warning" style="width: 0%" data-width="<?php echo min(100, ($issued_today / max(1, $received_today + $issued_today)) * 100); ?>"></div>
                    </div>
                    <span class="progress-text"><?php echo number_format(($total_issued > 0) ? ($issued_today / $total_issued) * 100 : 0, 1); ?>% of total</span>
                </div>
                <div class="stat-badge badge-info">
                    <i class="fas fa-chart-line"></i>
                    Total: <?php echo number_format($total_issued); ?> items
                </div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">ACTIVITY</div>
                <div class="stat-number" data-count="<?php echo ($received_today + $issued_today); ?>">0</div>
                <div class="stat-description">Total Transactions Today</div>
                <div class="stat-progress">
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar info" style="width: 0%" data-width="100"></div>
                    </div>
                    <span class="progress-text">Last 24 hours</span>
                </div>
                <div class="stat-badge badge-success">
                    <i class="fas fa-check-circle"></i>
                    System Active
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Overview -->
    <div class="quick-stats-overview">
        <div class="stats-summary-card">
            <div class="summary-header">
                <h3><i class="fas fa-chart-pie"></i> Today's Performance</h3>
            </div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-icon success">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Efficiency Rate</span>
                        <span class="summary-value"><?php echo $total_received > 0 ? number_format(($received_today / $total_received) * 100, 1) : 0; ?>%</span>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Processing Time</span>
                        <span class="summary-value">~2.5h</span>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-icon info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Active Users</span>
                        <span class="summary-value">1</span>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-icon primary">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Last Update</span>
                        <span class="summary-value"><?php echo date('H:i'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-rocket"></i>
                Quick Actions
            </h2>
            <p class="section-subtitle">Manage your inventory operations efficiently</p>
        </div>
        
        <div class="actions-grid">
            <a href="toner_master.php" class="action-card modern">
                <div class="action-icon-wrapper primary">
                    <i class="fas fa-database"></i>
                </div>
                <h3 class="action-title">Toner Master</h3>
                <p class="action-description">View and manage toner inventory database</p>
                <div class="action-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="toner_receiving.php" class="action-card modern">
                <div class="action-icon-wrapper success">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3 class="action-title">Receive Items</h3>
                <p class="action-description">Record new toner items received</p>
                <div class="action-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="toner_issuing.php" class="action-card modern">
                <div class="action-icon-wrapper warning">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <h3 class="action-title">Issue Items</h3>
                <p class="action-description">Issue toner items to departments</p>
                <div class="action-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="toner_return.php" class="action-card modern">
                <div class="action-icon-wrapper info">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3 class="action-title">Returns</h3>
                <p class="action-description">Process toner item returns</p>
                <div class="action-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="papers_master.php" class="action-card modern">
                <div class="action-icon-wrapper primary">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3 class="action-title">Papers Master</h3>
                <p class="action-description">Manage papers inventory database</p>
                <div class="action-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="papers_receiving.php" class="action-card modern">
                <div class="action-icon-wrapper success">
                    <i class="fas fa-truck-loading"></i>
                </div>
                <h3 class="action-title">Receive Papers</h3>
                <p class="action-description">Record new paper items received</p>
                <div class="action-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="papers_issuing.php" class="action-card modern">
                <div class="action-icon-wrapper warning">
                    <i class="fas fa-share-square"></i>
                </div>
                <h3 class="action-title">Issue Papers</h3>
                <p class="action-description">Issue paper items to departments</p>
                <div class="action-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="papers_return.php" class="action-card modern">
                <div class="action-icon-wrapper info">
                    <i class="fas fa-reply"></i>
                </div>
                <h3 class="action-title">Paper Returns</h3>
                <p class="action-description">Process paper item returns</p>
                <div class="action-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Activities with Real Data -->
    <div class="recent-activities-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Activities
                </h2>
                <p class="section-subtitle">Latest inventory transactions</p>
            </div>
            <div class="activity-filters">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-list"></i> All
                </button>
                <button class="filter-btn" data-filter="received">
                    <i class="fas fa-arrow-down"></i> Received
                </button>
                <button class="filter-btn" data-filter="issued">
                    <i class="fas fa-arrow-up"></i> Issued
                </button>
            </div>
        </div>
        
        <div class="activities-container">
            <div class="activity-timeline">
                <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                    <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="activity-item" data-type="<?php echo strtolower(str_replace(' ', '', $activity['activity_type'])); ?>">
                            <div class="activity-timeline-marker <?php echo strpos($activity['activity_type'], 'Received') !== false ? 'received' : 'issued'; ?>">
                                <i class="fas fa-<?php echo strpos($activity['activity_type'], 'Received') !== false ? 'arrow-circle-down' : 'arrow-circle-up'; ?>"></i>
                            </div>
                            <div class="activity-card">
                                <div class="activity-header">
                                    <span class="activity-type-badge <?php echo strpos($activity['activity_type'], 'Received') !== false ? 'received' : 'issued'; ?>">
                                        <?php echo htmlspecialchars($activity['activity_type']); ?>
                                    </span>
                                    <span class="activity-time">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('h:i A', strtotime($activity['date'])); ?>
                                    </span>
                                </div>
                                <div class="activity-body">
                                    <h4 class="activity-item-name"><?php echo htmlspecialchars($activity['item_name']); ?></h4>
                                    <div class="activity-details">
                                        <span class="detail-item">
                                            <i class="fas fa-cube"></i>
                                            Quantity: <strong><?php echo number_format($activity['quantity']); ?></strong>
                                        </span>
                                        <?php if (!empty($activity['user_name'])): ?>
                                            <span class="detail-item">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($activity['user_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="detail-item">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M j, Y', strtotime($activity['date'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state-modern">
                        <div class="empty-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>No Recent Activities</h3>
                        <p>Start by adding items to your inventory or processing transactions.</p>
                        <a href="toner_receiving.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Transaction
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Update dashboard date display
function updateDashboardDate() {
    const dateElement = document.getElementById('dashboard-date');
    if(dateElement) {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateElement.textContent = now.toLocaleDateString('en-US', options);
    }
}

// Update real-time clock
function updateClock() {
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        const now = new Date();
        const hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;
        
        timeElement.textContent = `${String(displayHours).padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
    }
}

// Refresh dashboard with animation
function refreshDashboard() {
    const btn = document.querySelector('.refresh-btn i');
    if (btn) {
        btn.style.animation = 'spin 1s linear';
        setTimeout(() => {
            btn.style.animation = '';
        }, 1000);
    }
    
    // Show toast notification
    if (window.DashboardUtils && window.DashboardUtils.showToast) {
        window.DashboardUtils.showToast('Dashboard refreshed successfully', 'success');
    }
    
    // Reload page after animation
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateDashboardDate();
    updateClock();
    
    // Update clock every second
    setInterval(updateClock, 1000);
});

// Add spin animation
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

<?php include '../includes/footer.php'; ?>