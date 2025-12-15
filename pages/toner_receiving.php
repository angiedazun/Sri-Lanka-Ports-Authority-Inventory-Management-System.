<?php
require_once '../includes/db.php';
require_login();

$page_title = "Toner Receiving - SLPA System";
$additional_css = ['../assets/css/toner-receiving.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/toner-receiving.js?v=' . time()];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages first
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Receive new toner
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'receive') {
    $toner_id = (int)$_POST['toner_id'];
    $toner_model = sanitize_input($_POST['toner_model']);
    $lot = sanitize_input($_POST['lot']);
    $stock = 'JCT/UCT';
    $color = sanitize_input($_POST['color']);
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $pr_no = sanitize_input($_POST['pr_no']);
    $tender_file_no = sanitize_input($_POST['tender_file_no']);
    $jct_quantity = (int)$_POST['jct_quantity'];
    $uct_quantity = (int)$_POST['uct_quantity'];
    $unit_price = (float)$_POST['unit_price'];
    $invoice = sanitize_input($_POST['invoice']);
    $remarks = sanitize_input($_POST['remarks']);
    $receive_date = sanitize_input($_POST['receive_date']);
    
    // Validate required fields
    if (empty($toner_id) || empty($toner_model) || empty($receive_date) || ($jct_quantity <= 0 && $uct_quantity <= 0)) {
        $_SESSION['message'] = 'Please fill in all required fields and ensure at least one quantity is greater than 0!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Validate that toner exists in toner_master
        try {
            $validate_stmt = $conn->prepare("SELECT toner_id, toner_model, color FROM toner_master WHERE toner_id = ?");
            $validate_stmt->bind_param("i", $toner_id);
            $validate_stmt->execute();
            $validate_result = $validate_stmt->get_result();
            $master_toner = $validate_result->fetch_assoc();
            $validate_stmt->close();
            
            if (!$master_toner) {
                $_SESSION['message'] = 'Error: Selected toner (ID: ' . $toner_id . ') does not exist in Toner Master! Please add it to Toner Master first.';
                $_SESSION['message_type'] = 'error';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
            // Verify toner model matches
            if ($master_toner['toner_model'] !== $toner_model) {
                $_SESSION['message'] = 'Warning: Toner model mismatch! Master has "' . $master_toner['toner_model'] . '" but you selected "' . $toner_model . '"';
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
            $stmt = $conn->prepare("INSERT INTO toner_receiving (toner_id, toner_model, stock, lot, color, supplier_name, pr_no, tender_file_no, jct_quantity, uct_quantity, unit_price, invoice, remarks, receive_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssidssss", $toner_id, $toner_model, $stock, $lot, $color, $supplier_name, $pr_no, $tender_file_no, $jct_quantity, $uct_quantity, $unit_price, $invoice, $remarks, $receive_date);
            
            if ($stmt->execute()) {
                $receive_id_new = $stmt->insert_id;
                
                // Update toner master stock
                $update_stmt = $conn->prepare("UPDATE toner_master SET jct_stock = jct_stock + ?, uct_stock = uct_stock + ? WHERE toner_id = ?");
                $update_stmt->bind_param("iii", $jct_quantity, $uct_quantity, $toner_id);
                
                if ($update_stmt->execute()) {
                    $affected_rows = $update_stmt->affected_rows;
                    if ($affected_rows > 0) {
                        $_SESSION['message'] = 'Toner received successfully! Stock updated (JCT +' . $jct_quantity . ', UCT +' . $uct_quantity . ')';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = 'Toner received but toner not found in master! Please check toner_id: ' . $toner_id;
                        $_SESSION['message_type'] = 'warning';
                    }
                } else {
                    $_SESSION['message'] = 'Toner received but stock update failed: ' . $update_stmt->error;
                    $_SESSION['message_type'] = 'warning';
                }
                $update_stmt->close();
            } else {
                $_SESSION['message'] = 'Error receiving toner: ' . $stmt->error;
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
    
    // Don't redirect for Add - show data immediately
    // header('Location: ' . $_SERVER['PHP_SELF']);
    // exit;
}

// Edit toner receiving
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $receive_id = (int)$_POST['receive_id'];
    $toner_id = (int)$_POST['toner_id'];
    $toner_model = sanitize_input($_POST['toner_model']);
    $lot = sanitize_input($_POST['lot']);
    $stock = 'JCT/UCT';
    $color = sanitize_input($_POST['color']);
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $pr_no = sanitize_input($_POST['pr_no']);
    $tender_file_no = sanitize_input($_POST['tender_file_no']);
    $jct_quantity = (int)$_POST['jct_quantity'];
    $uct_quantity = (int)$_POST['uct_quantity'];
    $unit_price = (float)$_POST['unit_price'];
    $invoice = sanitize_input($_POST['invoice']);
    $remarks = sanitize_input($_POST['remarks']);
    $receive_date = sanitize_input($_POST['receive_date']);
    
    // Validate required fields
    if (empty($receive_id) || empty($toner_id) || empty($toner_model) || empty($receive_date) || ($jct_quantity <= 0 && $uct_quantity <= 0)) {
        $_SESSION['message'] = 'Please fill in all required fields and ensure at least one quantity is greater than 0!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Validate that toner exists in toner_master
        try {
            $validate_stmt = $conn->prepare("SELECT toner_id, toner_model FROM toner_master WHERE toner_id = ?");
            $validate_stmt->bind_param("i", $toner_id);
            $validate_stmt->execute();
            $validate_result = $validate_stmt->get_result();
            $master_toner = $validate_result->fetch_assoc();
            $validate_stmt->close();
            
            if (!$master_toner) {
                $_SESSION['message'] = 'Error: Selected toner (ID: ' . $toner_id . ') does not exist in Toner Master! Cannot update.';
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
            $orig_stmt = $conn->prepare("SELECT jct_quantity, uct_quantity, toner_id FROM toner_receiving WHERE receive_id = ?");
            $orig_stmt->bind_param("i", $receive_id);
            $orig_stmt->execute();
            $orig_result = $orig_stmt->get_result();
            $original = $orig_result->fetch_assoc();
            $orig_stmt->close();
            
            if ($original) {
                // Update the receiving record
                $stmt = $conn->prepare("UPDATE toner_receiving SET toner_id = ?, toner_model = ?, stock = ?, lot = ?, color = ?, supplier_name = ?, pr_no = ?, tender_file_no = ?, jct_quantity = ?, uct_quantity = ?, unit_price = ?, invoice = ?, remarks = ?, receive_date = ? WHERE receive_id = ?");
                $stmt->bind_param("issssssssidsssi", $toner_id, $toner_model, $stock, $lot, $color, $supplier_name, $pr_no, $tender_file_no, $jct_quantity, $uct_quantity, $unit_price, $invoice, $remarks, $receive_date, $receive_id);
                
                if ($stmt->execute()) {
                    // Adjust stock - restore original quantities then add new quantities
                    $restore_stmt = $conn->prepare("UPDATE toner_master SET jct_stock = jct_stock - ?, uct_stock = uct_stock - ? WHERE toner_id = ?");
                    $restore_stmt->bind_param("iii", $original['jct_quantity'], $original['uct_quantity'], $original['toner_id']);
                    $restore_stmt->execute();
                    $restore_stmt->close();
                    
                    // Add new quantities
                    $add_stmt = $conn->prepare("UPDATE toner_master SET jct_stock = jct_stock + ?, uct_stock = uct_stock + ? WHERE toner_id = ?");
                    $add_stmt->bind_param("iii", $jct_quantity, $uct_quantity, $toner_id);
                    $add_stmt->execute();
                    $add_stmt->close();
                    
                    $_SESSION['message'] = 'Toner receiving updated successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error updating toner receiving: ' . $conn->error;
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
        $get_stmt = $conn->prepare("SELECT jct_quantity, uct_quantity, toner_id FROM toner_receiving WHERE receive_id = ?");
        $get_stmt->bind_param("i", $receive_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $receiving = $result->fetch_assoc();
        $get_stmt->close();
        
        if ($receiving) {
            // Delete the record
            $stmt = $conn->prepare("DELETE FROM toner_receiving WHERE receive_id = ?");
            $stmt->bind_param("i", $receive_id);
            
            if ($stmt->execute()) {
                // Restore stock
                $update_stmt = $conn->prepare("UPDATE toner_master SET jct_stock = jct_stock - ?, uct_stock = uct_stock - ? WHERE toner_id = ?");
                $update_stmt->bind_param("iii", $receiving['jct_quantity'], $receiving['uct_quantity'], $receiving['toner_id']);
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
$toners = [];

// Get toner receivings from database
try {
    $result = $conn->query("SELECT * FROM toner_receiving ORDER BY receive_date DESC, receive_id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $receivings[] = $row;
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
                    Toner Receiving Management
                </h1>
                <p>Track and manage toner deliveries and stock receipts</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('receiveTonerModal')">
                    <i class="fas fa-plus"></i>
                    Receive New Toner
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
                Toner Receiving Records
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
                <p>Start by receiving your first toner delivery.</p>
                <button class="btn btn-primary" onclick="openModal('receiveTonerModal')">
                    <i class="fas fa-plus"></i>
                    Receive First Toner
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="receivingsTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Receive Date</th>
                            <th>Toner Model</th>
                            <th>LOT</th>
                            <th>Color</th>
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
                                <td><?php echo htmlspecialchars($receiving['toner_model']); ?></td>
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
                                <td>
                                    <span class="color-badge" style="background-color: <?php echo strtolower($receiving['color']); ?>">
                                        <?php echo htmlspecialchars($receiving['color']); ?>
                                    </span>
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
                                    <span class="quantity-badge" style="background: white; color: #333; border: 2px solid #667eea; font-weight: bold;"><?php echo ($receiving['jct_quantity'] + $receiving['uct_quantity']); ?></span>
                                </td>
                                <td>
                                    <div style="text-align: left;">
                                        <div>Rs. <?php echo number_format($receiving['unit_price'], 2); ?></div>
                                        <div style="margin-top: 5px;">
                                            <strong style="color: #28a745; font-size: 1.1em;">
                                                🎯 FULL PAYMENT: Rs. <?php echo number_format(($receiving['jct_quantity'] + $receiving['uct_quantity']) * $receiving['unit_price'], 2); ?>
                                            </strong>
                                        </div>
                                        <small style="color: #6c757d;">
                                            Calculation: <?php echo ($receiving['jct_quantity'] + $receiving['uct_quantity']); ?> × Rs. <?php echo number_format($receiving['unit_price'], 2); ?> = Rs. <?php echo number_format(($receiving['jct_quantity'] + $receiving['uct_quantity']) * $receiving['unit_price'], 2); ?>
                                        </small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($receiving['invoice'] ?: 'N/A'); ?></td>
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

<!-- Receive Toner Modal -->
<div id="receiveTonerModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-truck-loading"></i> Receive New Toner</h2>
            <span class="modal-close" onclick="closeModal('receiveTonerModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="" onsubmit="return validateForm('receiveTonerForm')" id="receiveTonerForm">
                <input type="hidden" name="action" value="receive">
                
                <!-- Toner Information -->
                <div class="form-section">
                    <h4><i class="fas fa-toner"></i> Toner Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Select Toner <span class="required">*</span></label>
                            <select name="toner_id" id="tonerSelect" class="form-control" required onchange="updateTonerDetails()">
                                <option value="">Select a toner</option>
                                <?php foreach ($toners as $toner): ?>
                                    <option value="<?php echo $toner['toner_id']; ?>" 
                                            data-model="<?php echo htmlspecialchars($toner['toner_model']); ?>"
                                            data-color="<?php echo htmlspecialchars($toner['color']); ?>">
                                        <?php echo htmlspecialchars($toner['toner_model']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Toner Model <span class="required">*</span></label>
                            <input type="text" name="toner_model" id="tonerModelDisplay" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Color</label>
                            <input type="text" name="color" id="colorDisplay" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">LOT Number</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold;" id="lotPrefix"><?php echo date('Y'); ?>/LOT</span>
                                <input type="number" name="lot_number" id="stockNumber" class="form-control" placeholder="Enter number (e.g., 1, 2, 3)" min="1" max="999">
                                <input type="hidden" name="lot" id="stockHidden">
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
                            <input type="number" name="jct_quantity" class="form-control" min="0" value="0" placeholder="Quantity for JCT">
                        </div>
                        <div class="form-col">
                            <label class="form-label">UCT Quantity</label>
                            <input type="number" name="uct_quantity" class="form-control" min="0" value="0" placeholder="Quantity for UCT">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Unit Price</label>
                            <input type="number" name="unit_price" class="form-control" step="0.01" min="0" placeholder="Price per unit">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Receive Date <span class="required">*</span></label>
                            <input type="date" name="receive_date" id="receiveDate" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('receiveTonerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" data-original-text="Receive Toner">
                        <i class="fas fa-truck-loading"></i> Receive Toner
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
            <h2><i class="fas fa-edit"></i> Edit Toner Receiving</h2>
            <span class="modal-close" onclick="closeModal('editReceivingModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editReceivingForm" method="POST" onsubmit="return validateForm('editReceivingForm')">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="receive_id" id="editReceiveId">
                
                <!-- Toner Information -->
                <div class="form-section">
                    <h4><i class="fas fa-toner"></i> Toner Information</h4>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Select Toner <span class="required">*</span></label>
                            <select name="toner_id" id="editTonerSelect" class="form-control" required onchange="updateEditTonerDetails()">
                                <option value="">Select a toner</option>
                                <?php foreach ($toners as $toner): ?>
                                    <option value="<?php echo $toner['toner_id']; ?>" 
                                            data-model="<?php echo htmlspecialchars($toner['toner_model']); ?>"
                                            data-color="<?php echo htmlspecialchars($toner['color']); ?>">
                                        <?php echo htmlspecialchars($toner['toner_model']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Toner Model <span class="required">*</span></label>
                            <input type="text" name="toner_model" id="editTonerModelDisplay" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Color</label>
                            <input type="text" name="color" id="editColorDisplay" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">LOT Number</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold;" id="editLotPrefix"><?php echo date('Y'); ?>/LOT</span>
                                <input type="number" name="edit_lot_number" id="editStockNumber" class="form-control" placeholder="Enter number (e.g., 1, 2, 3)" min="1" max="999">
                                <input type="hidden" name="lot" id="editStockHidden">
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
                            <input type="number" name="jct_quantity" id="editJctQuantity" class="form-control" min="0" placeholder="Quantity for JCT">
                        </div>
                        <div class="form-col">
                            <label class="form-label">UCT Quantity</label>
                            <input type="number" name="uct_quantity" id="editUctQuantity" class="form-control" min="0" placeholder="Quantity for UCT">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Unit Price</label>
                            <input type="number" name="unit_price" id="editUnitPrice" class="form-control" step="0.01" min="0" placeholder="Price per unit">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Receive Date <span class="required">*</span></label>
                            <input type="date" name="receive_date" id="editReceiveDate" class="form-control" required>
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
                    <button type="submit" class="btn btn-primary" data-original-text="Update Receiving">
                        <i class="fas fa-save"></i> Update Receiving
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Receiving Details Modal -->
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

<!-- Print Modal -->
<div id="printModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-print"></i> Print Toner Receiving Report</h2>
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

<script>
// Store receivings data for JavaScript access
const receivingsData = <?php echo json_encode($receivings); ?>;
const tonersData = <?php echo json_encode($toners); ?>;
</script>

<?php include '../includes/footer.php'; ?>