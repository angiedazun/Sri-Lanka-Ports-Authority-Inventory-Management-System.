<?php
require_once '../includes/db.php';
require_login();

// Check if user is admin - only admins can access user management
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'Access denied! Only administrators can manage users.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

$page_title = "User Management - SLPA System";
$additional_css = ['../assets/css/users.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/users.js?v=' . time()];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Add missing columns to existing users table if they don't exist
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) AFTER password");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(100) AFTER full_name");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin', 'manager', 'user') DEFAULT 'user' AFTER email");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) AFTER role");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) AFTER department");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active' AFTER phone");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME AFTER status");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER last_login");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");

// Add new user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $role = sanitize_input($_POST['role']);
    $department = sanitize_input($_POST['department'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $status = sanitize_input($_POST['status']);
    
    if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $_SESSION['message'] = 'Username or email already exists!';
            $_SESSION['message_type'] = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, department, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $username, $hashed_password, $full_name, $email, $role, $department, $phone, $status);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'User added successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error adding user!';
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Update user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $user_id = (int)$_POST['user_id'];
    $username = sanitize_input($_POST['username']);
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $role = sanitize_input($_POST['role']);
    $department = sanitize_input($_POST['department'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $status = sanitize_input($_POST['status']);
    $password = $_POST['password'] ?? '';
    
    if (empty($user_id) || empty($username) || empty($full_name) || empty($email)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $check_stmt->bind_param("ssi", $username, $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $_SESSION['message'] = 'Username or email already exists!';
            $_SESSION['message_type'] = 'error';
        } else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=?, email=?, role=?, department=?, phone=?, status=? WHERE user_id=?");
                $stmt->bind_param("ssssssssi", $username, $hashed_password, $full_name, $email, $role, $department, $phone, $status, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, role=?, department=?, phone=?, status=? WHERE user_id=?");
                $stmt->bind_param("sssssssi", $username, $full_name, $email, $role, $department, $phone, $status, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'User updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error updating user!';
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Delete user
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = 'You cannot delete your own account!';
        $_SESSION['message_type'] = 'error';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'User deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error deleting user!';
            $_SESSION['message_type'] = 'error';
        }
        $stmt->close();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get all active users who can login (have username, password, and active status)
$users = [];
try {
    $result = $conn->query("SELECT * FROM users WHERE username IS NOT NULL AND username != '' AND password IS NOT NULL AND password != '' AND status = 'active' ORDER BY created_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
} catch (Exception $e) {
    $message = 'Database error!';
    $message_type = 'error';
}

// Calculate statistics
$total_users = count($users);
$active_users = 0;
$inactive_users = 0;
$admin_users = 0;

foreach ($users as $user) {
    if ($user['status'] == 'active') $active_users++;
    else $inactive_users++;
    if ($user['role'] == 'admin') $admin_users++;
}

include '../includes/header.php';
?>

<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>
                    <i class="fas fa-users-cog"></i>
                    User Management
                </h1>
                <p>Manage system users and access control</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('addUserModal')">
                    <i class="fas fa-user-plus"></i>
                    Add User
                </button>
            </div>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?php echo $total_users; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-value"><?php echo $active_users; ?></div>
            <div class="stat-label">Active Users</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-user-times"></i>
            </div>
            <div class="stat-value"><?php echo $inactive_users; ?></div>
            <div class="stat-label">Inactive Users</div>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-value"><?php echo $admin_users; ?></div>
            <div class="stat-label">Administrators</div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="content-card">
        <div class="card-header">
            <h2>
                <i class="fas fa-list"></i>
                User List
            </h2>
            <div class="header-actions">
                <button class="btn btn-secondary btn-sm" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    Print
                </button>
                <button class="btn btn-secondary btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search users..." onkeyup="filterTable()">
            </div>
            <div style="display: flex; gap: 10px;">
                <select id="roleFilter" class="form-control" onchange="filterTable()">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="user">User</option>
                </select>
                <select id="statusFilter" class="form-control" onchange="filterTable()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <?php if (empty($users)): ?>
            <div class="no-data">
                <i class="fas fa-users"></i>
                <h3>No Users Found</h3>
                <p>Start by adding your first user.</p>
                <button class="btn btn-primary" onclick="openModal('addUserModal')">
                    <i class="fas fa-user-plus"></i>
                    Add First User
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="usersTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <i class="fas fa-user-circle"></i>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['department'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : '<span style="color: #999;">Never</span>'; ?></td>
                                <td class="table-actions-cell">
                                    <button class="btn btn-view btn-sm" onclick="viewUser(<?php echo $user['user_id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-edit btn-sm" onclick="editUser(<?php echo $user['user_id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-delete btn-sm" onclick="confirmDelete(<?php echo $user['user_id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Add New User</h2>
            <span class="modal-close" onclick="closeModal('addUserModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" onsubmit="return validateForm('addUserForm')">
                <input type="hidden" name="action" value="add">
                
                <div class="form-section">
                    <h4><i class="fas fa-user"></i> User Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Username <span class="required">*</span></label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Password <span class="required">*</span></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-shield-alt"></i> Access Control</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Role <span class="required">*</span></label>
                            <select name="role" class="form-control" required>
                                <option value="user">User</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Status <span class="required">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Edit User</h2>
            <span class="modal-close" onclick="closeModal('editUserModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" onsubmit="return validateForm('editUserForm')">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="editUserId" name="user_id">
                
                <div class="form-section">
                    <h4><i class="fas fa-user"></i> User Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Username <span class="required">*</span></label>
                            <input type="text" id="editUsername" name="username" class="form-control" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" id="editFullName" name="full_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Email <span class="required">*</span></label>
                            <input type="email" id="editEmail" name="email" class="form-control" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">New Password <small>(leave blank to keep current)</small></label>
                            <input type="password" id="editPassword" name="password" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Phone</label>
                            <input type="text" id="editPhone" name="phone" class="form-control">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Department</label>
                            <input type="text" id="editDepartment" name="department" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-shield-alt"></i> Access Control</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Role <span class="required">*</span></label>
                            <select id="editRole" name="role" class="form-control" required>
                                <option value="user">User</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Status <span class="required">*</span></label>
                            <select id="editStatus" name="status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div id="viewUserModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-eye"></i> User Details</h2>
            <span class="modal-close" onclick="closeModal('viewUserModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="viewUserContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewUserModal')">Close</button>
        </div>
    </div>
</div>

<script>
const usersData = <?php echo json_encode($users); ?>;
</script>

<?php include '../includes/footer.php'; ?>
