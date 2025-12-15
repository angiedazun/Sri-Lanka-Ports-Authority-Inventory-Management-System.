<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db.php';

$page_title = "Toner Returns - SLPA System";
$additional_css = ['../assets/css/toner-returns.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/toner-returns.js?v=' . time()];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages first
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Return toner
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'return') {
    $toner_id = (int)($_POST['toner_id'] ?? 0);
    $toner_model = sanitize_input($_POST['toner_model'] ?? '');
    $code = sanitize_input($_POST['code'] ?? '');
    $stock = sanitize_input($_POST['stock'] ?? '');
    $lot = sanitize_input($_POST['lot'] ?? '');
    $supplier_name = sanitize_input($_POST['supplier_name'] ?? '');
    $return_date = sanitize_input($_POST['return_date'] ?? '');
    $receiving_date = sanitize_input($_POST['receiving_date'] ?? '');
    $tender_file_no = sanitize_input($_POST['tender_file_no'] ?? '');
    $invoice = sanitize_input($_POST['invoice'] ?? '');
    $returned_by = sanitize_input($_POST['returned_by'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $reason = sanitize_input($_POST['reason'] ?? '');
    $remarks = sanitize_input($_POST['remarks'] ?? '');
    
    // Validate required fields
    if (empty($toner_id) || empty($return_date) || empty($returned_by) || empty($quantity) || empty($reason)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Insert into database
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Insert return record
            $stmt = $conn->prepare("INSERT INTO toner_return (toner_id, toner_model, code, stock, lot, supplier_name, return_date, receiving_date, tender_file_no, invoice, location, quantity, returned_by, reason, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssssisss", $toner_id, $toner_model, $code, $stock, $lot, $supplier_name, $return_date, $receiving_date, $tender_file_no, $invoice, $stock, $quantity, $returned_by, $reason, $remarks);
            
            if ($stmt->execute()) {
                // Commit transaction
                $conn->commit();
                $_SESSION['message'] = 'Toner return recorded successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                // Rollback transaction
                $conn->rollback();
                $_SESSION['message'] = 'Error recording toner return: ' . $conn->error;
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
    $toner_id = (int)$_POST['toner_id'];
    $toner_model = sanitize_input($_POST['toner_model']);
    $code = sanitize_input($_POST['code']);
    $stock = sanitize_input($_POST['stock']);
    $lot = sanitize_input($_POST['lot'] ?? '');
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $return_date = sanitize_input($_POST['return_date']);
    $receiving_date = sanitize_input($_POST['receiving_date']);
    $tender_file_no = sanitize_input($_POST['tender_file_no']);
    $invoice = sanitize_input($_POST['invoice']);
    $returned_by = sanitize_input($_POST['returned_by']);
    $quantity = (int)$_POST['quantity'];
    $reason = sanitize_input($_POST['reason']);
    $remarks = sanitize_input($_POST['remarks']);
    
    // Validate required fields
    if (empty($return_id) || empty($toner_id) || empty($return_date) || empty($returned_by) || empty($quantity) || empty($reason)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Get the original return record to reverse stock changes
            $originalStmt = $conn->prepare("SELECT toner_id, stock, quantity FROM toner_return WHERE return_id = ?");
            $originalStmt->bind_param("i", $return_id);
            $originalStmt->execute();
            $originalResult = $originalStmt->get_result();
            $originalReturn = $originalResult->fetch_assoc();
            $originalStmt->close();
            
            if ($originalReturn) {
                // Update the return record
                $stmt = $conn->prepare("UPDATE toner_return SET toner_id=?, toner_model=?, code=?, stock=?, lot=?, supplier_name=?, return_date=?, receiving_date=?, tender_file_no=?, invoice=?, location=?, quantity=?, returned_by=?, reason=?, remarks=? WHERE return_id=?");
                $stmt->bind_param("issssssssssisssi", $toner_id, $toner_model, $code, $stock, $lot, $supplier_name, $return_date, $receiving_date, $tender_file_no, $invoice, $stock, $quantity, $returned_by, $reason, $remarks, $return_id);
                
                if ($stmt->execute()) {
                    // Commit transaction
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
            } else {
                // Rollback transaction
                $conn->rollback();
                $_SESSION['message'] = 'Original return record not found!';
                $_SESSION['message_type'] = 'error';
            }
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
        
        // Get the return record to verify it exists
        $selectStmt = $conn->prepare("SELECT return_id FROM toner_return WHERE return_id = ?");
        $selectStmt->bind_param("i", $return_id);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        $returnRecord = $result->fetch_assoc();
        $selectStmt->close();
        
        if ($returnRecord) {
            // Delete the return record
            $stmt = $conn->prepare("DELETE FROM toner_return WHERE return_id = ?");
            $stmt->bind_param("i", $return_id);
            
            if ($stmt->execute()) {
                // Commit transaction
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
        } else {
            // Rollback transaction
            $conn->rollback();
            $_SESSION['message'] = 'Return record not found!';
            $_SESSION['message_type'] = 'error';
        }
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
$toners = [];

// Get toner returns from database with issued toner information and supplier details
try {
    $result = $conn->query("SELECT 
        tr.return_id,
        tr.toner_id,
        tr.toner_model,
        tr.code,
        tr.stock,
        tr.lot,
        tr.return_date,
        tr.receiving_date,
        tr.tender_file_no,
        tr.invoice,
        tr.location,
        tr.quantity,
        tr.returned_by,
        tr.reason,
        tr.remarks,
        ti.issue_id,
        ti.color,
        ti.printer_model,
        ti.printer_no,
        ti.division,
        ti.section,
        ti.request_officer,
        ti.receiver_name,
        ti.receiver_emp_no,
        ti.issue_date,
        ti.remarks as issue_remarks,
        COALESCE(NULLIF(tr.supplier_name, ''), trec.supplier_name, 'N/A') as supplier_name
    FROM toner_return tr
    LEFT JOIN toner_issuing ti ON tr.code = ti.code
    LEFT JOIN toner_receiving trec ON tr.lot = trec.lot AND tr.toner_id = trec.toner_id
    ORDER BY tr.return_date DESC, tr.return_id DESC");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $returns[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get toners from database for dropdown
try {
    $result = $conn->query("SELECT * FROM toner_master ORDER BY toner_model");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $toners[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get issued toners with stock type and supplier information for auto-suggestion
$issued_toners = [];
try {
    $result = $conn->query("SELECT DISTINCT
                                ti.issue_id, 
                                ti.toner_id, 
                                ti.toner_model, 
                                ti.code, 
                                ti.stock, 
                                ti.lot, 
                                ti.division, 
                                ti.section, 
                                ti.receiver_name, 
                                ti.receiver_emp_no, 
                                ti.issue_date,
                                ti.quantity as issued_quantity,
                                COALESCE(tr.supplier_name, '') as supplier_name,
                                tr.receive_date as receiving_date,
                                COALESCE(tr.tender_file_no, '') as tender_file_no,
                                COALESCE(tr.invoice, '') as invoice
                           FROM toner_issuing ti
                           LEFT JOIN toner_receiving tr ON ti.lot = tr.lot AND ti.toner_id = tr.toner_id
                           ORDER BY ti.issue_date DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $issued_toners[] = $row;
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

include '../includes/header.php';
?>

<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>
                    <i class="fas fa-undo-alt"></i>
                    Toner Returns Management
                </h1>
                <p>Track and manage toner returns and defective units</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('returnTonerModal')">
                    <i class="fas fa-plus"></i>
                    Record Return
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='toner_master.php'">
                    <i class="fas fa-box"></i>
                    Toner Master
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
                Toner Return Records
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
                <p>Start by recording your first toner return.</p>
                <button class="btn btn-primary" onclick="openModal('returnTonerModal')">
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
                            <th>Issue Date</th>
                            <th>Toner Model</th>
                            <th>IS Code</th>
                            <th>Stock Type</th>
                            <th>LOT</th>
                            <th>Color</th>
                            <th>Printer Model</th>
                            <th>Printer No</th>
                            <th>Division</th>
                            <th>Section</th>
                            <th>Returned By</th>
                            <th>Supplier Name</th>
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
                                <td><?php echo $return['issue_date'] ? date('M d, Y', strtotime($return['issue_date'])) : '<span style="color: #999;">N/A</span>'; ?></td>
                                <td><?php echo htmlspecialchars($return['toner_model']); ?></td>
                                <td><?php echo htmlspecialchars($return['code'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="stock-badge stock-<?php echo strtolower($return['stock']); ?>">
                                        <?php echo htmlspecialchars($return['stock'] ?: 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($return['lot']) && $return['lot'] != '0'): ?>
                                        <span style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85em;">
                                            <?php echo htmlspecialchars($return['lot']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($return['color'])): ?>
                                        <span class="color-badge" style="background-color: <?php echo strtolower($return['color']); ?>; color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85em; display: inline-block;">
                                            <?php echo htmlspecialchars($return['color']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($return['printer_model'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($return['printer_no'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($return['division'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($return['section'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($return['returned_by'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($return['supplier_name'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="quantity-badge"><?php echo $return['quantity']; ?></span>
                                </td>
                                <td>
                                    <div class="remarks-cell" title="<?php echo htmlspecialchars($return['reason']); ?>">
                                        <?php echo htmlspecialchars($return['reason'] ?: 'N/A'); ?>
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

<!-- Return Toner Modal -->
<div id="returnTonerModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-undo-alt"></i> Record Toner Return</h2>
            <span class="modal-close" onclick="closeModal('returnTonerModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" onsubmit="return validateForm('returnForm')">
                <input type="hidden" name="action" value="return">
                
                <div class="form-section">
                    <h4><i class="fas fa-toner"></i> Toner Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Toner Code <span class="required">*</span></label>
                            <div class="autocomplete-container">
                                <input type="text" id="tonerCodeInput" class="form-control" placeholder="Type or scan toner code..." autocomplete="off" required>
                                <input type="hidden" id="tonerIdInput" name="toner_id">
                                <div id="tonerCodeSuggestions" class="autocomplete-suggestions"></div>
                            </div>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Toner Model</label>
                            <input type="text" id="tonerModelDisplay" name="toner_model" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">IS Code</label>
                            <input type="text" id="codeDisplay" name="code" class="form-control" readonly>
                        </div>
                        <div class="form-col" style="display: none;">
                            <input type="text" id="stockLocationDisplay" class="form-control" readonly style="display: none;">
                            <input type="hidden" id="stockLocationInput" name="stock" required>
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <input type="text" id="lotDisplayField" class="form-control" readonly style="display: none;">
                            <input type="hidden" name="lot" id="lotDisplay" required>
                        </div>
                        <div class="form-col">
                            <input type="text" id="colorDisplay" class="form-control" readonly style="display: none;">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <input type="text" id="tenderFileNo" name="tender_file_no" class="form-control" style="display: none;">
                        </div>
                        <div class="form-col">
                            <input type="text" id="invoiceNumber" name="invoice" class="form-control" style="display: none;">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <input type="text" id="supplierName" name="supplier_name" class="form-control" style="display: none;">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="display: none;">
                    <h4><i class="fas fa-building"></i> Supplier Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <input type="text" id="supplierNameField" name="supplier_name" class="form-control" style="display: none;">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-calendar-alt"></i> Return Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Return Date <span class="required">*</span></label>
                            <input type="date" id="returnDate" name="return_date" class="form-control" required>
                        </div>
                        <div class="form-col">
                            <!-- Empty col for spacing -->
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Returned By <span class="required">*</span></label>
                            <input type="text" name="returned_by" class="form-control" placeholder="Enter name..." required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Quantity <span class="required">*</span></label>
                            <input type="number" id="quantityInput" name="quantity" class="form-control" min="1" placeholder="Will auto-fill from code..." required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-info-circle"></i> Additional Information</h4>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">Return Reason <span class="required">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Enter reason for return..." required></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Enter additional remarks..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('returnTonerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Return</button>
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
            <form method="POST" onsubmit="return validateForm('editReturnForm')">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="editReturnId" name="return_id">
                
                <div class="form-section">
                    <h4><i class="fas fa-toner"></i> Toner Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Select Toner <span class="required">*</span></label>
                            <select id="editTonerSelect" name="toner_id" class="form-control" required onchange="updateEditTonerDetails()">
                                <option value="">Choose toner...</option>
                                <?php foreach ($toners as $toner): ?>
                                    <option value="<?php echo $toner['toner_id']; ?>" 
                                            data-model="<?php echo htmlspecialchars($toner['toner_model']); ?>"
                                            data-code="<?php echo htmlspecialchars($toner['code'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($toner['toner_model']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Toner Model</label>
                            <input type="text" id="editTonerModelDisplay" name="toner_model" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">IS Code</label>
                            <input type="text" id="editCodeDisplay" name="code" class="form-control" readonly>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Stock Type</label>
                            <select id="editStock" name="stock" class="form-control">
                                <option value="">Select type...</option>
                                <option value="JCT">JCT</option>
                                <option value="UCT">UCT</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <input type="text" id="editLotDisplay" name="lot" class="form-control" readonly style="display: none;">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <input type="text" id="editTenderFileNoInfo" name="tender_file_no" class="form-control" style="display: none;">
                        </div>
                        <div class="form-col">
                            <input type="text" id="editInvoiceNumber" name="invoice" class="form-control" style="display: none;">
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: none;">
                        <div class="form-col">
                            <input type="text" id="editSupplierNameInfo" name="supplier_name" class="form-control" style="display: none;">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="display: none;">
                    <h4><i class="fas fa-building"></i> Supplier Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <input type="text" id="editSupplierNameField" name="supplier_name" class="form-control" style="display: none;">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-calendar-alt"></i> Return Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Return Date <span class="required">*</span></label>
                            <input type="date" id="editReturnDate" name="return_date" class="form-control" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Original Receiving Date</label>
                            <input type="date" id="editReceivingDate" name="receiving_date" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Returned By <span class="required">*</span></label>
                            <input type="text" id="editReturnedBy" name="returned_by" class="form-control" placeholder="Enter name..." required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Quantity <span class="required">*</span></label>
                            <input type="number" id="editQuantity" name="quantity" class="form-control" min="1" placeholder="0" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-info-circle"></i> Additional Information</h4>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">Return Reason <span class="required">*</span></label>
                            <textarea id="editReason" name="reason" class="form-control" rows="3" placeholder="Enter reason for return..." required></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">Remarks</label>
                            <textarea id="editRemarks" name="remarks" class="form-control" rows="3" placeholder="Enter additional remarks..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editReturnModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Return</button>
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
            <h2><i class="fas fa-print"></i> Print Toner Returns Report</h2>
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
                        <input type="checkbox" id="includePrinter" checked>
                        <span class="checkmark"></span>
                        Include Printer Details
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

        </div>
    </div>
</div>

<script>
// Store issued toners data for JavaScript access
const issuedTonersData = <?php echo json_encode($issued_toners); ?>;
const returnsData = <?php echo json_encode($returns); ?>;
const tonersData = <?php echo json_encode($toners); ?>;

console.log('=== TONER RETURNS DATA LOADED ===');
console.log('Issued Toners:', issuedTonersData ? issuedTonersData.length : 0);
if (issuedTonersData && issuedTonersData.length > 0) {
    console.log('First issued toner sample:', issuedTonersData[0]);
    console.log('Code:', issuedTonersData[0].code);
    console.log('LOT:', issuedTonersData[0].lot);
    console.log('Issued Quantity:', issuedTonersData[0].issued_quantity);
    console.log('Stock:', issuedTonersData[0].stock);
    console.log('Supplier Name:', issuedTonersData[0].supplier_name);
    console.log('Tender File No:', issuedTonersData[0].tender_file_no);
    console.log('Invoice:', issuedTonersData[0].invoice);
}
console.log('Returns:', returnsData ? returnsData.length : 0);
if (returnsData && returnsData.length > 0) {
    console.log('First return sample:', returnsData[0]);
    console.log('LOT in first return:', returnsData[0].lot);
}
console.log('==================================');
</script>

<?php include '../includes/footer.php'; ?>
