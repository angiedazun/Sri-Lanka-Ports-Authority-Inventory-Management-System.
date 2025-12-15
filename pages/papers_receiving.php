<?php
require_once '../includes/db.php';
require_login();

$page_title = "Papers Receiving - SLPA System";
$additional_css = ['../assets/css/papers-receiving.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/papers-receiving.js?v=' . time()];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages first
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Receive new paper
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'receive') {
    $paper_id = (int)$_POST['paper_id'];
    $paper_type = sanitize_input($_POST['paper_type']);
    $lot = sanitize_input($_POST['lot']);
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $pr_no = sanitize_input($_POST['pr_no']);
    $tender_file_no = sanitize_input($_POST['tender_file_no']);
    $jct_quantity = (int)($_POST['jct_quantity'] ?? 0);
    $uct_quantity = (int)($_POST['uct_quantity'] ?? 0);
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    $invoice = isset($_POST['invoice']) ? sanitize_input($_POST['invoice']) : '';
    $remarks = isset($_POST['remarks']) ? sanitize_input($_POST['remarks']) : '';
    $receive_date = sanitize_input($_POST['receive_date']);
    
    // Validate required fields
    if (empty($paper_id) || empty($paper_type) || empty($receive_date) || ($jct_quantity <= 0 && $uct_quantity <= 0)) {
        $_SESSION['message'] = 'Please fill in all required fields and ensure at least one quantity is greater than 0!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Validate that paper exists in papers_master
        try {
            $validate_stmt = $conn->prepare("SELECT paper_id, paper_type FROM papers_master WHERE paper_id = ?");
            $validate_stmt->bind_param("i", $paper_id);
            $validate_stmt->execute();
            $validate_result = $validate_stmt->get_result();
            $master_paper = $validate_result->fetch_assoc();
            $validate_stmt->close();
            
            if (!$master_paper) {
                $_SESSION['message'] = 'Error: Selected paper (ID: ' . $paper_id . ') does not exist in Papers Master! Please add it to Papers Master first.';
                $_SESSION['message_type'] = 'error';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
            // Verify paper type matches
            if ($master_paper['paper_type'] !== $paper_type) {
                $_SESSION['message'] = 'Warning: Paper type mismatch! Master has "' . $master_paper['paper_type'] . '" but you selected "' . $paper_type . '"';
                $_SESSION['message_type'] = 'warning';
            }
            
        } catch (Exception $e) {
            $_SESSION['message'] = 'Validation error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Insert into database
        try {
            $stmt = $conn->prepare("INSERT INTO papers_receiving (paper_id, paper_type, supplier_name, pr_no, jct_quantity, uct_quantity, lot, tender_file_no, invoice, unit_price, remarks, receive_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssiisssdss", $paper_id, $paper_type, $supplier_name, $pr_no, $jct_quantity, $uct_quantity, $lot, $tender_file_no, $invoice, $unit_price, $remarks, $receive_date);
            
            if ($stmt->execute()) {
                $receive_id_new = $stmt->insert_id;
                
                // Update papers master stock
                $update_stmt = $conn->prepare("UPDATE papers_master SET jct_stock = jct_stock + ?, uct_stock = uct_stock + ? WHERE paper_id = ?");
                $update_stmt->bind_param("iii", $jct_quantity, $uct_quantity, $paper_id);
                
                if ($update_stmt->execute()) {
                    $affected_rows = $update_stmt->affected_rows;
                    if ($affected_rows > 0) {
                        $_SESSION['message'] = 'Paper received successfully! Stock updated (JCT +' . $jct_quantity . ', UCT +' . $uct_quantity . ')';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = 'Paper received but paper not found in master! Please check paper_id: ' . $paper_id;
                        $_SESSION['message_type'] = 'warning';
                    }
                } else {
                    $_SESSION['message'] = 'Paper received but stock update failed: ' . $update_stmt->error;
                    $_SESSION['message_type'] = 'warning';
                }
                $update_stmt->close();
            } else {
                $_SESSION['message'] = 'Error receiving paper: ' . $stmt->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Redirect to refresh the page and show updated stock
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Edit paper receiving
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $receive_id = (int)$_POST['receive_id'];
    $paper_id = (int)$_POST['paper_id'];
    $paper_type = sanitize_input($_POST['paper_type']);
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $pr_no = sanitize_input($_POST['pr_no']);
    $tender_file_no = sanitize_input($_POST['tender_file_no']);
    $jct_quantity = (int)($_POST['jct_quantity'] ?? 0);
    $uct_quantity = (int)($_POST['uct_quantity'] ?? 0);
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    $invoice = isset($_POST['invoice']) ? sanitize_input($_POST['invoice']) : '';
    $remarks = isset($_POST['remarks']) ? sanitize_input($_POST['remarks']) : '';
    $receive_date = sanitize_input($_POST['receive_date']);
    
    // Validate required fields
    if (empty($receive_id) || empty($paper_id) || empty($paper_type) || empty($receive_date) || ($jct_quantity <= 0 && $uct_quantity <= 0)) {
        $_SESSION['message'] = 'Please fill in all required fields and ensure at least one quantity is greater than 0!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Validate that paper exists in papers_master
        try {
            $validate_stmt = $conn->prepare("SELECT paper_id, paper_type FROM papers_master WHERE paper_id = ?");
            $validate_stmt->bind_param("i", $paper_id);
            $validate_stmt->execute();
            $validate_result = $validate_stmt->get_result();
            $master_paper = $validate_result->fetch_assoc();
            $validate_stmt->close();
            
            if (!$master_paper) {
                $_SESSION['message'] = 'Error: Selected paper (ID: ' . $paper_id . ') does not exist in Papers Master! Cannot update.';
                $_SESSION['message_type'] = 'error';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['message'] = 'Validation error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        try {
            // Get original quantities for stock adjustment
            $orig_stmt = $conn->prepare("SELECT jct_quantity, uct_quantity, paper_id FROM papers_receiving WHERE receive_id = ?");
            $orig_stmt->bind_param("i", $receive_id);
            $orig_stmt->execute();
            $orig_result = $orig_stmt->get_result();
            $original = $orig_result->fetch_assoc();
            $orig_stmt->close();
            
            if ($original) {
                // Update the receiving record
                $stmt = $conn->prepare("UPDATE papers_receiving SET paper_id = ?, paper_type = ?, supplier_name = ?, pr_no = ?, jct_quantity = ?, uct_quantity = ?, lot = ?, tender_file_no = ?, invoice = ?, unit_price = ?, remarks = ?, receive_date = ? WHERE receive_id = ?");
                $stmt->bind_param("isssiisssdssi", $paper_id, $paper_type, $supplier_name, $pr_no, $jct_quantity, $uct_quantity, $lot, $tender_file_no, $invoice, $unit_price, $remarks, $receive_date, $receive_id);
                
                if ($stmt->execute()) {
                    // Adjust stock - restore original quantities then add new quantities
                    $restore_stmt = $conn->prepare("UPDATE papers_master SET jct_stock = jct_stock - ?, uct_stock = uct_stock - ? WHERE paper_id = ?");
                    $restore_stmt->bind_param("iii", $original['jct_quantity'], $original['uct_quantity'], $original['paper_id']);
                    $restore_stmt->execute();
                    $restore_stmt->close();
                    
                    // Add new quantities
                    $add_stmt = $conn->prepare("UPDATE papers_master SET jct_stock = jct_stock + ?, uct_stock = uct_stock + ? WHERE paper_id = ?");
                    $add_stmt->bind_param("iii", $jct_quantity, $uct_quantity, $paper_id);
                    $add_stmt->execute();
                    $add_stmt->close();
                    
                    $_SESSION['message'] = 'Paper receiving updated successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error updating paper receiving: ' . $conn->error;
                    $_SESSION['message_type'] = 'error';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = 'Receiving record not found!';
                $_SESSION['message_type'] = 'error';
            }
        } catch (Exception $e) {
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Redirect to refresh the page and show updated stock
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Delete receiving record
if (isset($_GET['delete'])) {
    $receive_id = (int)$_GET['delete'];
    
    try {
        // Get receiving data for stock adjustment
        $get_stmt = $conn->prepare("SELECT jct_quantity, uct_quantity, paper_id FROM papers_receiving WHERE receive_id = ?");
        $get_stmt->bind_param("i", $receive_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $receiving = $result->fetch_assoc();
        $get_stmt->close();
        
        if ($receiving) {
            // Delete the record
            $stmt = $conn->prepare("DELETE FROM papers_receiving WHERE receive_id = ?");
            $stmt->bind_param("i", $receive_id);
            
            if ($stmt->execute()) {
                // Restore stock
                $update_stmt = $conn->prepare("UPDATE papers_master SET jct_stock = jct_stock - ?, uct_stock = uct_stock - ? WHERE paper_id = ?");
                $update_stmt->bind_param("iii", $receiving['jct_quantity'], $receiving['uct_quantity'], $receiving['paper_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                $_SESSION['message'] = 'Receiving record deleted successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error deleting receiving record: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Receiving record not found!';
            $_SESSION['message_type'] = 'error';
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Database error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    // Redirect to prevent URL manipulation
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get data for display
$receivings = [];
$papers = [];

// Get paper receivings from database
try {
    $result = $conn->query("SELECT * FROM papers_receiving ORDER BY receive_date DESC, receive_id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $receivings[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Get papers from database for dropdown - always get all paper types from papers_master
try {
    $result = $conn->query("SELECT paper_id, paper_type FROM papers_master ORDER BY paper_type");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $papers[] = $row;
        }
        // Debug: Check if papers were loaded
        if (empty($papers)) {
            $_SESSION['message'] = 'No papers found. Please add papers to Papers Master first.';
            $_SESSION['message_type'] = 'warning';
        }
    } else {
        $_SESSION['message'] = 'Query failed: ' . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Calculate statistics
$total_receivings = count($receivings);
$recent_receivings = 0;
$total_value = 0;
$total_quantity = 0;

foreach ($receivings as $receiving) {
    if (strtotime($receiving['receive_date']) >= strtotime('-30 days')) {
        $recent_receivings++;
    }
    $total_value += ($receiving['jct_quantity'] + $receiving['uct_quantity']) * $receiving['unit_price'];
    $total_quantity += $receiving['jct_quantity'] + $receiving['uct_quantity'];
}

include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1>
                    <i class="fas fa-truck-loading"></i>
                    Papers Receiving Management
                </h1>
                <p>Track and manage paper deliveries and stock receipts</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('receivePaperModal')">
                    <i class="fas fa-plus"></i>
                    Receive New Paper
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
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-value"><?php echo $total_receivings; ?></div>
            <div class="stat-label">Total Receivings</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-value"><?php echo $recent_receivings; ?></div>
            <div class="stat-label">This Month</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-value"><?php echo $total_quantity; ?></div>
            <div class="stat-label">Total Quantity</div>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-value">Rs. <?php echo number_format($total_value, 2); ?></div>
            <div class="stat-label">Total Value</div>
        </div>
    </div>

    <!-- Receivings Table -->
    <div class="content-card">
        <div class="card-header">
            <h2>
                <i class="fas fa-list"></i>
                Papers Receiving Records
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
                <input type="text" id="searchInput" placeholder="Search receivings..." onkeyup="filterTable()">
            </div>
            <div style="display: flex; gap: 10px;">
                <select id="supplierFilter" class="form-control" onchange="filterTable()">
                    <option value="">All Suppliers</option>
                </select>
                <select id="stockFilter" class="form-control" onchange="filterTable()">
                    <option value="">All Stock Types</option>
                    <option value="JCT">JCT Stock</option>
                    <option value="UCT">UCT Stock</option>
                    <option value="Both">Both Stocks</option>
                </select>
            </div>
        </div>

        <?php if (empty($receivings)): ?>
            <div class="no-data">
                <i class="fas fa-truck-loading"></i>
                <h3>No Receiving Records Found</h3>
                <p>Start by receiving your first paper delivery.</p>
                <button class="btn btn-primary" onclick="openModal('receivePaperModal')">
                    <i class="fas fa-plus"></i>
                    Receive First Paper
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="receivingsTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Receive Date</th>
                            <th>Paper Type</th>
                            <th>LOT</th>
                            <th>Supplier Name</th>
                            <th>PR No</th>
                            <th>Tender File No</th>
                            <th>JCT Quantity</th>
                            <th>UCT Quantity</th>
                            <th>Total Quantity</th>
                            <th>Unit Price</th>
                            <th>Invoice</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receivings as $receiving): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($receiving['receive_date'])); ?></td>
                                <td><?php echo htmlspecialchars($receiving['paper_type']); ?></td>
                                <td>
                                    <?php if (!empty($receiving['lot'])): ?>
                                        <span class="stock-badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85em; display: inline-block;">
                                            <?php echo htmlspecialchars($receiving['lot']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="stock-badge" style="background-color: #6c757d; color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85em; display: inline-block;">
                                            No LOT
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($receiving['supplier_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($receiving['pr_no'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($receiving['tender_file_no'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="quantity-badge"><?php echo $receiving['jct_quantity']; ?></span>
                                </td>
                                <td>
                                    <span class="quantity-badge"><?php echo $receiving['uct_quantity']; ?></span>
                                </td>
                                <td>
                                    <span class="quantity-badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 6px 14px; border-radius: 20px; font-weight: bold; font-size: 1.1em;">
                                        <?php echo ($receiving['jct_quantity'] + $receiving['uct_quantity']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="text-align: left; min-width: 180px;">
                                        <div style="margin-bottom: 8px;">
                                            <strong style="color: #2196F3; font-size: 1em;">Rs. <?php echo number_format($receiving['unit_price'], 2); ?></strong>
                                        </div>
                                        <div style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 10px; border-radius: 8px; border-left: 4px solid #4caf50;">
                                            <div style="margin-bottom: 5px;">
                                                <i class="fas fa-bullseye" style="color: #d32f2f;"></i>
                                                <strong style="color: #d32f2f; font-size: 1em;">FULL PAYMENT:</strong>
                                            </div>
                                            <div style="font-size: 1.2em; font-weight: bold; color: #1b5e20; margin-bottom: 5px;">
                                                Rs. <?php echo number_format(($receiving['jct_quantity'] + $receiving['uct_quantity']) * $receiving['unit_price'], 2); ?>
                                            </div>
                                            <small style="color: #666; font-size: 0.85em;">
                                                Calculation: <?php echo ($receiving['jct_quantity'] + $receiving['uct_quantity']); ?> × Rs. <?php echo number_format($receiving['unit_price'], 2); ?> = Rs. <?php echo number_format(($receiving['jct_quantity'] + $receiving['uct_quantity']) * $receiving['unit_price'], 2); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($receiving['invoice'] ?: '-'); ?></td>
                                <td>
                                    <div class="remarks-cell">
                                        <?php 
                                        $remarks = $receiving['remarks'];
                                        if (strlen($remarks) > 50) {
                                            echo htmlspecialchars(substr($remarks, 0, 50)) . '...';
                                        } else {
                                            echo htmlspecialchars($remarks ?: 'N/A');
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="table-actions-cell">
                                    <button class="btn btn-sm btn-view" title="View Details" onclick="viewReceiving(<?php echo $receiving['receive_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-edit" title="Edit" onclick="editReceiving(<?php echo $receiving['receive_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-delete" title="Delete" 
                                            onclick="confirmDelete(<?php echo $receiving['receive_id']; ?>)">
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

<!-- Receive Paper Modal -->
<div id="receivePaperModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-truck-loading"></i> Receive New Paper</h2>
            <span class="modal-close" onclick="closeModal('receivePaperModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="" onsubmit="return validateForm('receivePaperForm')" id="receivePaperForm">
                <input type="hidden" name="action" value="receive">
                
                <!-- Paper Information -->
                <div class="form-section">
                    <h4><i class="fas fa-file"></i> Paper Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Paper Type <span class="required">*</span></label>
                            <select name="paper_id" id="paperSelect" class="form-control" required onchange="updatePaperDetails()">
                                <option value="">Select paper type</option>
                                <?php foreach ($papers as $paper): ?>
                                    <option value="<?php echo $paper['paper_id']; ?>" 
                                            data-type="<?php echo htmlspecialchars($paper['paper_type']); ?>">
                                        <?php echo htmlspecialchars($paper['paper_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="paper_type" id="paperTypeHidden">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">LOT Number</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold;" id="lotPrefix"><?php echo date('Y'); ?>/LOT</span>
                                <input type="number" name="lot_number" id="lotNumber" class="form-control" placeholder="Enter number (e.g., 1, 2, 3)" min="1" max="999" oninput="updateLotPreview()">
                                <input type="hidden" name="lot" id="lotHidden">
                            </div>
                            <small class="text-muted">Enter only the number - "<?php echo date('Y'); ?>/LOT" will be added automatically</small>
                        </div>
                    </div>
                </div>
                
                <!-- Supplier Information -->
                <div class="form-section">
                    <h4><i class="fas fa-building"></i> Supplier Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Supplier Name</label>
                            <input type="text" name="supplier_name" class="form-control" placeholder="Enter supplier name">
                        </div>
                        <div class="form-col">
                            <label class="form-label">PR Number</label>
                            <input type="text" name="pr_no" class="form-control" placeholder="Purchase Request Number">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Tender File Number</label>
                            <input type="text" name="tender_file_no" class="form-control" placeholder="Tender file reference">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Invoice</label>
                            <input type="text" name="invoice" class="form-control" placeholder="Invoice number">
                        </div>
                    </div>
                </div>
                
                <!-- Quantity Information -->
                <div class="form-section">
                    <h4><i class="fas fa-boxes"></i> Quantity Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">JCT Quantity</label>
                            <input type="number" name="jct_quantity" id="jctQuantity" class="form-control" min="0" value="0" placeholder="Quantity for JCT" oninput="calculatePayment()">
                        </div>
                        <div class="form-col">
                            <label class="form-label">UCT Quantity</label>
                            <input type="number" name="uct_quantity" id="uctQuantity" class="form-control" min="0" value="0" placeholder="Quantity for UCT" oninput="calculatePayment()">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Unit Price</label>
                            <input type="number" name="unit_price" id="unitPrice" class="form-control" step="0.01" min="0" placeholder="Price per unit" oninput="calculatePayment()">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Receive Date <span class="required">*</span></label>
                            <input type="date" name="receive_date" id="receiveDate" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row" id="paymentCalculation" style="display: none;">
                        <div class="form-col-full">
                            <div style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 20px; border-radius: 10px; border-left: 5px solid #4caf50;">
                                <div style="margin-bottom: 10px;">
                                    <i class="fas fa-check-circle" style="color: #4caf50;"></i>
                                    <strong style="color: #2e7d32; font-size: 1em;">Unit price: Rs. <span id="displayUnitPrice">0.00</span> - Valid</strong>
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <i class="fas fa-bullseye" style="color: #d32f2f;"></i>
                                    <strong style="color: #d32f2f; font-size: 1.3em;">FULL PAYMENT: Rs. <span id="displayFullPayment">0.00</span></strong>
                                </div>
                                <div style="color: #666; font-size: 0.9em;">
                                    <strong>Calculation:</strong> <span id="displayCalculation">0 × Rs. 0.00 = Rs. 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div class="form-section">
                    <h4><i class="fas fa-info-circle"></i> Additional Information</h4>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Additional notes or comments"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('receivePaperModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" data-original-text="Receive Paper">
                        <i class="fas fa-truck-loading"></i> Receive Paper
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Receiving Modal -->
<div id="editReceivingModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Paper Receiving</h2>
            <span class="modal-close" onclick="closeModal('editReceivingModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="" onsubmit="return validateForm('editReceivingForm')" id="editReceivingForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="receive_id" id="editReceiveId">
                
                <!-- Paper Information -->
                <div class="form-section">
                    <h4><i class="fas fa-file"></i> Paper Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Paper Type <span class="required">*</span></label>
                            <select name="paper_id" id="editPaperSelect" class="form-control" required onchange="updateEditPaperDetails()">
                                <option value="">Select paper type</option>
                                <?php foreach ($papers as $paper): ?>
                                    <option value="<?php echo $paper['paper_id']; ?>" 
                                            data-type="<?php echo htmlspecialchars($paper['paper_type']); ?>">
                                        <?php echo htmlspecialchars($paper['paper_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="paper_type" id="editPaperTypeHidden">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">LOT Number</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold;" id="editLotPrefix"><?php echo date('Y'); ?>/LOT</span>
                                <input type="number" name="lot_number" id="editLotNumber" class="form-control" placeholder="Enter number (e.g., 1, 2, 3)" min="1" max="999" oninput="updateEditLotPreview()">
                                <input type="hidden" name="lot" id="editLotHidden">
                            </div>
                            <small class="text-muted">Enter only the number - "2025/LOT" will be added automatically</small>
                        </div>
                    </div>
                </div>
                
                <!-- Supplier Information -->
                <div class="form-section">
                    <h4><i class="fas fa-building"></i> Supplier Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Supplier Name</label>
                            <input type="text" name="supplier_name" id="editSupplierName" class="form-control" placeholder="Enter supplier name">
                        </div>
                        <div class="form-col">
                            <label class="form-label">PR Number</label>
                            <input type="text" name="pr_no" id="editPrNo" class="form-control" placeholder="Purchase Request Number">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Tender File Number</label>
                            <input type="text" name="tender_file_no" id="editTenderFileNo" class="form-control" placeholder="Tender file reference">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Invoice</label>
                            <input type="text" name="invoice" id="editInvoice" class="form-control" placeholder="Invoice number">
                        </div>
                    </div>
                </div>
                
                <!-- Quantity Information -->
                <div class="form-section">
                    <h4><i class="fas fa-boxes"></i> Quantity Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">JCT Quantity</label>
                            <input type="number" name="jct_quantity" id="editJctQuantity" class="form-control" min="0" value="0" placeholder="Quantity for JCT" oninput="calculateEditPayment()">
                        </div>
                        <div class="form-col">
                            <label class="form-label">UCT Quantity</label>
                            <input type="number" name="uct_quantity" id="editUctQuantity" class="form-control" min="0" value="0" placeholder="Quantity for UCT" oninput="calculateEditPayment()">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Unit Price</label>
                            <input type="number" name="unit_price" id="editUnitPrice" class="form-control" step="0.01" min="0" placeholder="Price per unit" oninput="calculateEditPayment()">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Receive Date <span class="required">*</span></label>
                            <input type="date" name="receive_date" id="editReceiveDate" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row" id="editPaymentCalculation" style="display: none;">
                        <div class="form-col-full">
                            <div style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 20px; border-radius: 10px; border-left: 5px solid #4caf50;">
                                <div style="margin-bottom: 10px;">
                                    <i class="fas fa-check-circle" style="color: #4caf50;"></i>
                                    <strong style="color: #2e7d32; font-size: 1em;">Unit price: Rs. <span id="editDisplayUnitPrice">0.00</span> - Valid</strong>
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <i class="fas fa-bullseye" style="color: #d32f2f;"></i>
                                    <strong style="color: #d32f2f; font-size: 1.3em;">FULL PAYMENT: Rs. <span id="editDisplayFullPayment">0.00</span></strong>
                                </div>
                                <div style="color: #666; font-size: 0.9em;">
                                    <strong>Calculation:</strong> <span id="editDisplayCalculation">0 × Rs. 0.00 = Rs. 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div class="form-section">
                    <h4><i class="fas fa-info-circle"></i> Additional Information</h4>
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" id="editRemarks" class="form-control" rows="3" placeholder="Additional notes or comments"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editReceivingModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" data-original-text="Update Paper">
                        <i class="fas fa-save"></i> Update Paper
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div id="printModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-print"></i> Print Receiving Report</h2>
            <span class="modal-close" onclick="closeModal('printModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-section">
                <h4><i class="fas fa-calendar-alt"></i> Report Settings</h4>
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
                        <input type="checkbox" id="includeSupplier" checked>
                        <span class="checkmark"></span>
                        Include Supplier Information
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="includePricing" checked>
                        <span class="checkmark"></span>
                        Include Pricing Details
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
            <button type="button" class="btn btn-primary" onclick="generatePrintReport()">
                <i class="fas fa-print"></i> Generate Print
            </button>
        </div>
    </div>
</div>

<!-- View Receiving Modal -->
<div id="viewReceivingModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-eye"></i> Receiving Details</h2>
            <span class="modal-close" onclick="closeModal('viewReceivingModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="viewReceivingContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewReceivingModal')">Close</button>
        </div>
    </div>
</div>

<script>
// Store receivings data for JavaScript access
const receivingsData = <?php echo json_encode($receivings); ?>;
const papersData = <?php echo json_encode($papers); ?>;
</script>

<?php include '../includes/footer.php'; ?>
