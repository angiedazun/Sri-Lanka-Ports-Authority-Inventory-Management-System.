<?php
require_once '../includes/db.php';
require_login();

$page_title = "Papers Returns - SLPA System";
$additional_css = ['../assets/css/papers-returns.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/papers-returns.js'];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages first
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Return paper
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'return') {
    $paper_id = (int)$_POST['paper_id'];
    $paper_type = sanitize_input($_POST['paper_type']);
    $code = sanitize_input($_POST['code'] ?? '');
    $lot = sanitize_input($_POST['lot'] ?? '');
    
    // TEMPORARY FIX: If LOT is empty, try to get it from the issuing record with this code
    if (empty($lot) && !empty($code)) {
        $lot_result = $conn->query("SELECT lot FROM papers_issuing WHERE code = '" . $conn->real_escape_string($code) . "' AND paper_id = $paper_id AND lot IS NOT NULL AND lot != '' LIMIT 1");
        if ($lot_result && $lot_row = $lot_result->fetch_assoc()) {
            $lot = $lot_row['lot'];
        }
    }
    
    // If still empty, try to get any LOT for this paper from papers_receiving
    if (empty($lot)) {
        $lot_result = $conn->query("SELECT lot FROM papers_receiving WHERE paper_id = $paper_id AND lot IS NOT NULL AND lot != '' LIMIT 1");
        if ($lot_result && $lot_row = $lot_result->fetch_assoc()) {
            $lot = $lot_row['lot'];
        }
    }
    
    $supplier_name = sanitize_input($_POST['supplier_name'] ?? '');
    $return_date = sanitize_input($_POST['return_date']);
    $issue_date = sanitize_input($_POST['issue_date'] ?? '');
    $issue_date = !empty($issue_date) ? $issue_date : NULL;
    $receiving_date = sanitize_input($_POST['receiving_date'] ?? '');
    // Convert empty date to NULL for database
    $receiving_date = !empty($receiving_date) ? $receiving_date : NULL;
    $tender_file_no = sanitize_input($_POST['tender_file_no'] ?? '');
    $location = sanitize_input($_POST['location'] ?? '');
    $invoice = sanitize_input($_POST['invoice'] ?? '');
    $return_by = sanitize_input($_POST['return_by']);
    $division = sanitize_input($_POST['division'] ?? '');
    $section = sanitize_input($_POST['section'] ?? '');
    $quantity = (int)$_POST['quantity'];
    $reason = sanitize_input($_POST['reason']);
    $remarks = sanitize_input($_POST['remarks'] ?? '');
    
    // Validate required fields
    if (empty($paper_id) || empty($return_date) || empty($return_by) || empty($quantity) || empty($reason)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Check if this paper code has already been returned
        if (!empty($code)) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) as return_count FROM papers_return WHERE code = ? AND paper_id = ?");
            $check_stmt->bind_param("si", $code, $paper_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_data = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ($check_data['return_count'] > 0) {
                $_SESSION['message'] = 'This paper (Code: ' . htmlspecialchars($code) . ') has already been returned and cannot be returned again!';
                $_SESSION['message_type'] = 'error';
                header("Location: papers_return.php");
                exit();
            }
        }
        
        // Insert into database
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Insert return record
            $stmt = $conn->prepare("INSERT INTO papers_return (paper_id, paper_type, code, lot, supplier_name, return_date, issue_date, receiving_date, tender_file_no, location, invoice, return_by, division, section, quantity, reason, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssssssssiss", $paper_id, $paper_type, $code, $lot, $supplier_name, $return_date, $issue_date, $receiving_date, $tender_file_no, $location, $invoice, $return_by, $division, $section, $quantity, $reason, $remarks);
            
            if ($stmt->execute()) {
                // Commit transaction (stock is NOT updated for returns)
                $conn->commit();
                $_SESSION['message'] = 'Paper return recorded successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                // Rollback transaction
                $conn->rollback();
                $_SESSION['message'] = 'Error recording paper return: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Update return record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $return_id = (int)$_POST['return_id'];
    $paper_id = (int)$_POST['paper_id'];
    $paper_type = sanitize_input($_POST['paper_type']);
    $code = sanitize_input($_POST['code'] ?? '');
    $lot = sanitize_input($_POST['lot'] ?? '');
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $return_date = sanitize_input($_POST['return_date']);
    $receiving_date = sanitize_input($_POST['receiving_date']);
    
    // Auto-populate receiving_date from papers_receiving if not provided and LOT is known
    if (empty($receiving_date) && !empty($lot)) {
        $date_result = $conn->query("SELECT receive_date FROM papers_receiving WHERE lot = '" . $conn->real_escape_string($lot) . "' AND paper_id = $paper_id LIMIT 1");
        if ($date_result && $date_row = $date_result->fetch_assoc()) {
            $receiving_date = $date_row['receive_date'];
        }
    }
    
    // Convert empty date to NULL for database
    $receiving_date = !empty($receiving_date) ? $receiving_date : NULL;
    $tender_file_no = sanitize_input($_POST['tender_file_no']);
    $location = sanitize_input($_POST['location'] ?? '');
    $invoice = sanitize_input($_POST['invoice']);
    $return_by = sanitize_input($_POST['return_by']);
    $quantity = (int)$_POST['quantity'];
    $reason = sanitize_input($_POST['reason']);
    $remarks = sanitize_input($_POST['remarks']);
    
    // Validate required fields
    if (empty($return_id) || empty($paper_id) || empty($return_date) || empty($return_by) || empty($quantity) || empty($reason)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Check if code is being changed and if the new code already exists
        if (!empty($code)) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) as return_count FROM papers_return WHERE code = ? AND paper_id = ? AND return_id != ?");
            $check_stmt->bind_param("sii", $code, $paper_id, $return_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_data = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ($check_data['return_count'] > 0) {
                $_SESSION['message'] = 'This paper (Code: ' . htmlspecialchars($code) . ') has already been returned and cannot be returned again!';
                $_SESSION['message_type'] = 'error';
                header("Location: papers_return.php");
                exit();
            }
        }
        
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Update the return record
            $stmt = $conn->prepare("UPDATE papers_return SET paper_id=?, paper_type=?, code=?, lot=?, supplier_name=?, return_date=?, receiving_date=?, tender_file_no=?, location=?, invoice=?, return_by=?, quantity=?, reason=?, remarks=? WHERE return_id=?");
            $stmt->bind_param("isssssssssisssi", $paper_id, $paper_type, $code, $lot, $supplier_name, $return_date, $receiving_date, $tender_file_no, $location, $invoice, $return_by, $quantity, $reason, $remarks, $return_id);
            
            if ($stmt->execute()) {
                // Commit transaction (stock is NOT updated for returns)
                $conn->commit();
                $_SESSION['message'] = 'Return record updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                // Rollback transaction
                $conn->rollback();
                $_SESSION['message'] = 'Error updating return record: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Delete return record
if (isset($_GET['delete'])) {
    $return_id = (int)$_GET['delete'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Delete the return record (no stock updates)
        $stmt = $conn->prepare("DELETE FROM papers_return WHERE return_id = ?");
        $stmt->bind_param("i", $return_id);
        
        if ($stmt->execute()) {
            // Commit transaction (stock is NOT updated for returns)
            $conn->commit();
            $_SESSION['message'] = 'Return record deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            // Rollback transaction
            $conn->rollback();
            $_SESSION['message'] = 'Error deleting return record: ' . $conn->error;
            $_SESSION['message_type'] = 'error';
        }
        $stmt->close();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['message'] = 'Database error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    // Redirect to prevent URL manipulation
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get data for display
$returns = [];
$papers = [];
$lot_stocks = [];
$issuing_records = [];

// Get paper returns from database
try {
    $result = $conn->query("SELECT * FROM papers_return ORDER BY return_date DESC, return_id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $returns[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get papers from database for dropdown
try {
    $result = $conn->query("SELECT * FROM papers_master ORDER BY paper_type");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $papers[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get LOT stocks with paper information from papers_receiving for dropdown
try {
    $result = $conn->query("SELECT pr.lot as stock, pr.paper_id, pm.paper_type 
                           FROM papers_receiving pr 
                           LEFT JOIN papers_master pm ON pr.paper_id = pm.paper_id 
                           WHERE pr.lot IS NOT NULL AND pr.lot != '' AND pr.lot != '0' 
                           ORDER BY pr.lot");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lot_stocks[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get issuing records with all details for auto-fill (including receiving info via JOIN)
try {
    $result = $conn->query("SELECT 
        pi.code, 
        pi.paper_id, 
        pi.paper_type, 
        pi.lot, 
        pi.stock,
        pi.division,
        pi.section,
        pi.issue_date,
        pi.quantity,
        pr.supplier_name,
        pr.receive_date as receiving_date,
        pr.tender_file_no,
        pr.invoice
    FROM papers_issuing pi
    LEFT JOIN papers_receiving pr ON pi.lot = pr.lot AND pi.paper_id = pr.paper_id
    WHERE pi.code IS NOT NULL AND pi.code != '' 
    ORDER BY pi.issue_date DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $issuing_records[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Calculate statistics
$total_returns = count($returns);
$recent_returns = 0;
$total_quantity = 0;
$unique_suppliers = [];

foreach ($returns as $return) {
    // Count recent returns (this month)
    if (date('Y-m', strtotime($return['return_date'])) == date('Y-m')) {
        $recent_returns++;
    }
    
    // Sum total quantity
    $total_quantity += $return['quantity'];
    
    // Collect unique suppliers
    if (!empty($return['supplier_name'])) {
        $unique_suppliers[$return['supplier_name']] = true;
    }
}

$supplier_count = count($unique_suppliers);

// Pass data to JavaScript
echo "<script>var returnsData = " . json_encode($returns) . ";</script>";
echo "<script>var papersData = " . json_encode($papers) . ";</script>";
echo "<script>var issuingRecords = " . json_encode($issuing_records) . ";</script>";

include '../includes/header.php';
?>

<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>
                    <i class="fas fa-undo-alt"></i>
                    Papers Returns Management
                </h1>
                <p>Track and manage paper returns and defective units</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('returnPaperModal')">
                    <i class="fas fa-plus"></i>
                    Record Return
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='papers_master.php'">
                    <i class="fas fa-box"></i>
                    Papers Master
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
                <i class="fas fa-undo-alt"></i>
            </div>
            <div class="stat-value"><?php echo $total_returns; ?></div>
            <div class="stat-label">Total Returns</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-value"><?php echo $recent_returns; ?></div>
            <div class="stat-label">This Month</div>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-value"><?php echo $total_quantity; ?></div>
            <div class="stat-label">Total Quantity</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-value"><?php echo $supplier_count; ?></div>
            <div class="stat-label">Suppliers</div>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="content-card">
        <div class="card-header">
            <h2>
                <i class="fas fa-list"></i>
                Paper Return Records
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
                <input type="text" id="searchInput" placeholder="Search returns..." onkeyup="filterTable()">
            </div>
            <div style="display: flex; gap: 10px;">
                <select id="supplierFilter" class="form-control" onchange="filterTable()">
                    <option value="">All Suppliers</option>
                </select>
            </div>
        </div>

        <?php if (empty($returns)): ?>
            <div class="no-data">
                <i class="fas fa-undo-alt"></i>
                <h3>No Return Records Found</h3>
                <p>Start by recording your first paper return.</p>
                <button class="btn btn-primary" onclick="openModal('returnPaperModal')">
                    <i class="fas fa-plus"></i>
                    Record First Return
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="returnsTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Return Date</th>
                            <th>Paper Type</th>
                            <th>Code</th>
                            <th>LOT</th>
                            <th>Supplier</th>
                            <th>Issue Date</th>
                            <th>Location</th>
                            <th>Division</th>
                            <th>Section</th>
                            <th>Return By</th>
                            <th>Tender File No</th>
                            <th>Invoice</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $return): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($return['return_date'])); ?></td>
                                <td><?php echo htmlspecialchars($return['paper_type']); ?></td>
                                <td><?php echo htmlspecialchars($return['code'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php if (!empty($return['lot']) && $return['lot'] != '0'): ?>
                                        <span style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85em;">
                                            <?php echo htmlspecialchars($return['lot']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($return['supplier_name'] ?: 'N/A'); ?></td>
                                <td><?php echo $return['issue_date'] ? date('M d, Y', strtotime($return['issue_date'])) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($return['location'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($return['division'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($return['section'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($return['return_by'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $tender = $return['tender_file_no'];
                                    echo ($tender && $tender != '0') ? htmlspecialchars($tender) : '<span style="color: #999;">N/A</span>'; 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($return['invoice'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="quantity-badge"><?php echo $return['quantity']; ?></span>
                                </td>
                                <td>
                                    <div class="remarks-cell" title="<?php echo htmlspecialchars($return['reason']); ?>">
                                        <?php echo htmlspecialchars($return['reason']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="remarks-cell" title="<?php echo htmlspecialchars($return['remarks']); ?>">
                                        <?php echo htmlspecialchars($return['remarks'] ?: 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="table-actions-cell">
                                    <button class="btn btn-view btn-sm" onclick="viewReturn(<?php echo $return['return_id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-edit btn-sm" onclick="editReturn(<?php echo $return['return_id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-delete btn-sm" onclick="confirmDelete(<?php echo $return['return_id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Return Paper Modal -->
<div id="returnPaperModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-undo-alt"></i> Record Paper Return</h2>
            <span class="modal-close" onclick="closeModal('returnPaperModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="returnForm" onsubmit="return validateForm('returnForm')">
                <input type="hidden" name="action" value="return">
                
                <!-- Paper Details Section -->
                <div class="form-section">
                    <h4><i class="fas fa-file-alt"></i> Paper Details</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-barcode"></i> IS Code <span class="required">*</span>
                                <span class="label-hint">(Type to auto-fill)</span>
                            </label>
                            <div class="autocomplete-container">
                                <div class="input-with-icon">
                                    <i class="fas fa-barcode input-icon"></i>
                                    <input type="text" id="codeInput" class="form-control form-control-enhanced" 
                                           placeholder="Type or scan IS code..." 
                                           autocomplete="off" required>
                                </div>
                                <input type="hidden" id="codeHidden" name="code">
                                <div id="codeSuggestions" class="autocomplete-suggestions"></div>
                            </div>
                            <small class="form-hint">
                                <i class="fas fa-info-circle"></i> Enter the IS code from the issued paper to auto-fill details
                            </small>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Paper Type</label>
                            <input type="text" id="paperTypeDisplay" class="form-control" 
                                   placeholder="Will auto-fill from code..." readonly>
                            <input type="hidden" id="paperIdInput" name="paper_id">
                            <input type="hidden" id="paperTypeHidden" name="paper_type">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-tag"></i> LOT Number
                            </label>
                            <div class="autocomplete-container">
                                <div class="input-with-icon">
                                    <i class="fas fa-layer-group input-icon"></i>
                                    <input type="text" id="lotInput" class="form-control form-control-enhanced" 
                                           placeholder="Type to search LOT..." autocomplete="off">
                                </div>
                                <input type="hidden" id="lotHidden" name="lot">
                                <div id="lotSuggestions" class="autocomplete-suggestions"></div>
                            </div>
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-building"></i> Supplier Name
                            </label>
                            <input type="text" name="supplier_name" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                    </div>
                </div>

                <!-- Return Information Section -->
                <div class="form-section">
                    <h4><i class="fas fa-calendar-check"></i> Return Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-calendar-day"></i> Return Date <span class="required">*</span>
                            </label>
                            <input type="date" id="returnDate" name="return_date" class="form-control" required>
                        </div>
                        <div class="form-col" style="display: none;">
                            <label class="form-label">
                                <i class="fas fa-calendar-check"></i> Issue Date
                            </label>
                            <input type="date" name="issue_date" id="issueDateInput" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt"></i> Original Receiving Date
                            </label>
                            <input type="date" name="receiving_date" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt"></i> Location
                            </label>
                            <input type="text" name="location" id="locationInput" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-building"></i> Division
                            </label>
                            <input type="text" name="division" id="divisionInput" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-sitemap"></i> Section
                            </label>
                            <input type="text" name="section" id="sectionInput" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-user"></i> Return By <span class="required">*</span>
                            </label>
                            <input type="text" name="return_by" class="form-control" 
                                   placeholder="Enter name of person returning" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-boxes"></i> Quantity <span class="required">*</span>
                            </label>
                            <input type="number" name="quantity" class="form-control" min="1" 
                                   placeholder="Enter quantity" required>
                        </div>
                    </div>
                </div>

                <!-- Document Information Section -->
                <div class="form-section" style="display: none;">
                    <h4><i class="fas fa-file-invoice"></i> Document Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-folder-open"></i> Tender File No
                            </label>
                            <input type="text" name="tender_file_no" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-file-invoice-dollar"></i> Invoice Number
                            </label>
                            <input type="text" id="invoiceInput" name="invoice" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <!-- Empty for spacing -->
                        </div>
                    </div>
                </div>

                <!-- Return Details Section -->
                <div class="form-section">
                    <h4><i class="fas fa-exclamation-circle"></i> Return Details</h4>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">
                                <i class="fas fa-clipboard-list"></i> Return Reason <span class="required">*</span>
                            </label>
                            <textarea name="reason" class="form-control" rows="3" 
                                      placeholder="Provide detailed reason for returning this paper (e.g., Damaged, Defective, Wrong specification, etc.)" 
                                      required></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">
                                <i class="fas fa-comment-alt"></i> Additional Remarks
                            </label>
                            <textarea name="remarks" class="form-control" rows="2" 
                                      placeholder="Enter any additional notes or comments (optional)"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('returnPaperModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle"></i> Record Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Return Modal -->
<div id="editReturnModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Return Record</h2>
            <span class="modal-close" onclick="closeModal('editReturnModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="editReturnForm" onsubmit="return validateForm('editReturnForm')">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="editReturnId" name="return_id">
                <input type="hidden" id="editPaperType" name="paper_type">
                
                <!-- Paper Details Section -->
                <div class="form-section">
                    <h4><i class="fas fa-file-alt"></i> Paper Details</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-barcode"></i> Code 
                                <span class="label-hint">(Type to auto-fill)</span>
                            </label>
                            <input type="text" id="editCodeDisplay" name="code" class="form-control" 
                                   placeholder="Enter issued paper code..." 
                                   style="font-weight: 500; font-size: 1.05em;">
                            <small class="form-hint">
                                <i class="fas fa-info-circle"></i> Enter the code from the issued paper to auto-fill details
                            </small>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Select Paper <span class="required">*</span></label>
                            <select id="editPaperSelect" name="paper_id" class="form-control" required onchange="updateEditPaperDetails()">
                                <option value="">Choose paper...</option>
                                <?php foreach ($papers as $paper): ?>
                                    <option value="<?php echo $paper['paper_id']; ?>" 
                                            data-type="<?php echo htmlspecialchars($paper['paper_type']); ?>">
                                        <?php echo htmlspecialchars($paper['paper_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-tag"></i> LOT Number
                            </label>
                            <select id="editLotSelect" name="lot" class="form-control">
                                <option value="">Select LOT (Optional)</option>
                                <?php foreach ($lot_stocks as $lot_stock): ?>
                                    <option value="<?php echo htmlspecialchars($lot_stock['stock']); ?>">
                                        <?php echo htmlspecialchars($lot_stock['stock']); ?> - <?php echo htmlspecialchars($lot_stock['paper_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-building"></i> Supplier Name
                            </label>
                            <input type="text" id="editSupplierName" name="supplier_name" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                    </div>
                </div>

                <!-- Return Information Section -->
                <div class="form-section">
                    <h4><i class="fas fa-calendar-check"></i> Return Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-calendar-day"></i> Return Date <span class="required">*</span>
                            </label>
                            <input type="date" id="editReturnDate" name="return_date" class="form-control" required>
                        </div>
                        <div class="form-col" style="display: none;">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt"></i> Original Receiving Date
                            </label>
                            <input type="date" id="editReceivingDate" name="receiving_date" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-user"></i> Return By <span class="required">*</span>
                            </label>
                            <input type="text" id="editReturnBy" name="return_by" class="form-control" 
                                   placeholder="Enter name of person returning" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-boxes"></i> Quantity <span class="required">*</span>
                            </label>
                            <input type="number" id="editQuantity" name="quantity" class="form-control" min="1" 
                                   placeholder="Enter quantity" required>
                        </div>
                    </div>
                </div>

                <!-- Document Information Section -->
                <div class="form-section" style="display: none;">
                    <h4><i class="fas fa-file-invoice"></i> Document Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-folder-open"></i> Tender File No
                            </label>
                            <input type="text" id="editTenderFileNo" name="tender_file_no" class="form-control" 
                                   placeholder="Will auto-fill from code...">
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-file-invoice-dollar"></i> Invoice Number
                            </label>
                            <input type="text" id="editInvoice" name="invoice" class="form-control" 
                                   placeholder="Enter invoice number (optional)">
                        </div>
                        <div class="form-col">
                            <!-- Empty for spacing -->
                        </div>
                    </div>
                </div>

                <!-- Return Details Section -->
                <div class="form-section">
                    <h4><i class="fas fa-exclamation-circle"></i> Return Details</h4>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">
                                <i class="fas fa-clipboard-list"></i> Return Reason <span class="required">*</span>
                            </label>
                            <textarea id="editReason" name="reason" class="form-control" rows="3" 
                                      placeholder="Provide detailed reason for returning this paper (e.g., Damaged, Defective, Wrong specification, etc.)" 
                                      required></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">
                                <i class="fas fa-comment-alt"></i> Additional Remarks
                            </label>
                            <textarea id="editRemarks" name="remarks" class="form-control" rows="2" 
                                      placeholder="Enter any additional notes or comments (optional)"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editReturnModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Return Modal -->
<div id="viewReturnModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-eye"></i> Return Details</h2>
            <span class="modal-close" onclick="closeModal('viewReturnModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="viewReturnContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewReturnModal')">Close</button>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div id="printModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-print"></i> Print Papers Returns Report</h2>
            <span class="modal-close" onclick="closeModal('printModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-section">
                <h4><i class="fas fa-calendar-alt"></i> Date Filtering Options</h4>
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Print Type:</label>
                        <select id="printType" class="form-control" onchange="toggleDateFields()">
                            <option value="all">All Records</option>
                            <option value="daily">Daily Report</option>
                            <option value="monthly">Monthly Report</option>
                            <option value="yearly">Yearly Report</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label">Report Format:</label>
                        <select id="reportFormat" class="form-control">
                            <option value="complete">Complete Report</option>
                            <option value="summary">Summary View</option>
                            <option value="statistics">Statistics Only</option>
                        </select>
                    </div>
                </div>
                
                <!-- Daily Date Selection -->
                <div id="dailySection" class="form-row" style="display: none;">
                    <div class="form-col">
                        <label class="form-label">Select Date:</label>
                        <input type="date" id="dailyDate" class="form-control">
                    </div>
                </div>
                
                <!-- Monthly Selection -->
                <div id="monthlySection" class="form-row" style="display: none;">
                    <div class="form-col">
                        <label class="form-label">Select Month:</label>
                        <select id="monthSelect" class="form-control">
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10" selected>October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label">Select Year:</label>
                        <select id="monthlyYear" class="form-control">
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>
                </div>
                
                <!-- Yearly Selection -->
                <div id="yearlySection" class="form-row" style="display: none;">
                    <div class="form-col">
                        <label class="form-label">Select Year:</label>
                        <select id="yearSelect" class="form-control">
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>
                </div>
                
                <!-- Custom Date Range -->
                <div id="customSection" class="form-row" style="display: none;">
                    <div class="form-col">
                        <label class="form-label">From Date:</label>
                        <input type="date" id="fromDate" class="form-control">
                    </div>
                    <div class="form-col">
                        <label class="form-label">To Date:</label>
                        <input type="date" id="toDate" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4><i class="fas fa-cog"></i> Print Options</h4>
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
                        <input type="checkbox" id="includeRemarks" checked>
                        <span class="checkmark"></span>
                        Include Remarks
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('printModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="generateReturnsPrintReport()">
                <i class="fas fa-print"></i> Generate Report
            </button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
