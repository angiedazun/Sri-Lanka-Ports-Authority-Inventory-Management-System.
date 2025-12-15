<?php
require_once '../includes/db.php';

$error_message = '';

// Check if user is already logged in
if (Auth::check()) {
    Response::redirect('../pages/dashboard.php');
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Invalid security token. Please try again.";
        Logger::warning('CSRF token validation failed on login');
    } else {
        // Use null coalescing to avoid "Undefined array key" warnings when fields are missing
        $username = Sanitizer::string($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error_message = "Please enter both username and password.";
        } elseif (!RateLimiter::check('login_' . $username, MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_TIME / 60)) {
            $remaining = RateLimiter::availableIn('login_' . $username);
            $minutes = ceil($remaining / 60);
            $error_message = "Too many failed attempts. Please try again in {$minutes} minutes.";
            AuditTrail::logSecurityEvent('rate_limit_exceeded', ['username' => $username]);
        } else {
            // Check user credentials from database
            $sql = "SELECT user_id, username, password, full_name, role, email FROM users WHERE username = ? AND status = 'active'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (Security::verifyPassword($password, $user['password'])) {
                    // Login successful
                    Auth::login(
                        $user['user_id'],
                        $user['username'],
                        $user['full_name'],
                        $user['role'],
                        $user['email'] ?? ''
                    );
                    
                    // Clear rate limiter
                    RateLimiter::clear('login_' . $username);
                    
                    // Log successful login
                    AuditTrail::logLogin($username, true);
                    
                    // Update last login
                    $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("i", $user['user_id']);
                    $update_stmt->execute();
                    
                    // Redirect to intended URL or dashboard
                    $redirectUrl = Auth::getIntendedUrl();
                    Response::redirect($redirectUrl);
                } else {
                    $error_message = "Invalid username or password.";
                    RateLimiter::hit('login_' . $username, LOGIN_LOCKOUT_TIME / 60);
                    AuditTrail::logLogin($username, false, 'Invalid password');
                }
            } else {
                $error_message = "Invalid username or password.";
                RateLimiter::hit('login_' . $username, LOGIN_LOCKOUT_TIME / 60);
                AuditTrail::logLogin($username, false, 'User not found');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sri Lanka Ports Authority</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/ui-components.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-wrapper">
        <!-- Decorative Elements -->
        <div class="decoration-circle circle-1"></div>
        <div class="decoration-circle circle-2"></div>
        <div class="decoration-circle circle-3"></div>
        <div class="decoration-shape shape-1"></div>
        <div class="decoration-shape shape-2"></div>
        <div class="decoration-shape shape-3"></div>
        <div class="decoration-shape shape-4"></div>
        <div class="decoration-dot dot-1"></div>
        <div class="decoration-dot dot-2"></div>
        <div class="decoration-dot dot-3"></div>
        <div class="decoration-dot dot-4"></div>
        
        <!-- Office Supply Decorations -->
        <div class="toner toner-1"></div>
        <div class="toner toner-2"></div>
        <div class="ribbon ribbon-1"></div>
        <div class="ribbon ribbon-2"></div>
        <div class="paper-stack paper-1"></div>
        <div class="paper-stack paper-2"></div>
        <div class="floating-paper floating-paper-1"></div>
        <div class="floating-paper floating-paper-2"></div>
        <div class="floating-paper floating-paper-3"></div>
        
        <!-- Additional Office Elements -->
        <div class="printer-icon printer-1"></div>
        <div class="printer-icon printer-2"></div>
        <div class="ink-drop ink-1"></div>
        <div class="ink-drop ink-2"></div>
        <div class="ink-drop ink-3"></div>
        <div class="stapler stapler-1"></div>
        <div class="pen pen-1"></div>
        <div class="pen pen-2"></div>
        <div class="sticky-note sticky-1"></div>
        <div class="sticky-note sticky-2"></div>
        <div class="folder folder-1"></div>
        <div class="magnifier magnifier-1"></div>
        
        <!-- Geometric Decorative Patterns -->
        <div class="star star-1"></div>
        <div class="star star-2"></div>
        <div class="star star-3"></div>
        <div class="star star-4"></div>
        <div class="star star-5"></div>
        <div class="star star-6"></div>
        <div class="plus plus-1"></div>
        <div class="plus plus-2"></div>
        <div class="plus plus-3"></div>
        <div class="plus plus-4"></div>
        <div class="line line-1"></div>
        <div class="line line-2"></div>
        <div class="line line-3"></div>
        <div class="line line-4"></div>
        <div class="triangle triangle-1"></div>
        <div class="triangle triangle-2"></div>
        <div class="triangle triangle-3"></div>
        <div class="small-circle small-circle-1"></div>
        <div class="small-circle small-circle-2"></div>
        <div class="small-circle small-circle-3"></div>
        <div class="small-circle small-circle-4"></div>
        <div class="zigzag zigzag-1"></div>
        <div class="zigzag zigzag-2"></div>
        <div class="dashed-circle dashed-circle-1"></div>
        <div class="dashed-circle dashed-circle-2"></div>
        <div class="curve curve-1"></div>
        <div class="curve curve-2"></div>
        
        <div class="login-card">
            <div class="card-header">
                <div class="port-logo">
                    <i class="fas fa-anchor"></i>
                </div>
                <div class="welcome-circle">
                    <h2>Welcome to the website</h2>
                    <p>Sri Lanka Ports Authority - Supply Logistics & Procurement Administration</p>
                </div>
                <a href="#" class="create-account" onclick="event.preventDefault(); alert('Contact admin to create account');">Create Account</a>
            </div>
            
            <div class="card-body">
                <h3>USER LOGIN</h3>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php echo Security::csrfField(); ?>
                    <div class="form-group">
                        <div class="input-icon-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" id="username" placeholder="Username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" placeholder="Password" required>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-label">
                            <input type="checkbox" name="remember">
                            <span>Remember</span>
                        </label>
                        <a href="#" class="forgot-link" onclick="event.preventDefault(); alert('Contact admin to reset password');">Forgot password ?</a>
                    </div>
                    
                    <button type="submit" class="login-button">LOGIN</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/login.js"></script>
    <script>
        // Show error message as toast if exists
        <?php if (!empty($error_message)): ?>
            document.addEventListener('DOMContentLoaded', () => {
                Toast.error('<?php echo addslashes($error_message); ?>');
            });
        <?php endif; ?>
    </script>
</body>
</html>