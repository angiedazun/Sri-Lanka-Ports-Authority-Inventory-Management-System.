<?php
// Header template for all pages
// Check authentication and session integrity
if (!Auth::check()) {
    Response::redirect('../auth/login.php');
}

// Verify session integrity
Auth::verifySessionIntegrity();

// Regenerate CSRF token for forms
Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'SLPA System'; ?></title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/font-awesome-fix.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/ui-components.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-anchor"></i>
                    <div class="logo-text">
                        <span class="org-name">Sri Lanka Ports Authority</span>
                        <span class="system-name">Inventory Management System</span>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="datetime-display">
                    <i class="fas fa-calendar-alt"></i>
                    <span id="current-datetime"></span>
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span class="user-name"><?php echo $_SESSION['full_name']; ?></span>
                </div>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link <?php echo (strpos(basename($_SERVER['PHP_SELF']), 'toner_') === 0) ? 'active' : ''; ?>">
                        <i class="fas fa-print"></i> Toner Management
                    </a>
                    <div class="dropdown-menu">
                        <a href="toner_master.php" class="dropdown-item">
                            <i class="fas fa-database"></i> Toner Master
                        </a>
                        <a href="toner_receiving.php" class="dropdown-item">
                            <i class="fas fa-arrow-down"></i> Receiving
                        </a>
                        <a href="toner_issuing.php" class="dropdown-item">
                            <i class="fas fa-arrow-up"></i> Issuing
                        </a>
                        <a href="toner_return.php" class="dropdown-item">
                            <i class="fas fa-undo"></i> Returns
                        </a>
                    </div>
                </li>
                
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link <?php echo (strpos(basename($_SERVER['PHP_SELF']), 'papers_') === 0) ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i> Papers Management
                    </a>
                    <div class="dropdown-menu">
                        <a href="papers_master.php" class="dropdown-item">
                            <i class="fas fa-database"></i> Papers Master
                        </a>
                        <a href="papers_receiving.php" class="dropdown-item">
                            <i class="fas fa-truck-loading"></i> Receiving
                        </a>
                        <a href="papers_issuing.php" class="dropdown-item">
                            <i class="fas fa-share-square"></i> Issuing
                        </a>
                        <a href="papers_return.php" class="dropdown-item">
                            <i class="fas fa-undo"></i> Returns
                        </a>
                    </div>
                </li>
                
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link <?php echo (strpos(basename($_SERVER['PHP_SELF']), 'ribbons_') === 0) ? 'active' : ''; ?>">
                        <i class="fas fa-tape"></i> Ribbons Management
                    </a>
                    <div class="dropdown-menu">
                        <a href="ribbons_master.php" class="dropdown-item">
                            <i class="fas fa-database"></i> Ribbons Master
                        </a>
                        <a href="ribbons_receiving.php" class="dropdown-item">
                            <i class="fas fa-inbox"></i> Receiving
                        </a>
                        <a href="ribbons_issuing.php" class="dropdown-item">
                            <i class="fas fa-paper-plane"></i> Issuing
                        </a>
                        <a href="ribbons_return.php" class="dropdown-item">
                            <i class="fas fa-undo-alt"></i> Returns
                        </a>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
    
    <!-- Load UI Enhancement Scripts -->
    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/loading.js"></script>
    <script src="../assets/js/modal.js"></script>
    <script src="../assets/js/enhancements.js"></script>
    
    <script>
        // Update datetime display
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('current-datetime').textContent = now.toLocaleString('en-US', options);
        }
        
        // Update every second
        if(document.getElementById('current-datetime')) {
            updateDateTime();
            setInterval(updateDateTime, 1000);
        }
        
        // Show PHP messages as toasts
        <?php if (isset($_SESSION['message']) && isset($_SESSION['message_type'])): ?>
            document.addEventListener('DOMContentLoaded', () => {
                Toast.<?php echo $_SESSION['message_type']; ?>('<?php echo addslashes($_SESSION['message']); ?>');
            });
        <?php endif; ?>
    </script>
<?php
// End of header template - content will be added by individual pages
?>