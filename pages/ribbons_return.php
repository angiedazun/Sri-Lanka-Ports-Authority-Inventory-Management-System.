<?php
require_once '../includes/db.php';
require_login();

$page_title = "Ribbons Returns - SLPA System";
$additional_css = ['../assets/css/ribbons-return.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/ribbons-return.js?v=' . time()];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages first
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Return ribbon
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'return') {
    $ribbon_id = (int)$_POST['ribbon_id'];
    $ribbon_model = sanitize_input($_POST['ribbon_model']);
    $code = sanitize_input($_POST['code'] ?? '');
    $lot = sanitize_input($_POST['lot'] ?? '');
    
    // TEMPORARY FIX: If LOT is empty, try to get it from the issuing record with this code
    if (empty($lot) && !empty($code)) {
        $lot_result = $conn->query("SELECT lot FROM ribbons_issuing WHERE code = '" . $conn->real_escape_string($code) . "' AND ribbon_id = $ribbon_id AND lot IS NOT NULL AND lot != '' LIMIT 1");
        if ($lot_result && $lot_row = $lot_result->fetch_assoc()) {
            $lot = $lot_row['lot'];
        }
    }
    
    // If still empty, try to get any LOT for this ribbon from ribbons_receiving
    if (empty($lot)) {
        $lot_result = $conn->query("SELECT lot FROM ribbons_receiving WHERE ribbon_id = $ribbon_id AND lot IS NOT NULL AND lot != '' LIMIT 1");
        if ($lot_result && $lot_row = $lot_result->fetch_assoc()) {
            $lot = $lot_row['lot'];
        }
    }
    
    $supplier_name = sanitize_input($_POST['supplier_name'] ?? '');
    $return_date = sanitize_input($_POST['return_date']);
    $issue_date = sanitize_input($_POST['issue_date'] ?? '');
    $issue_date = !empty($issue_date) ? $issue_date : NULL;
    $receiving_date = sanitize_input($_POST['receiving_date'] ?? '');
    $receiving_date = !empty($receiving_date) ? $receiving_date : NULL;
    $tender_file_no = sanitize_input($_POST['tender_file_no'] ?? '');
    $invoice = sanitize_input($_POST['invoice'] ?? '');
    $return_by = sanitize_input($_POST['return_by']);
    $division = sanitize_input($_POST['division'] ?? '');
    $section = sanitize_input($_POST['section'] ?? '');
    $quantity = (int)$_POST['quantity'];
    $reason = sanitize_input($_POST['reason']);
    $remarks = sanitize_input($_POST['remarks'] ?? '');
    
    // Validate required fields
    if (empty($ribbon_id) || empty($return_date) || empty($return_by) || empty($quantity) || empty($reason)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Check if this ribbon code has already been returned
        if (!empty($code)) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) as return_count FROM ribbons_return WHERE code = ? AND ribbon_id = ?");
            $check_stmt->bind_param("si", $code, $ribbon_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_data = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ($check_data['return_count'] > 0) {
                $_SESSION['message'] = 'This ribbon (Code: ' . htmlspecialchars($code) . ') has already been returned and cannot be returned again!';
                $_SESSION['message_type'] = 'error';
                header("Location: ribbons_return.php");
                exit();
            }
        }
        
        // Insert into database
        try {
            $conn->begin_transaction();
            
            $stmt = $conn->prepare("INSERT INTO ribbons_return (ribbon_id, ribbon_model, code, lot, supplier_name, return_date, issue_date, receiving_date, tender_file_no, invoice, return_by, division, section, quantity, reason, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssssssssss", $ribbon_id, $ribbon_model, $code, $lot, $supplier_name, $return_date, $issue_date, $receiving_date, $tender_file_no, $invoice, $return_by, $division, $section, $quantity, $reason, $remarks);
            
            if ($stmt->execute()) {
                $conn->commit();
                $_SESSION['message'] = 'Ribbon return recorded successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $conn->rollback();
                $_SESSION['message'] = 'Error recording ribbon return: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Update return record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $return_id = (int)$_POST['return_id'];
    $ribbon_id = (int)$_POST['ribbon_id'];
    $ribbon_model = sanitize_input($_POST['ribbon_model']);
    $code = sanitize_input($_POST['code'] ?? '');
    $lot = sanitize_input($_POST['lot'] ?? '');
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $return_date = sanitize_input($_POST['return_date']);
    $receiving_date = sanitize_input($_POST['receiving_date']);
    
    if (empty($receiving_date) && !empty($lot)) {
        $date_result = $conn->query("SELECT receive_date FROM ribbons_receiving WHERE lot = '" . $conn->real_escape_string($lot) . "' AND ribbon_id = $ribbon_id LIMIT 1");
        if ($date_result && $date_row = $date_result->fetch_assoc()) {
            $receiving_date = $date_row['receive_date'];
        }
    }
    
    $receiving_date = !empty($receiving_date) ? $receiving_date : NULL;
    $tender_file_no = sanitize_input($_POST['tender_file_no']);
    $invoice = sanitize_input($_POST['invoice']);
    $return_by = sanitize_input($_POST['return_by']);
    $quantity = (int)$_POST['quantity'];
    $reason = sanitize_input($_POST['reason']);
    $remarks = sanitize_input($_POST['remarks']);
    
    if (empty($return_id) || empty($ribbon_id) || empty($return_date) || empty($return_by) || empty($quantity) || empty($reason)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        if (!empty($code)) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) as return_count FROM ribbons_return WHERE code = ? AND ribbon_id = ? AND return_id != ?");
            $check_stmt->bind_param("sii", $code, $ribbon_id, $return_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_data = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ($check_data['return_count'] > 0) {
                $_SESSION['message'] = 'This ribbon (Code: ' . htmlspecialchars($code) . ') has already been returned and cannot be returned again!';
                $_SESSION['message_type'] = 'error';
                header("Location: ribbons_return.php");
                exit();
            }
        }
        
        try {
            $conn->begin_transaction();
            
            $stmt = $conn->prepare("UPDATE ribbons_return SET ribbon_id=?, ribbon_model=?, code=?, lot=?, supplier_name=?, return_date=?, receiving_date=?, tender_file_no=?, invoice=?, return_by=?, quantity=?, reason=?, remarks=? WHERE return_id=?");
            $stmt->bind_param("isssssssssissi", $ribbon_id, $ribbon_model, $code, $lot, $supplier_name, $return_date, $receiving_date, $tender_file_no, $invoice, $return_by, $quantity, $reason, $remarks, $return_id);
            
            if ($stmt->execute()) {
                $conn->commit();
                $_SESSION['message'] = 'Return record updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $conn->rollback();
                $_SESSION['message'] = 'Error updating return record: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Delete return record
if (isset($_GET['delete'])) {
    $return_id = (int)$_GET['delete'];
    
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("DELETE FROM ribbons_return WHERE return_id = ?");
        $stmt->bind_param("i", $return_id);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['message'] = 'Return record deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $conn->rollback();
            $_SESSION['message'] = 'Error deleting return record: ' . $conn->error;
            $_SESSION['message_type'] = 'error';
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = 'Database error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get data for display
$returns = [];
$ribbons = [];
$lot_stocks = [];
$issuing_records = [];

// Get ribbon returns from database
try {
    $result = $conn->query("SELECT * FROM ribbons_return ORDER BY return_date DESC, return_id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $returns[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get ribbons from database for dropdown
try {
    $result = $conn->query("SELECT * FROM ribbons_master ORDER BY ribbon_model");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ribbons[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get LOT stocks with ribbon information from ribbons_receiving for dropdown
try {
    $result = $conn->query("SELECT rr.lot as stock, rr.ribbon_id, rm.ribbon_model 
                           FROM ribbons_receiving rr 
                           LEFT JOIN ribbons_master rm ON rr.ribbon_id = rm.ribbon_id 
                           WHERE rr.lot IS NOT NULL AND rr.lot != '' AND rr.lot != '0' 
                           ORDER BY rr.lot");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lot_stocks[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get issuing records with all details for auto-fill
try {
    $result = $conn->query("SELECT 
        ri.code, 
        ri.ribbon_id, 
        ri.ribbon_model, 
        ri.lot, 
        ri.stock,
        ri.division,
        ri.section,
        ri.issue_date,
        ri.quantity,
        rr.supplier_name,
        rr.receive_date as receiving_date,
        rr.tender_file_no,
        rr.invoice
    FROM ribbons_issuing ri
    LEFT JOIN ribbons_receiving rr ON ri.lot = rr.lot AND ri.ribbon_id = rr.ribbon_id
    WHERE ri.code IS NOT NULL AND ri.code != '' 
    ORDER BY ri.issue_date DESC");
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
    if (date('Y-m', strtotime($return['return_date'])) == date('Y-m')) {
        $recent_returns++;
    }
    
    $total_quantity += $return['quantity'];
    
    if (!empty($return['supplier_name'])) {
        $unique_suppliers[$return['supplier_name']] = true;
    }
}

$supplier_count = count($unique_suppliers);

// Pass data to JavaScript
echo "<script>var returnsData = " . json_encode($returns) . ";</script>";
echo "<script>var ribbonsData = " . json_encode($ribbons) . ";</script>";
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
                    Ribbons Returns Management
                </h1>
                <p>Track and manage ribbon returns and defective units</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('returnRibbonModal')">
                    <i class="fas fa-plus"></i>
                    Record Return
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='ribbons_master.php'">
                    <i class="fas fa-box"></i>
                    Ribbons Master
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
                Ribbon Return Records
            </h2>
            <div class="header-actions">
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
                <p>Start by recording your first ribbon return.</p>
                <button class="btn btn-primary" onclick="openModal('returnRibbonModal')">
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
                            <th>Ribbon Model</th>
                            <th>Code</th>
                            <th>LOT</th>
                            <th>Supplier</th>
                            <th>Issue Date</th>
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
                                <td><?php echo htmlspecialchars($return['ribbon_model']); ?></td>
                                <td><?php echo htmlspecialchars($return['code'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php if (!empty($return['lot']) && $return['lot'] != '0'): ?>
                                        <span class="badge badge-lot">
                                            <?php echo htmlspecialchars($return['lot']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($return['supplier_name'] ?: 'N/A'); ?></td>
                                <td><?php echo $return['issue_date'] ? date('M d, Y', strtotime($return['issue_date'])) : 'N/A'; ?></td>
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
                                    <button class="btn btn-info btn-sm" onclick="viewReturn(<?php echo $return['return_id']; ?>)" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="editReturn(<?php echo $return['return_id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $return['return_id']; ?>)" title="Delete">
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

<!-- Return Ribbon Modal -->
<div id="returnRibbonModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-undo-alt"></i> Record Ribbon Return</h2>
            <span class="modal-close" onclick="closeModal('returnRibbonModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="returnForm">
                <input type="hidden" name="action" value="return">
                
                <!-- Ribbon Details Section -->
                <div class="form-section">
                    <h4><i class="fas fa-print"></i> Ribbon Details</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-barcode"></i> IS Code <span class="required">*</span>
                            </label>
                            <div class="autocomplete-container">
                                <input type="text" id="codeInput" class="form-control" 
                                       placeholder="Type or scan IS code..." 
                                       autocomplete="off" required>
                                <input type="hidden" id="codeHidden" name="code">
                                <div id="codeSuggestions" class="autocomplete-suggestions"></div>
                            </div>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Ribbon Model</label>
                            <input type="text" id="ribbonModelDisplay" class="form-control" 
                                   placeholder="Will auto-fill from code..." readonly>
                            <input type="hidden" id="ribbonIdInput" name="ribbon_id">
                            <input type="hidden" id="ribbonModelHidden" name="ribbon_model">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-tag"></i> LOT Number
                            </label>
                            <div class="autocomplete-container">
                                <input type="text" id="lotInput" class="form-control" 
                                       placeholder="Type to search LOT..." autocomplete="off">
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
                            <input type="date" name="issue_date" id="issueDateInput" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt"></i> Original Receiving Date
                            </label>
                            <input type="date" name="receiving_date" class="form-control">
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-building"></i> Division
                            </label>
                            <input type="text" name="division" id="divisionInput" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-sitemap"></i> Section
                            </label>
                            <input type="text" name="section" id="sectionInput" class="form-control">
                        </div>
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-folder-open"></i> Tender File No
                            </label>
                            <input type="text" name="tender_file_no" class="form-control">
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
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <label class="form-label">
                                <i class="fas fa-file-invoice-dollar"></i> Invoice Number
                            </label>
                            <input type="text" id="invoiceInput" name="invoice" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Reason Section -->
                <div class="form-section">
                    <h4><i class="fas fa-exclamation-circle"></i> Return Reason</h4>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">
                                <i class="fas fa-comment-alt"></i> Reason <span class="required">*</span>
                            </label>
                            <textarea name="reason" class="form-control" rows="3" 
                                      placeholder="Enter reason for return..." required></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">
                                <i class="fas fa-sticky-note"></i> Additional Remarks
                            </label>
                            <textarea name="remarks" class="form-control" rows="2" 
                                      placeholder="Enter any additional remarks..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('returnRibbonModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Record Return
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
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="return_id" id="editReturnId">
                
                <div class="form-section">
                    <h4><i class="fas fa-print"></i> Ribbon Details</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Ribbon ID</label>
                            <input type="number" name="ribbon_id" id="editRibbonId" class="form-control" readonly>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Ribbon Model</label>
                            <input type="text" name="ribbon_model" id="editRibbonModel" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" id="editCode" class="form-control" readonly>
                        </div>
                        <div class="form-col">
                            <label class="form-label">LOT</label>
                            <input type="text" name="lot" id="editLot" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Supplier</label>
                            <input type="text" name="supplier_name" id="editSupplier" class="form-control">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Tender File No</label>
                            <input type="text" name="tender_file_no" id="editTenderFile" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-calendar-check"></i> Return Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Return Date <span class="required">*</span></label>
                            <input type="date" name="return_date" id="editReturnDate" class="form-control" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Receiving Date</label>
                            <input type="date" name="receiving_date" id="editReceivingDate" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Invoice</label>
                            <input type="text" name="invoice" id="editInvoice" class="form-control">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Return By <span class="required">*</span></label>
                            <input type="text" name="return_by" id="editReturnBy" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Quantity <span class="required">*</span></label>
                            <input type="number" name="quantity" id="editQuantity" class="form-control" min="1" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-exclamation-circle"></i> Return Reason</h4>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">Reason <span class="required">*</span></label>
                            <textarea name="reason" id="editReason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" id="editRemarks" class="form-control" rows="2"></textarea>
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

<?php include '../includes/footer.php'; ?>
