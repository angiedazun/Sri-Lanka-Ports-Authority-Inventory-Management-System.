<?php
require_once '../includes/db.php';
require_login();

$page_title = "Paper Issuing - SLPA System";
$additional_css = ['../assets/css/papers-issuing.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/papers-issuing.js?v=' . time()];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages first
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Issue new paper
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'issue') {
    $paper_id = (int)$_POST['paper_id'];
    $paper_type = sanitize_input($_POST['paper_type']);
    $code = sanitize_input($_POST['code'] ?? '');
    $lot = sanitize_input($_POST['lot'] ?? '');
    $stock = sanitize_input($_POST['stock']);
    $division = sanitize_input($_POST['division']);
    $section = sanitize_input($_POST['section']);
    $store = sanitize_input($_POST['store'] ?? '');
    $request_officer = sanitize_input($_POST['request_officer'] ?? '');
    $receiver_name = sanitize_input($_POST['receiver_name'] ?? '');
    $receiver_emp_no = sanitize_input($_POST['receiver_emp_no'] ?? '');
    $quantity = (int)$_POST['quantity'];
    $issue_date = sanitize_input($_POST['issue_date'] ?? date('Y-m-d'));
    $remarks = sanitize_input($_POST['remarks'] ?? '');
    
    // Auto-assign LOT if empty
    if (empty($lot)) {
        $lot_result = $conn->query("SELECT lot FROM papers_receiving WHERE paper_id = $paper_id AND lot IS NOT NULL AND lot != '' LIMIT 1");
        if ($lot_result && $lot_row = $lot_result->fetch_assoc()) {
            $lot = $lot_row['lot'];
        }
    }
    
    // Validate required fields
    if (empty($paper_id) || empty($stock) || empty($division) || empty($section) || empty($quantity) || empty($issue_date)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO papers_issuing (paper_id, paper_type, code, lot, stock, division, section, store, request_officer, receiver_name, receiver_emp_no, quantity, issue_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssssssss", $paper_id, $paper_type, $code, $lot, $stock, $division, $section, $store, $request_officer, $receiver_name, $receiver_emp_no, $quantity, $issue_date, $remarks);
            
            if ($stmt->execute()) {
                $stock_field = ($stock == 'JCT') ? 'jct_stock' : 'uct_stock';
                $update_stmt = $conn->prepare("UPDATE papers_master SET $stock_field = $stock_field - ? WHERE paper_id = ? AND $stock_field >= ?");
                $update_stmt->bind_param("iii", $quantity, $paper_id, $quantity);
                
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = 'Paper issued successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Paper issued but stock update failed!';
                    $_SESSION['message_type'] = 'warning';
                }
                $update_stmt->close();
            } else {
                $_SESSION['message'] = 'Error issuing paper: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $issue_id = (int)$_POST['issue_id'];
    $paper_id = (int)$_POST['paper_id'];
    $paper_type = sanitize_input($_POST['paper_type']);
    $code = sanitize_input($_POST['code'] ?? '');
    $lot = sanitize_input($_POST['lot'] ?? '');
    $stock = sanitize_input($_POST['stock']);
    $division = sanitize_input($_POST['division']);
    $section = sanitize_input($_POST['section']);
    $store = sanitize_input($_POST['store'] ?? '');
    $request_officer = sanitize_input($_POST['request_officer'] ?? '');
    $receiver_name = sanitize_input($_POST['receiver_name'] ?? '');
    $receiver_emp_no = sanitize_input($_POST['receiver_emp_no'] ?? '');
    $quantity = (int)$_POST['quantity'];
    $issue_date = sanitize_input($_POST['issue_date'] ?? date('Y-m-d'));
    $remarks = sanitize_input($_POST['remarks'] ?? '');
    
    // Auto-assign LOT if empty
    if (empty($lot)) {
        $lot_result = $conn->query("SELECT lot FROM papers_receiving WHERE paper_id = $paper_id AND lot IS NOT NULL AND lot != '' LIMIT 1");
        if ($lot_result && $lot_row = $lot_result->fetch_assoc()) {
            $lot = $lot_row['lot'];
        }
    }
    
    if (empty($issue_id) || empty($paper_id) || empty($stock) || empty($division) || empty($section) || empty($quantity) || empty($issue_date)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE papers_issuing SET paper_id=?, paper_type=?, code=?, lot=?, stock=?, division=?, section=?, store=?, request_officer=?, receiver_name=?, receiver_emp_no=?, quantity=?, issue_date=?, remarks=? WHERE issue_id=?");
            $stmt->bind_param("isssssssssssssi", $paper_id, $paper_type, $code, $lot, $stock, $division, $section, $store, $request_officer, $receiver_name, $receiver_emp_no, $quantity, $issue_date, $remarks, $issue_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Paper issue record updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error updating record: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get data for display
$issues = [];
$papers = [];

// Get paper issues from database
try {
    $result = $conn->query("SELECT * FROM papers_issuing ORDER BY issue_date DESC, issue_id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $issues[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get papers from receiving table
try {
    $result = $conn->query("SELECT 
        pr.receive_id,
        pr.paper_id,
        pm.paper_type,
        pr.lot,
        pr.jct_quantity,
        pr.uct_quantity
    FROM papers_receiving pr
    INNER JOIN papers_master pm ON pr.paper_id = pm.paper_id
    WHERE pr.lot IS NOT NULL AND pr.lot != ''
    ORDER BY pm.paper_type, pr.lot");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $papers[] = $row;
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

// For low stock check
try {
    $stock_check = $conn->query("SELECT paper_id, jct_stock, uct_stock, reorder_level 
                                  FROM papers_master 
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
                    Paper Issuing Management
                </h1>
                <p>Track and manage paper distributions across departments</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('issuePaperModal')">
                    <i class="fas fa-plus"></i>
                    Issue New Paper
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='papers_master.php'">
                    <i class="fas fa-box"></i>
                    Papers Master
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
                <i class="fas fa-archive"></i>
            </div>
            <div class="stat-value"><?php echo count($papers); ?></div>
            <div class="stat-label">Available Papers</div>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Paper Issue Records</h2>
        </div>

        <?php if (empty($issues)): ?>
            <div class="no-data">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Issue Records Found</h3>
                <p>Start by issuing your first paper.</p>
                <button class="btn btn-primary" onclick="openModal('issuePaperModal')">
                    <i class="fas fa-plus"></i> Issue First Paper
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Code</th><th>Type</th><th>Size</th><th>GSM</th>
                            <th>Stock</th><th>LOT</th><th>Division</th><th>Quantity</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($issues as $issue): ?>
                            <tr>
                                <td><?php echo $issue['issue_id']; ?></td>
                                <td><?php echo htmlspecialchars($issue['code'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($issue['paper_type']); ?></td>
                                <td><?php echo htmlspecialchars($issue['paper_size'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($issue['gsm'] ?: 'N/A'); ?></td>
                                <td><span class="stock-badge"><?php echo $issue['stock']; ?></span></td>
                                <td><?php echo htmlspecialchars($issue['lot'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($issue['division']); ?></td>
                                <td><?php echo $issue['quantity']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editIssue(<?php echo json_encode($issue); ?>)'><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-view"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Issue Paper Modal -->
<div id="issuePaperModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Issue New Paper</h2>
            <span class="modal-close" onclick="closeModal('issuePaperModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="issue">
            <div class="modal-body">
                <div class="form-section">
                    <h4><i class="fas fa-file-alt"></i> Paper Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Select Paper (LOT) <span class="required">*</span></label>
                            <select name="paper_id" id="paperSelect" class="form-control" required onchange="updatePaperDetails()">
                                <option value="">Select Paper with LOT</option>
                                <?php foreach ($papers as $paper): ?>
                                    <option value="<?php echo $paper['paper_id']; ?>"
                                        data-type="<?php echo htmlspecialchars($paper['paper_type']); ?>"
                                        data-lot="<?php echo htmlspecialchars($paper['lot'] ?? ''); ?>"
                                        data-jct="<?php echo $paper['jct_quantity']; ?>"
                                        data-uct="<?php echo $paper['uct_quantity']; ?>">
                                        <?php echo htmlspecialchars($paper['paper_type']); ?> 
                                        (LOT: <?php echo htmlspecialchars($paper['lot']); ?>) 
                                        - JCT: <?php echo $paper['jct_quantity']; ?>, 
                                        UCT: <?php echo $paper['uct_quantity']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                            <label class="form-label">Paper Type</label>
                            <input type="text" name="paper_type" id="paperTypeDisplay" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">LOT Number</label>
                            <input type="text" name="lot" id="lotDisplay" class="form-control" readonly>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" id="codeDisplay" class="form-control" placeholder="Enter code (optional)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Available Stock</label>
                            <input type="text" id="availableStock" class="form-control" readonly placeholder="Select paper and stock location">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Quantity <span class="required">*</span></label>
                            <input type="number" name="quantity" class="form-control" required min="1">
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
                                <option value="Operations & Pilotage Division">Operations & Pilotage Division</option>
                                <option value="Planning & Marketing Division">Planning & Marketing Division</option>
                                <option value="Port Security Division">Port Security Division</option>
                                <option value="Procurement & Supplies Division">Procurement & Supplies Division</option>
                                <option value="Real Estate & Lands Division">Real Estate & Lands Division</option>
                                <option value="Security Division">Security Division</option>
                                <option value="Terminal Operations Division">Terminal Operations Division</option>
                                <option value="Training & Development Unit">Training & Development Unit</option>
                                <option value="UCT Ltd">UCT Ltd</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Section <span class="required">*</span></label>
                            <input type="text" name="section" class="form-control" required placeholder="Enter section">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col" style="display:none;">
                            <label class="form-label">Store</label>
                            <input type="text" name="store" class="form-control" placeholder="Enter store location">
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
                <button type="button" class="btn btn-secondary" onclick="closeModal('issuePaperModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i>
                    Issue Paper
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Issue Modal -->
<div id="editIssueModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Paper Issue</h2>
            <span class="modal-close" onclick="closeModal('editIssueModal')">&times;</span>
        </div>
        <form method="POST" action="" id="editIssueForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="issue_id" id="editIssueId">
            <div class="modal-body">
                <div class="form-section">
                    <h4><i class="fas fa-file-alt"></i> Paper Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Paper ID <span class="required">*</span></label>
                            <input type="number" name="paper_id" id="editPaperId" class="form-control" required readonly>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Stock Location <span class="required">*</span></label>
                            <select name="stock" id="editStock" class="form-control" required>
                                <option value="">Select Stock</option>
                                <option value="JCT">JCT</option>
                                <option value="UCT">UCT</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Paper Type</label>
                            <input type="text" name="paper_type" id="editPaperType" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">LOT Number</label>
                            <input type="text" name="lot" id="editLot" class="form-control" readonly>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" id="editCode" class="form-control" placeholder="Enter code (optional)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Quantity <span class="required">*</span></label>
                            <input type="number" name="quantity" id="editQuantity" class="form-control" required min="1">
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
                                <option value="Operations & Pilotage Division">Operations & Pilotage Division</option>
                                <option value="Planning & Marketing Division">Planning & Marketing Division</option>
                                <option value="Port Security Division">Port Security Division</option>
                                <option value="Procurement & Supplies Division">Procurement & Supplies Division</option>
                                <option value="Real Estate & Lands Division">Real Estate & Lands Division</option>
                                <option value="Security Division">Security Division</option>
                                <option value="Terminal Operations Division">Terminal Operations Division</option>
                                <option value="Training & Development Unit">Training & Development Unit</option>
                                <option value="UCT Ltd">UCT Ltd</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Section <span class="required">*</span></label>
                            <input type="text" name="section" id="editSection" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col" style="display:none;">
                            <label class="form-label">Store</label>
                            <input type="text" name="store" id="editStore" class="form-control" placeholder="Enter store location">
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

<?php include '../includes/footer.php'; ?>
