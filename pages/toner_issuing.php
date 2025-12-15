<?php
require_once '../includes/db.php';
require_login();

$page_title = "Toner Issuing - SLPA System";
$additional_css = ['../assets/css/toner-issuing.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/toner-issuing.js?v=' . time()];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages first
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Issue new toner
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'issue') {
    $toner_id = (int)$_POST['toner_id'];
    $toner_model = sanitize_input($_POST['toner_model']);
    $code = sanitize_input($_POST['code'] ?? '');
    $lot = sanitize_input($_POST['lot'] ?? ''); // Get LOT from form
    $stock = sanitize_input($_POST['stock']);
    $color = sanitize_input($_POST['color'] ?? '');
    $printer_model = sanitize_input($_POST['printer_model'] ?? '');
    $printer_no = sanitize_input($_POST['printer_no'] ?? '');
    $division = sanitize_input($_POST['division']);
    $section = sanitize_input($_POST['section']);
    $request_officer = sanitize_input($_POST['request_officer']);
    $receiver_name = sanitize_input($_POST['receiver_name']);
    $receiver_emp_no = sanitize_input($_POST['receiver_emp_no']);
    $quantity = (int)$_POST['quantity'];
    $issue_date = sanitize_input($_POST['issue_date']);
    $remarks = sanitize_input($_POST['remarks']);
    
    // Auto-assign LOT if empty - get first available LOT for this toner
    if (empty($lot)) {
        $lot_result = $conn->query("SELECT lot FROM toner_receiving WHERE toner_id = $toner_id AND lot IS NOT NULL AND lot != '' LIMIT 1");
        if ($lot_result && $lot_row = $lot_result->fetch_assoc()) {
            $lot = $lot_row['lot'];
        }
    }
    
    // Validate required fields
    if (empty($toner_id) || empty($stock) || empty($division) || empty($section) || empty($quantity) || empty($issue_date)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Insert into database
        try {
            $stmt = $conn->prepare("INSERT INTO toner_issuing (toner_id, toner_model, code, lot, stock, color, printer_model, printer_no, division, section, request_officer, receiver_name, receiver_emp_no, quantity, issue_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssssssssss", $toner_id, $toner_model, $code, $lot, $stock, $color, $printer_model, $printer_no, $division, $section, $request_officer, $receiver_name, $receiver_emp_no, $quantity, $issue_date, $remarks);
            
            if ($stmt->execute()) {
                // Update toner stock
                $stock_field = ($stock == 'JCT') ? 'jct_stock' : 'uct_stock';
                $update_stmt = $conn->prepare("UPDATE toner_master SET $stock_field = $stock_field - ? WHERE toner_id = ? AND $stock_field >= ?");
                $update_stmt->bind_param("iii", $quantity, $toner_id, $quantity);
                
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = 'Toner issued successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Toner issued but stock update failed!';
                    $_SESSION['message_type'] = 'warning';
                }
                $update_stmt->close();
            } else {
                $_SESSION['message'] = 'Error issuing toner: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Edit toner issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $issue_id = (int)$_POST['issue_id'];
    $toner_id = (int)$_POST['toner_id'];
    $toner_model = sanitize_input($_POST['toner_model']);
    $code = sanitize_input($_POST['code'] ?? '');
    $lot = sanitize_input($_POST['lot'] ?? ''); // Get LOT from form
    $stock = sanitize_input($_POST['stock']);
    $color = sanitize_input($_POST['color'] ?? '');
    $printer_model = sanitize_input($_POST['printer_model'] ?? '');
    $printer_no = sanitize_input($_POST['printer_no'] ?? '');
    $division = sanitize_input($_POST['division']);
    $section = sanitize_input($_POST['section']);
    $request_officer = sanitize_input($_POST['request_officer']);
    $receiver_name = sanitize_input($_POST['receiver_name']);
    $receiver_emp_no = sanitize_input($_POST['receiver_emp_no']);
    $quantity = (int)$_POST['quantity'];
    $issue_date = sanitize_input($_POST['issue_date']);
    $remarks = sanitize_input($_POST['remarks']);
    
    // Auto-assign LOT if empty
    if (empty($lot)) {
        $lot_result = $conn->query("SELECT lot FROM toner_receiving WHERE toner_id = $toner_id AND lot IS NOT NULL AND lot != '' LIMIT 1");
        if ($lot_result && $lot_row = $lot_result->fetch_assoc()) {
            $lot = $lot_row['lot'];
        }
    }
    
    // Validate required fields
    if (empty($issue_id) || empty($toner_id) || empty($stock) || empty($division) || empty($section) || empty($quantity) || empty($issue_date)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        try {
            // Get original quantity for stock adjustment
            $orig_stmt = $conn->prepare("SELECT quantity, stock, toner_id FROM toner_issuing WHERE issue_id = ?");
            $orig_stmt->bind_param("i", $issue_id);
            $orig_stmt->execute();
            $orig_result = $orig_stmt->get_result();
            $original = $orig_result->fetch_assoc();
            $orig_stmt->close();
            
            if ($original) {
                // Update the issue record
                $stmt = $conn->prepare("UPDATE toner_issuing SET toner_id = ?, toner_model = ?, code = ?, lot = ?, stock = ?, color = ?, printer_model = ?, printer_no = ?, division = ?, section = ?, request_officer = ?, receiver_name = ?, receiver_emp_no = ?, quantity = ?, issue_date = ?, remarks = ? WHERE issue_id = ?");
                $stmt->bind_param("isssssssssssssssi", $toner_id, $toner_model, $code, $lot, $stock, $color, $printer_model, $printer_no, $division, $section, $request_officer, $receiver_name, $receiver_emp_no, $quantity, $issue_date, $remarks, $issue_id);
                
                if ($stmt->execute()) {
                    // Adjust stock - restore original quantity then deduct new quantity
                    $orig_stock_field = ($original['stock'] == 'JCT') ? 'jct_stock' : 'uct_stock';
                    $new_stock_field = ($stock == 'JCT') ? 'jct_stock' : 'uct_stock';
                    
                    // Restore original stock
                    $restore_stmt = $conn->prepare("UPDATE toner_master SET $orig_stock_field = $orig_stock_field + ? WHERE toner_id = ?");
                    $restore_stmt->bind_param("ii", $original['quantity'], $original['toner_id']);
                    $restore_stmt->execute();
                    $restore_stmt->close();
                    
                    // Deduct new quantity
                    $deduct_stmt = $conn->prepare("UPDATE toner_master SET $new_stock_field = $new_stock_field - ? WHERE toner_id = ? AND $new_stock_field >= ?");
                    $deduct_stmt->bind_param("iii", $quantity, $toner_id, $quantity);
                    $deduct_stmt->execute();
                    $deduct_stmt->close();
                    
                    $_SESSION['message'] = 'Toner issue updated successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error updating toner issue: ' . $conn->error;
                    $_SESSION['message_type'] = 'error';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = 'Issue record not found!';
                $_SESSION['message_type'] = 'error';
            }
        } catch (Exception $e) {
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Delete issue record
if (isset($_GET['delete'])) {
    $issue_id = (int)$_GET['delete'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM toner_issuing WHERE issue_id = ?");
        $stmt->bind_param("i", $issue_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Issue record deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error deleting issue record: ' . $conn->error;
            $_SESSION['message_type'] = 'error';
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['message'] = 'Database error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    // Redirect to prevent URL manipulation
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get data for display
$issues = [];
$toners = [];

// Get toner issues from database
try {
    $result = $conn->query("SELECT * FROM toner_issuing ORDER BY issue_date DESC, issue_id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $issues[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get individual toners from receiving table (each LOT as separate option) with current stock from master
try {
    $result = $conn->query("SELECT 
        tr.receive_id,
        tr.toner_id,
        tm.toner_model,
        tm.compatible_printers,
        tr.color,
        tr.lot,
        tr.pr_no,
        tm.jct_stock as jct_quantity,
        tm.uct_stock as uct_quantity,
        tr.stock
    FROM toner_receiving tr
    INNER JOIN toner_master tm ON tr.toner_id = tm.toner_id
    WHERE tr.lot IS NOT NULL AND tr.lot != ''
    ORDER BY tm.toner_model, tr.lot");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $toners[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get LOT stocks with toner information from toner_receiving
$lot_stocks = [];
try {
    $result = $conn->query("SELECT tr.stock, tr.lot, tr.toner_id, tm.toner_model, tr.color 
                           FROM toner_receiving tr 
                           LEFT JOIN toner_master tm ON tr.toner_id = tm.toner_id 
                           WHERE tr.lot IS NOT NULL AND tr.lot != '' 
                           ORDER BY tr.lot");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lot_stocks[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Calculate statistics
$total_issues = count($issues);
$recent_issues = 0;
$low_stock_items = 0;

foreach ($issues as $issue) {
    if (strtotime($issue['issue_date']) >= strtotime('-30 days')) {
        $recent_issues++;
    }
}

// For low stock check, we need to get aggregated stock from toner_master
try {
    $stock_check = $conn->query("SELECT toner_id, jct_stock, uct_stock, reorder_level 
                                  FROM toner_master 
                                  WHERE (jct_stock + uct_stock) <= reorder_level");
    if ($stock_check) {
        $low_stock_items = $stock_check->num_rows;
    }
} catch (Exception $e) {
    $low_stock_items = 0;
}

include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>
                    <i class="fas fa-share-square"></i>
                    Toner Issuing Management
                </h1>
                <p>Track and manage toner distributions across departments</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('issueTonerModal')">
                    <i class="fas fa-plus"></i>
                    Issue New Toner
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='toner_master.php'">
                    <i class="fas fa-box"></i>
                    Toner Master
                </button>
            </div>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $_SESSION['message']; ?>
        </div>
        <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-value"><?php echo $total_issues; ?></div>
            <div class="stat-label">Total Issues</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-value"><?php echo $recent_issues; ?></div>
            <div class="stat-label">This Month</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value"><?php echo $low_stock_items; ?></div>
            <div class="stat-label">Low Stock Items</div>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-value"><?php echo count($toners); ?></div>
            <div class="stat-label">Available Toners</div>
        </div>
    </div>

    <!-- Issues Table -->
    <div class="content-card">
        <div class="card-header">
            <h2>
                <i class="fas fa-list"></i>
                Toner Issue Records
            </h2>
            <div class="header-actions">
                <button class="btn btn-secondary btn-sm" onclick="openModal('printModal')">
                    <i class="fas fa-print"></i>
                    Print
                </button>
                <button class="btn btn-secondary btn-sm" onclick="refreshTable()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search issues..." onkeyup="filterTable()">
            </div>
            <div style="display: flex; gap: 10px;">
                <select id="divisionFilter" class="form-control" onchange="filterTable()">
                    <option value="">All Divisions</option>
                    <option value="Board of Directors">Board of Directors</option>
                    <option value="Civil Engineering Division">Civil Engineering Division</option>
                    <option value="Communication & Public Relations Division">Communication & Public Relations Division</option>
                    <option value="Container Freight Station">Container Freight Station</option>
                    <option value="Contracts & Designs Division">Contracts & Designs Division</option>
                    <option value="Covid-19 Prevention Action Committee">Covid-19 Prevention Action Committee</option>
                    <option value="Development Division">Development Division</option>
                    <option value="Electrical & Electronics Engineering Division">Electrical & Electronics Engineering Division</option>
                    <option value="Engineering Division">Engineering Division</option>
                    <option value="Finance Division">Finance Division</option>
                    <option value="Galle Harbour">Galle Harbour</option>
                    <option value="Government Audit Branch">Government Audit Branch</option>
                    <option value="Heads of Divisions">Heads of Divisions</option>
                    <option value="Human Resources Division">Human Resources Division</option>
                    <option value="Information Systems Division">Information Systems Division</option>
                    <option value="Internal Audit Division">Internal Audit Division</option>
                    <option value="JCT Ltd">JCT Ltd</option>
                    <option value="KKS Harbour">KKS Harbour</option>
                    <option value="Legal Division">Legal Division</option>
                    <option value="Logistics Division">Logistics Division</option>
                    <option value="Mahapola Ports & Maritime Academy">Mahapola Ports & Maritime Academy</option>
                    <option value="Marine Engineering Division">Marine Engineering Division</option>
                    <option value="Marketing & Business Development Division">Marketing & Business Development Division</option>
                    <option value="Mechanical & Maintenance Division">Mechanical & Maintenance Division</option>
                    <option value="Medical Unit">Medical Unit</option>
                    <option value="Navigation & Estate Division">Navigation & Estate Division</option>
                    <option value="Occupational Health & Safety Division">Occupational Health & Safety Division</option>
                    <option value="Operation Division">Operation Division</option>
                    <option value="Planning and Development Division">Planning and Development Division</option>
                    <option value="Port Security Division">Port Security Division</option>
                    <option value="Supplies Division">Supplies Division</option>
                    <option value="Premises and Land Management Division">Premises and Land Management Division</option>
                    <option value="Secretariat Division">Secretariat Division</option>
                    <option value="Security Division">Security Division</option>
                    <option value="Mechanical Plant Division">Mechanical Plant Division</option>
                    <option value="Mechanical Works Division">Mechanical Works Division</option>
                    <option value="Terminal Operations Division">Terminal Operations Division</option>
                    <option value="Training & Development Unit">Training & Development Unit</option>
                    <option value="Trincomalee Harbour">Trincomalee Harbour</option>
                    <option value="Trade Unions">Trade Unions</option>
                    <option value="UCT Ltd">UCT Ltd</option>
                    <option value="Other">Other</option>
                </select>
                <select id="stockFilter" class="form-control" onchange="filterTable()">
                    <option value="">All Stocks</option>
                    <option value="JCT">JCT</option>
                    <option value="UCT">UCT</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (count($issues) > 0): ?>
            <table class="data-table" id="issuesTable">
                <thead>
                    <tr>
                        <th>ISSUE DATE</th>
                        <th>TONER MODEL</th>
                        <th>IS CODE</th>
                        <th>COLOR</th>
                        <th>STOCK</th>
                        <th>LOT</th>
                        <th>PRINTER</th>
                        <th>DIVISION</th>
                        <th>SECTION</th>
                        <th>RECEIVER</th>
                        <th>QTY</th>
                        <th>REMARKS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($issues as $issue): ?>
                    <tr data-division="<?php echo htmlspecialchars($issue['division']); ?>" 
                        data-stock="<?php echo htmlspecialchars($issue['stock']); ?>" 
                        data-date="<?php echo htmlspecialchars($issue['issue_date']); ?>">
                        <td><?php echo date('Y-m-d', strtotime($issue['issue_date'])); ?></td>
                        <td><?php echo htmlspecialchars($issue['toner_model']); ?></td>
                        <td><?php echo htmlspecialchars($issue['code']); ?></td>
                        <td>
                            <span class="color-badge" style="background-color: <?php echo htmlspecialchars(strtolower($issue['color'])); ?>">
                                <?php echo htmlspecialchars($issue['color']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="stock-badge stock-<?php echo htmlspecialchars(strtolower($issue['stock'])); ?>">
                                <?php echo htmlspecialchars($issue['stock']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background: linear-gradient(135deg, rgb(102,126,234) 0%, rgb(118,75,162) 100%); color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">
                                <?php echo htmlspecialchars($issue['lot'] ?: 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="printer-info">
                                <div class="printer-model"><?php echo htmlspecialchars($issue['printer_model'] ?: 'N/A'); ?></div>
                                <?php if (!empty($issue['printer_no'])): ?>
                                    <div class="printer-no"><?php echo htmlspecialchars($issue['printer_no']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($issue['division']); ?></td>
                        <td><?php echo htmlspecialchars($issue['section']); ?></td>
                        <td>
                            <div class="receiver-info">
                                <div class="receiver-name"><?php echo htmlspecialchars($issue['receiver_name'] ?: 'N/A'); ?></div>
                                <?php if (!empty($issue['receiver_emp_no'])): ?>
                                    <div class="receiver-emp"><?php echo htmlspecialchars($issue['receiver_emp_no']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="quantity-badge"><?php echo htmlspecialchars($issue['quantity']); ?></span>
                        </td>
                        <td class="remarks-cell"><?php echo htmlspecialchars($issue['remarks'] ?: '-'); ?></td>
                        <td class="table-actions-cell">
                            <button class="btn btn-view btn-sm" onclick="viewIssue(<?php echo $issue['issue_id']; ?>)" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-edit btn-sm" onclick="editIssue(<?php echo $issue['issue_id']; ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-delete btn-sm" onclick="confirmDelete(<?php echo $issue['issue_id']; ?>)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <h3>No Issue Records Found</h3>
                <p>Start by issuing a new toner using the button above.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Issue Toner Modal -->
<div id="issueTonerModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Issue New Toner</h2>
            <span class="modal-close" onclick="closeModal('issueTonerModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="issue">
            <div class="modal-body">
                <div class="form-section">
                    <h4><i class="fas fa-toner"></i> Toner Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Select Toner (LOT) <span class="required">*</span></label>
                            <select name="toner_id" id="tonerSelect" class="form-control" required onchange="updateTonerDetails()">
                                <option value="">Select Toner with LOT</option>
                                <?php foreach ($toners as $toner): ?>
                                    <option value="<?php echo $toner['toner_id']; ?>" 
                                            data-model="<?php echo htmlspecialchars($toner['toner_model']); ?>"
                                            data-printers="<?php echo htmlspecialchars($toner['compatible_printers']); ?>"
                                            data-color="<?php echo htmlspecialchars($toner['color']); ?>"
                                            data-lot="<?php echo htmlspecialchars($toner['lot']); ?>"
                                            data-pr="<?php echo htmlspecialchars($toner['pr_no'] ?? ''); ?>"
                                            data-stock="<?php echo htmlspecialchars($toner['stock']); ?>"
                                            data-jct="<?php echo $toner['jct_quantity']; ?>"
                                            data-uct="<?php echo $toner['uct_quantity']; ?>">
                                        <?php echo htmlspecialchars($toner['toner_model']); ?> 
                                        (LOT: <?php echo htmlspecialchars($toner['lot']); ?>) 
                                        - JCT: <?php echo $toner['jct_quantity']; ?>, 
                                        UCT: <?php echo $toner['uct_quantity']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="stockInfoBox" style="display: none; margin-top: 8px; padding: 10px; background: #f8f9fa; border-left: 3px solid #007bff; border-radius: 4px; font-size: 13px;">
                                <div style="color: #495057; font-weight: 500; margin-bottom: 5px;">📦 Available Stock:</div>
                                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                    <span><strong>JCT:</strong> <span id="jctQty" style="color: #28a745;">0</span></span>
                                    <span><strong>UCT:</strong> <span id="uctQty" style="color: #28a745;">0</span></span>
                                    <span><strong>Total:</strong> <span id="totalQty" style="color: #007bff; font-weight: 600;">0</span> units</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Stock Location <span class="required">*</span></label>
                            <select name="stock" id="stockSelect" class="form-control" required onchange="updateAvailableStock()">
                                <option value="">Select Stock</option>
                                <option value="JCT">JCT</option>
                                <option value="UCT">UCT</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Toner Model</label>
                            <input type="text" name="toner_model" id="tonerModelDisplay" class="form-control" readonly>
                        </div>

                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Color</label>
                            <input type="text" name="color" id="colorDisplay" class="form-control" readonly>
                        </div>
                        <div class="form-col">
                            <label class="form-label">LOT Number</label>
                            <input type="text" name="lot" id="lotDisplay" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Quantity <span class="required">*</span></label>
                            <input type="number" name="quantity" class="form-control" required min="1">
                        </div>
                        <div class="form-col">
                            <!-- Empty column for layout balance -->
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-print"></i> Printer Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Printer Model</label>
                            <select name="printer_model" id="printerModelSelect" class="form-control">
                                <option value="">Select toner first</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">PR Number</label>
                            <input type="text" name="printer_no" class="form-control" placeholder="Enter PR number">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-map-marker-alt"></i> Location Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Division <span class="required">*</span></label>
                            <select name="division" class="form-control" required>
                                <option value="">Select Division</option>
                                <option value="Board of Directors">Board of Directors</option>
                                <option value="Civil Engineering Division">Civil Engineering Division</option>
                                <option value="Communication & Public Relations Division">Communication & Public Relations Division</option>
                                <option value="Container Freight Station">Container Freight Station</option>
                                <option value="Contracts & Designs Division">Contracts & Designs Division</option>
                                <option value="Covid-19 Prevention Action Committee">Covid-19 Prevention Action Committee</option>
                                <option value="Development Division">Development Division</option>
                                <option value="Electrical & Electronics Engineering Division">Electrical & Electronics Engineering Division</option>
                                <option value="Engineering Division">Engineering Division</option>
                                <option value="Finance Division">Finance Division</option>
                                <option value="Galle Harbour">Galle Harbour</option>
                                <option value="Government Audit Branch">Government Audit Branch</option>
                                <option value="Heads of Divisions">Heads of Divisions</option>
                                <option value="Human Resources Division">Human Resources Division</option>
                                <option value="Information Systems Division">Information Systems Division</option>
                                <option value="Internal Audit Division">Internal Audit Division</option>
                                <option value="JCT Ltd">JCT Ltd</option>
                                <option value="KKS Harbour">KKS Harbour</option>
                                <option value="Legal Division">Legal Division</option>
                                <option value="Logistics Division">Logistics Division</option>
                                <option value="Mahapola Ports & Maritime Academy">Mahapola Ports & Maritime Academy</option>
                                <option value="Marine Engineering Division">Marine Engineering Division</option>
                                <option value="Marketing & Business Development Division">Marketing & Business Development Division</option>
                                <option value="Mechanical & Maintenance Division">Mechanical & Maintenance Division</option>
                                <option value="Medical Unit">Medical Unit</option>
                                <option value="Navigation & Estate Division">Navigation & Estate Division</option>
                                <option value="Occupational Health & Safety Division">Occupational Health & Safety Division</option>
                                <option value="Operation Division">Operation Division</option>
                                <option value="Planning and Development Division">Planning and Development Division</option>
                                <option value="Port Security Division">Port Security Division</option>
                                <option value="Supplies Division">Supplies Division</option>
                                <option value="Premises and Land Management Division">Premises and Land Management Division</option>
                                <option value="Secretariat Division">Secretariat Division</option>
                                <option value="Security Division">Security Division</option>
                                <option value="Mechanical Plant Division">Mechanical Plant Division</option>
                                <option value="Mechanical Works Division">Mechanical Works Division</option>
                                <option value="Terminal Operations Division">Terminal Operations Division</option>
                                <option value="Training & Development Unit">Training & Development Unit</option>
                                <option value="Trincomalee Harbour">Trincomalee Harbour</option>
                                <option value="Trade Unions">Trade Unions</option>
                                <option value="UCT Ltd">UCT Ltd</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Section <span class="required">*</span></label>
                            <input type="text" name="section" class="form-control" required placeholder="Enter section">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">IS Code</label>
                            <input type="text" name="code" id="codeDisplay" class="form-control" placeholder="Enter IS code (optional)">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Request Officer</label>
                            <input type="text" name="request_officer" class="form-control" placeholder="Enter request officer name">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-user"></i> Receiver Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Receiver Name</label>
                            <input type="text" name="receiver_name" class="form-control" placeholder="Enter receiver name">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Receiver Employee No</label>
                            <input type="text" name="receiver_emp_no" class="form-control" placeholder="Enter employee number">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-calendar-alt"></i> Issue Details</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Issue Date <span class="required">*</span></label>
                            <input type="date" name="issue_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Enter any additional remarks"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('issueTonerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i>
                    Issue Toner
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Issue Modal -->
<div id="viewIssueModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-eye"></i> View Toner Issue</h2>
            <span class="modal-close" onclick="closeModal('viewIssueModal')">&times;</span>
        </div>
        <div class="modal-body" id="viewIssueContent">
            <!-- Content will be populated by JavaScript -->
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewIssueModal')">Close</button>
        </div>
    </div>
</div>

<!-- Edit Issue Modal -->
<div id="editIssueModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Toner Issue</h2>
            <span class="modal-close" onclick="closeModal('editIssueModal')">&times;</span>
        </div>
        <form method="POST" action="" id="editIssueForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="issue_id" id="editIssueId">
            <div class="modal-body">
                <div class="form-section">
                    <h4><i class="fas fa-toner"></i> Toner Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Select Toner (LOT) <span class="required">*</span></label>
                            <select name="toner_id" id="editTonerSelect" class="form-control" required onchange="updateEditTonerDetails()">
                                <option value="">Select Toner with LOT</option>
                                <?php foreach ($toners as $toner): ?>
                                    <option value="<?php echo $toner['toner_id']; ?>" 
                                            data-model="<?php echo htmlspecialchars($toner['toner_model']); ?>"
                                            data-printers="<?php echo htmlspecialchars($toner['compatible_printers']); ?>"
                                            data-color="<?php echo htmlspecialchars($toner['color']); ?>"
                                            data-lot="<?php echo htmlspecialchars($toner['lot']); ?>"
                                            data-stock="<?php echo htmlspecialchars($toner['stock']); ?>"
                                            data-jct="<?php echo $toner['jct_quantity']; ?>"
                                            data-uct="<?php echo $toner['uct_quantity']; ?>">
                                        <?php echo htmlspecialchars($toner['toner_model']); ?> 
                                        (LOT: <?php echo htmlspecialchars($toner['lot']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Stock Location <span class="required">*</span></label>
                            <select name="stock" id="editStockSelect" class="form-control" required onchange="updateEditAvailableStock()">
                                <option value="">Select Stock</option>
                                <option value="JCT">JCT</option>
                                <option value="UCT">UCT</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Toner Model</label>
                            <input type="text" name="toner_model" id="editTonerModelDisplay" class="form-control" readonly>
                        </div>

                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Color</label>
                            <input type="text" name="color" id="editColorDisplay" class="form-control" readonly>
                        </div>
                        <div class="form-col">
                            <label class="form-label">LOT Number</label>
                            <input type="text" name="lot" id="editLotDisplay" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Available Stock</label>
                            <input type="text" id="editAvailableStock" class="form-control" readonly>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Quantity <span class="required">*</span></label>
                            <input type="number" name="quantity" id="editQuantity" class="form-control" required min="1">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-print"></i> Printer Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Printer Model</label>
                            <select name="printer_model" id="editPrinterModel" class="form-control">
                                <option value="">Select printer model</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">PR Number</label>
                            <input type="text" name="printer_no" id="editPrinterNo" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-map-marker-alt"></i> Location Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Division <span class="required">*</span></label>
                            <select name="division" id="editDivision" class="form-control" required>
                                <option value="">Select Division</option>
                                <option value="Board of Directors">Board of Directors</option>
                                <option value="Civil Engineering Division">Civil Engineering Division</option>
                                <option value="Communication & Public Relations Division">Communication & Public Relations Division</option>
                                <option value="Container Freight Station">Container Freight Station</option>
                                <option value="Contracts & Designs Division">Contracts & Designs Division</option>
                                <option value="Covid-19 Prevention Action Committee">Covid-19 Prevention Action Committee</option>
                                <option value="Development Division">Development Division</option>
                                <option value="Electrical & Electronics Engineering Division">Electrical & Electronics Engineering Division</option>
                                <option value="Engineering Division">Engineering Division</option>
                                <option value="Finance Division">Finance Division</option>
                                <option value="Galle Harbour">Galle Harbour</option>
                                <option value="Government Audit Branch">Government Audit Branch</option>
                                <option value="Heads of Divisions">Heads of Divisions</option>
                                <option value="Human Resources Division">Human Resources Division</option>
                                <option value="Information Systems Division">Information Systems Division</option>
                                <option value="Internal Audit Division">Internal Audit Division</option>
                                <option value="JCT Ltd">JCT Ltd</option>
                                <option value="KKS Harbour">KKS Harbour</option>
                                <option value="Legal Division">Legal Division</option>
                                <option value="Logistics Division">Logistics Division</option>
                                <option value="Mahapola Ports & Maritime Academy">Mahapola Ports & Maritime Academy</option>
                                <option value="Marine Engineering Division">Marine Engineering Division</option>
                                <option value="Marketing & Business Development Division">Marketing & Business Development Division</option>
                                <option value="Mechanical & Maintenance Division">Mechanical & Maintenance Division</option>
                                <option value="Medical Unit">Medical Unit</option>
                                <option value="Navigation & Estate Division">Navigation & Estate Division</option>
                                <option value="Occupational Health & Safety Division">Occupational Health & Safety Division</option>
                                <option value="Operation Division">Operation Division</option>
                                <option value="Planning and Development Division">Planning and Development Division</option>
                                <option value="Port Security Division">Port Security Division</option>
                                <option value="Supplies Division">Supplies Division</option>
                                <option value="Premises and Land Management Division">Premises and Land Management Division</option>
                                <option value="Secretariat Division">Secretariat Division</option>
                                <option value="Security Division">Security Division</option>
                                <option value="Mechanical Plant Division">Mechanical Plant Division</option>
                                <option value="Mechanical Works Division">Mechanical Works Division</option>
                                <option value="Terminal Operations Division">Terminal Operations Division</option>
                                <option value="Training & Development Unit">Training & Development Unit</option>
                                <option value="Trincomalee Harbour">Trincomalee Harbour</option>
                                <option value="Trade Unions">Trade Unions</option>
                                <option value="UCT Ltd">UCT Ltd</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Section <span class="required">*</span></label>
                            <input type="text" name="section" id="editSection" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">IS Code</label>
                            <input type="text" name="code" id="editCodeDisplay" class="form-control" placeholder="Enter IS code (optional)">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Request Officer</label>
                            <input type="text" name="request_officer" id="editRequestOfficer" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-user"></i> Receiver Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Receiver Name</label>
                            <input type="text" name="receiver_name" id="editReceiverName" class="form-control">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Receiver Employee No</label>
                            <input type="text" name="receiver_emp_no" id="editReceiverEmpNo" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-calendar-alt"></i> Issue Details</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Issue Date <span class="required">*</span></label>
                            <input type="date" name="issue_date" id="editIssueDate" class="form-control" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" id="editRemarks" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editIssueModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Issue
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Print Modal -->
<div id="printModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-print"></i> Print Toner Issuing Report</h2>
            <span class="modal-close" onclick="closeModal('printModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-section">
                <h4><i class="fas fa-calendar-alt"></i> Date Filtering Options</h4>
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Print Type</label>
                        <select id="printType" class="form-control" onchange="toggleDateFields()">
                            <option value="all">All Records</option>
                            <option value="daily">Daily Report</option>
                            <option value="monthly">Monthly Report</option>
                            <option value="yearly">Yearly Report</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label">Report Format</label>
                        <select id="reportFormat" class="form-control">
                            <option value="summary">Summary Report</option>
                            <option value="detailed">Detailed Report</option>
                            <option value="statistics">Statistics Only</option>
                        </select>
                    </div>
                </div>
                
                <!-- Daily Date Selection -->
                <div id="dailySection" class="form-row" style="display: none;">
                    <div class="form-col">
                        <label class="form-label">Select Date</label>
                        <input type="date" id="dailyDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <!-- Monthly Selection -->
                <div id="monthlySection" class="form-row" style="display: none;">
                    <div class="form-col">
                        <label class="form-label">Select Month</label>
                        <select id="monthSelect" class="form-control">
                            <?php
                            $months = [
                                '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                                '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                                '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                            ];
                            foreach ($months as $value => $name) {
                                $selected = ($value == date('m')) ? 'selected' : '';
                                echo "<option value='$value' $selected>$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label">Select Year</label>
                        <select id="monthlyYear" class="form-control">
                            <?php
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= $current_year - 5; $year--) {
                                $selected = ($year == $current_year) ? 'selected' : '';
                                echo "<option value='$year' $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <!-- Yearly Selection -->
                <div id="yearlySection" class="form-row" style="display: none;">
                    <div class="form-col">
                        <label class="form-label">Select Year</label>
                        <select id="yearSelect" class="form-control">
                            <?php
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= $current_year - 10; $year--) {
                                $selected = ($year == $current_year) ? 'selected' : '';
                                echo "<option value='$year' $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <!-- Custom Date Range -->
                <div id="customSection" class="form-row" style="display: none;">
                    <div class="form-col">
                        <label class="form-label">From Date</label>
                        <input type="date" id="fromDate" class="form-control">
                    </div>
                    <div class="form-col">
                        <label class="form-label">To Date</label>
                        <input type="date" id="toDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4><i class="fas fa-cogs"></i> Print Options</h4>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="includeStatistics" checked>
                        <span class="checkmark"></span>
                        Include Statistics Summary
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="includeDivision" checked>
                        <span class="checkmark"></span>
                        Include Division Information
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="includePrinter" checked>
                        <span class="checkmark"></span>
                        Include Printer Details
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="includeRemarks">
                        <span class="checkmark"></span>
                        Include Remarks Column
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('printModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="generateIssuingPrintReport()">
                <i class="fas fa-print"></i> Generate Print
            </button>
        </div>
    </div>
</div>

<script>
// Store issues data for JavaScript access - MUST be before footer/js includes
const issuesData = <?php echo json_encode($issues); ?>;
const tonersData = <?php echo json_encode($toners); ?>;
const lotStocksData = <?php echo json_encode($lot_stocks); ?>;

// Debug output
console.log('=== TONER ISSUING DATA LOADED ===');
console.log('LOT Stocks Data:', lotStocksData);
console.log('LOT Stocks Count:', lotStocksData ? lotStocksData.length : 0);
if (lotStocksData && lotStocksData.length > 0) {
    console.log('First LOT entry:', lotStocksData[0]);
}
console.log('==================================');
</script>

<?php include '../includes/footer.php'; ?>
