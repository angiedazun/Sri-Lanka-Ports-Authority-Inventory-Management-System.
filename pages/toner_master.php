
<?php
require_once '../includes/db.php';
require_login();

$page_title = "Toner Master - SLPA System";
$additional_css = ['../assets/css/toner-master.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/toner-master.js?v=' . time()];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages first
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Add new toner
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $toner_model = sanitize_input($_POST['toner_model']);
    $compatible_printers = sanitize_input($_POST['compatible_printers']);
    $reorder_level = (int)$_POST['reorder_level'];
    $jct_stock = (int)$_POST['jct_stock'];
    $uct_stock = (int)$_POST['uct_stock'];
    $color = sanitize_input($_POST['color']);
    $purchase_date = sanitize_input($_POST['purchase_date']);
    
    // Validate required fields
    if (empty($toner_model) || empty($compatible_printers) || empty($color) || empty($purchase_date)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Insert into database
        try {
            $stmt = $conn->prepare("INSERT INTO toner_master (toner_model, compatible_printers, reorder_level, jct_stock, uct_stock, color, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiisss", $toner_model, $compatible_printers, $reorder_level, $jct_stock, $uct_stock, $color, $purchase_date);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Toner added successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error adding toner to database: ' . $conn->error;
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

// Edit toner
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $toner_id = (int)$_POST['toner_id'];
    $toner_model = sanitize_input($_POST['toner_model']);
    $compatible_printers = sanitize_input($_POST['compatible_printers']);
    $reorder_level = (int)$_POST['reorder_level'];
    $jct_stock = (int)$_POST['jct_stock'];
    $uct_stock = (int)$_POST['uct_stock'];
    $color = sanitize_input($_POST['color']);
    $purchase_date = sanitize_input($_POST['purchase_date']);
    
    // Validate toner_id exists
    if (empty($toner_id)) {
        $_SESSION['message'] = 'Invalid toner ID!';
        $_SESSION['message_type'] = 'error';
    } else {
        try {
            // Update all fields including stock quantities using toner_id directly
            $stmt = $conn->prepare("UPDATE toner_master SET toner_model = ?, compatible_printers = ?, reorder_level = ?, jct_stock = ?, uct_stock = ?, color = ?, purchase_date = ? WHERE toner_id = ?");
            $stmt->bind_param("ssiisssi", $toner_model, $compatible_printers, $reorder_level, $jct_stock, $uct_stock, $color, $purchase_date, $toner_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['message'] = 'Toner updated successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'No changes made or toner not found!';
                    $_SESSION['message_type'] = 'warning';
                }
            } else {
                $_SESSION['message'] = 'Error updating toner: ' . $conn->error;
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

// Delete toner
if (isset($_GET['delete'])) {
    $toner_id = (int)$_GET['delete'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Delete related records from toner_issuing
        $deleteIssuing = $conn->prepare("DELETE FROM toner_issuing WHERE toner_id = ?");
        $deleteIssuing->bind_param("i", $toner_id);
        $deleteIssuing->execute();
        $issuingDeleted = $deleteIssuing->affected_rows;
        $deleteIssuing->close();
        
        // Delete related records from toner_receiving
        $deleteReceiving = $conn->prepare("DELETE FROM toner_receiving WHERE toner_id = ?");
        $deleteReceiving->bind_param("i", $toner_id);
        $deleteReceiving->execute();
        $receivingDeleted = $deleteReceiving->affected_rows;
        $deleteReceiving->close();
        
        // Delete related records from toner_return
        $deleteReturn = $conn->prepare("DELETE FROM toner_return WHERE toner_id = ?");
        $deleteReturn->bind_param("i", $toner_id);
        $deleteReturn->execute();
        $returnDeleted = $deleteReturn->affected_rows;
        $deleteReturn->close();
        
        // Finally delete the toner from toner_master
        $stmt = $conn->prepare("DELETE FROM toner_master WHERE toner_id = ?");
        $stmt->bind_param("i", $toner_id);
        
        if ($stmt->execute()) {
            // Commit transaction
            $conn->commit();
            
            $message = 'Toner deleted successfully!';
            $deletedRecords = [];
            if ($issuingDeleted > 0) $deletedRecords[] = $issuingDeleted . ' issue record(s)';
            if ($receivingDeleted > 0) $deletedRecords[] = $receivingDeleted . ' receiving record(s)';
            if ($returnDeleted > 0) $deletedRecords[] = $returnDeleted . ' return record(s)';
            
            if (!empty($deletedRecords)) {
                $message .= ' Also deleted ' . implode(', ', $deletedRecords) . '.';
            }
            $_SESSION['message'] = $message;
            $_SESSION['message_type'] = 'success';
        } else {
            // Rollback transaction
            $conn->rollback();
            $_SESSION['message'] = 'Error deleting toner: ' . $conn->error;
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

// Get toners from database
$toners = [];
try {
    $result = $conn->query("SELECT * FROM toner_master ORDER BY toner_id DESC");
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
$total_toners = count($toners);
$active_toners = count($toners); // All toners are considered active for now
$total_jct_stock = array_sum(array_column($toners, 'jct_stock'));
$total_uct_stock = array_sum(array_column($toners, 'uct_stock'));
$low_stock = count(array_filter($toners, function($t) { 
    $total_stock = $t['jct_stock'] + $t['uct_stock'];
    return $total_stock > 0 && $total_stock <= 5; // Show items with 5 or fewer toners
}));
$out_of_stock = count(array_filter($toners, function($t) { 
    return ($t['jct_stock'] + $t['uct_stock']) == 0; 
}));

// Debug information (remove in production)
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Database toners count: " . count($toners) . " items\n";
    echo "Database connection status: " . ($conn ? "Connected" : "Not connected") . "\n";
    echo "Database name: " . $conn->get_server_info() . "\n";
    if (!empty($toners)) {
        echo "Sample toner data:\n";
        echo print_r($toners[0], true);
    } else {
        echo "No toners found in database\n";
        echo "Last SQL error: " . $conn->error . "\n";
    }
    echo "</pre>";
}

include '../includes/header.php';
?>

<div class="toner-master-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>
                <i class="fas fa-print"></i>
                Toner Master Management
            </h1>
            <p>Manage your toner cartridge inventory, stock levels, and product information</p>
        </div>
    </div>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>" id="alert-message">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Quick Statistics -->
        <div class="quick-stats">
            <div class="stat-card-toner total">
                <div class="stat-icon" style="color: #667eea;">
                    <i class="fas fa-print"></i>
                </div>
                <div class="stat-number" style="color: #667eea;"><?php echo $total_toners; ?></div>
                <div class="stat-label">Total Toners</div>
            </div>
            
            <div class="stat-card-toner active">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="stat-number" style="color: #28a745;"><?php echo $total_jct_stock; ?></div>
                <div class="stat-label">JCT Stock</div>
            </div>
            
            <div class="stat-card-toner low-stock">
                <div class="stat-icon" style="color: #17a2b8;">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-number" style="color: #17a2b8;"><?php echo $total_uct_stock; ?></div>
                <div class="stat-label">UCT Stock</div>
            </div>
            
            <div class="stat-card-toner out-of-stock">
                <div class="stat-icon" style="color: #dc3545;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number" style="color: #dc3545;"><?php echo $low_stock; ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-add" onclick="openModal('addTonerModal')">
                <i class="fas fa-plus"></i>
                Add New Toner
            </button>
            <button class="btn btn-import">
                <i class="fas fa-file-import"></i>
                Import from Excel
            </button>
            <button class="btn btn-export" onclick="exportToCSV('tonerTable', 'toner_master_export.csv')">
                <i class="fas fa-file-export"></i>
                Export to Excel
            </button>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filter-section">
            <h3>
                <i class="fas fa-search"></i>
                Search & Filter
            </h3>
            <div class="filter-row">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search toner code, name, brand, or model..." onkeyup="filterTable()">
                </div>
                <div>
                    <select id="brandFilter" class="form-control" onchange="filterTable()">
                        <option value="">All Brands</option>
                        <option value="HP">HP</option>
                        <option value="Canon">Canon</option>
                        <option value="Epson">Epson</option>
                        <option value="Brother">Brother</option>
                        <option value="Samsung">Samsung</option>
                    </select>
                </div>
                <div>
                    <select id="colorFilter" class="form-control" onchange="filterTable()">
                        <option value="">All Colors</option>
                        <option value="Black">Black</option>
                        <option value="Cyan">Cyan</option>
                        <option value="Magenta">Magenta</option>
                        <option value="Yellow">Yellow</option>
                        <option value="Tri-Color">Tri-Color</option>
                    </select>
                </div>
                <div>
                    <select id="locationFilter" class="form-control" onchange="filterTable()">
                        <option value="">All Locations</option>
                        <option value="JCT">JCT Only</option>
                        <option value="UCT">UCT Only</option>
                        <option value="Both">Both Locations</option>
                    </select>
                </div>
            </div>
            <div class="filter-buttons">
                <button class="btn-filter" onclick="filterByStock('all')">All Items</button>
                <button class="btn-filter" onclick="filterByStock('good')">Good Stock</button>
                <button class="btn-filter" onclick="filterByStock('low')">Low Stock</button>
                <button class="btn-filter" onclick="filterByStock('out')">Out of Stock</button>
            </div>
        </div>

        <!-- Toner Inventory Table -->
        <div class="inventory-table-container">
            <div class="table-header">
                <h3 class="table-title">
                    <i class="fas fa-list"></i>
                    Toner Inventory
                </h3>
                <div class="table-actions">
                    <button class="btn btn-sm btn-secondary" onclick="openModal('printDateModal')">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="btn btn-sm btn-info" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <?php if (empty($toners)): ?>
                    <div class="empty-state">
                        <i class="fas fa-print"></i>
                        <h3>No Toner Items Found</h3>
                        <p>Start building your toner inventory by adding your first toner cartridge.</p>
                        <button class="btn btn-primary" onclick="openModal('addTonerModal')">
                            <i class="fas fa-plus"></i> Add First Toner
                        </button>
                    </div>
                <?php else: ?>
                    <table class="toner-table" id="tonerTable">
                        <thead>
                            <tr>
                                <th>Toner Model</th>
                                <th>Compatible Printers</th>
                                <th>Color</th>
                                <th>JCT Stock</th>
                                <th>UCT Stock</th>
                                <th>Total Stock</th>
                                <th>Reorder Level</th>
                                <th>Purchase Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($toners as $index => $toner): ?>
                                <?php 
                                $total_stock = $toner['jct_stock'] + $toner['uct_stock'];
                                $stock_class = 'stock-good';
                                if ($total_stock == 0) {
                                    $stock_class = 'stock-critical';
                                } elseif ($total_stock <= $toner['reorder_level']) {
                                    $stock_class = 'stock-low';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($toner['toner_model']); ?></td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($toner['compatible_printers']); ?>">
                                            <?php echo htmlspecialchars($toner['compatible_printers']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($toner['color']) == 'black' ? 'secondary' : 'primary'; ?>">
                                            <?php echo htmlspecialchars($toner['color']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="stock-level">
                                            <span class="stock-indicator <?php echo $toner['jct_stock'] > 0 ? 'stock-good' : 'stock-critical'; ?>"></span>
                                            <span class="stock-number"><?php echo $toner['jct_stock']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="stock-level">
                                            <span class="stock-indicator <?php echo $toner['uct_stock'] > 0 ? 'stock-good' : 'stock-critical'; ?>"></span>
                                            <span class="stock-number"><?php echo $toner['uct_stock']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="stock-level">
                                            <span class="stock-indicator <?php echo $stock_class; ?>"></span>
                                            <span class="stock-number" style="font-weight: bold; font-size: 1.1rem;"><?php echo $total_stock; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $toner['reorder_level']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($toner['purchase_date'])); ?></td>
                                    <td>
                                        <?php
                                            $total_stock = $toner['jct_stock'] + $toner['uct_stock'];
                                            if ($total_stock == 0) {
                                                echo '<span class="status-badge status-critical">🔴 OUT OF STOCK</span>';
                                            } elseif ($total_stock <= $toner['reorder_level']) {
                                                echo '<span class="status-badge status-low">🟡 LOW STOCK</span>';
                                            } else {
                                                echo '<span class="status-badge status-good">🟢 IN STOCK</span>';
                                            }
                                        ?>
                                    </td>
                                    <td class="table-actions-cell">
                                        <button class="btn btn-sm btn-view" title="View Details" onclick="viewToner(<?php echo $index; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-edit" title="Edit" onclick="openEditModal(<?php echo $index; ?>, <?php echo $toner['toner_id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-delete" title="Delete" 
                                                onclick="confirmDelete(<?php echo $toner['toner_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Toner Modal -->
<div id="addTonerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Add New Toner</h3>
            <button class="close" onclick="closeModal('addTonerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Toner Model <span class="required">*</span></label>
                        <input type="text" name="toner_model" class="form-control" required placeholder="e.g., HP 85A CE285A">
                    </div>
                    <div class="form-col">
                        <label class="form-label">Color <span class="required">*</span></label>
                        <select name="color" class="form-control" required>
                            <option value="">Select Color</option>
                            <option value="Black">Black</option>
                            <option value="Cyan">Cyan</option>
                            <option value="Magenta">Magenta</option>
                            <option value="Yellow">Yellow</option>
                            <option value="Tri-Color">Tri-Color</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Compatible Printers <span class="required">*</span></label>
                    <textarea name="compatible_printers" class="form-control" rows="3" required 
                              placeholder="List all compatible printer models, separated by commas"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">JCT Stock <span class="required">*</span></label>
                        <input type="number" name="jct_stock" class="form-control" required min="0" value="0">
                    </div>
                    <div class="form-col">
                        <label class="form-label">UCT Stock <span class="required">*</span></label>
                        <input type="number" name="uct_stock" class="form-control" required min="0" value="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Reorder Level <span class="required">*</span></label>
                        <input type="number" name="reorder_level" class="form-control" required min="1" value="5">
                    </div>
                    <div class="form-col">
                        <label class="form-label">Purchase Date <span class="required">*</span></label>
                        <input type="date" name="purchase_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addTonerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Toner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Toner Modal -->
<div id="editTonerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Toner</h3>
            <button class="close" onclick="closeModal('editTonerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="toner_id" id="editTonerId" value="">
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Toner Model <span class="required">*</span></label>
                        <input type="text" name="toner_model" id="editTonerModel" class="form-control" required placeholder="e.g., HP 85A CE285A">
                    </div>
                    <div class="form-col">
                        <label class="form-label">Color <span class="required">*</span></label>
                        <select name="color" id="editColor" class="form-control" required>
                            <option value="">Select Color</option>
                            <option value="Black">Black</option>
                            <option value="Cyan">Cyan</option>
                            <option value="Magenta">Magenta</option>
                            <option value="Yellow">Yellow</option>
                            <option value="Tri-Color">Tri-Color</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Compatible Printers <span class="required">*</span></label>
                    <textarea name="compatible_printers" id="editCompatiblePrinters" class="form-control" rows="3" required 
                              placeholder="List all compatible printer models, separated by commas"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">JCT Stock <span class="required">*</span></label>
                        <input type="number" name="jct_stock" id="editJctStock" class="form-control" required min="0">
                    </div>
                    <div class="form-col">
                        <label class="form-label">UCT Stock <span class="required">*</span></label>
                        <input type="number" name="uct_stock" id="editUctStock" class="form-control" required min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Reorder Level <span class="required">*</span></label>
                        <input type="number" name="reorder_level" id="editReorderLevel" class="form-control" required min="1">
                    </div>
                    <div class="form-col">
                        <label class="form-label">Purchase Date <span class="required">*</span></label>
                        <input type="date" name="purchase_date" id="editPurchaseDate" class="form-control" required max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editTonerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Toner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Toner Details Modal -->
<div id="viewTonerModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> Toner Details</h3>
            <button class="close" onclick="closeModal('viewTonerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="details-container">
                <!-- Toner Information Section -->
                <div class="info-section">
                    <div class="section-header">
                        <h4><i class="fas fa-print"></i> Toner Information</h4>
                    </div>
                    <div class="info-content">
                        <div class="info-row">
                            <label class="info-label">TONER MODEL:</label>
                            <div class="info-value toner-model" id="detail-toner-model">N/A</div>
                        </div>
                        <div class="info-row">
                            <label class="info-label">STOCK:</label>
                            <div class="info-value stock-info">
                                <span class="stock-badge jct" id="detail-jct-stock">JCT: 0</span>
                                <span class="stock-badge uct" id="detail-uct-stock">UCT: 0</span>
                                <span class="stock-badge total" id="detail-total-stock">Total: 0</span>
                            </div>
                        </div>
                        <div class="info-row">
                            <label class="info-label">COLOR:</label>
                            <div class="info-value">
                                <div class="color-display" id="detail-color-display">
                                    <span class="color-text" id="detail-color">N/A</span>
                                </div>
                            </div>
                        </div>
                        <div class="info-row">
                            <label class="info-label">PURCHASE DATE:</label>
                            <div class="info-value date-value" id="detail-purchase-date">N/A</div>
                        </div>
                        <div class="info-row">
                            <label class="info-label">REORDER LEVEL:</label>
                            <div class="info-value reorder-value" id="detail-reorder-level">0</div>
                        </div>
                        <div class="info-row">
                            <label class="info-label">STATUS:</label>
                            <div class="info-value status-value" id="detail-status">N/A</div>
                        </div>
                    </div>
                </div>

                <!-- Compatible Printers Section -->
                <div class="info-section">
                    <div class="section-header">
                        <h4><i class="fas fa-desktop"></i> Compatible Printers</h4>
                    </div>
                    <div class="info-content">
                        <div class="info-row">
                            <div class="printers-list" id="detail-printers-list">
                                <span class="printer-tag">No compatible printers listed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewTonerModal')">Close</button>
            <button type="button" class="btn btn-primary" onclick="closeModal('viewTonerModal'); openEditModalFromView()">
                <i class="fas fa-edit"></i> Edit Toner
            </button>
        </div>
    </div>
</div>

<!-- Print Date Selection Modal -->
<div id="printDateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-print"></i> Print Toner Master Report</h3>
            <button class="close" onclick="closeModal('printDateModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="printForm" target="_blank">
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" id="print_from_date" class="form-control">
                        <small class="form-text">Leave empty to include all dates</small>
                    </div>
                    <div class="form-col">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" id="print_to_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        <small class="form-text">Leave empty to include all dates</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Filter by Month</label>
                        <select name="filter_month" id="print_filter_month" class="form-control">
                            <option value="">All Months</option>
                            <option value="01">January</option>
                            <option value="02">February</option>
                            <option value="03">March</option>
                            <option value="04">April</option>
                            <option value="05">May</option>
                            <option value="06">June</option>
                            <option value="07">July</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label">Filter by Year</label>
                        <select name="filter_year" id="print_filter_year" class="form-control">
                            <option value="">All Years</option>
                            <?php
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= $current_year - 10; $year--) {
                                echo "<option value='$year'>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Print Options</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_zero_stock" value="1">
                            <span class="checkmark"></span>
                            Include toners with zero stock
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_summary" value="1" checked>
                            <span class="checkmark"></span>
                            Include summary statistics
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('printDateModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="generatePrintReport()">
                        <i class="fas fa-print"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Enhanced Modal Styles for Details View - Compact Version */
.modal {
    z-index: 1050 !important;
}

.modal-large {
    max-width: 600px;
    width: 85%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-content {
    margin: 3% auto !important;
    max-height: 85vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border-radius: 8px !important;
}

.modal-header {
    padding: 12px 20px !important;
}

.modal-header h3 {
    font-size: 18px !important;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    max-height: calc(80vh - 100px);
    padding: 10px 15px !important;
}

.details-container {
    padding: 5px 0;
}

.info-section {
    margin-bottom: 15px;
    border: 1px solid #e0e6ed;
    border-radius: 6px;
    overflow: hidden;
    background: white;
}

.section-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 15px;
    border-bottom: 1px solid #e0e6ed;
}

.section-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.section-header i {
    margin-right: 6px;
}

.info-content {
    padding: 10px 15px;
}

.info-row {
    display: flex;
    align-items: flex-start;
    margin-bottom: 8px;
    min-height: 28px;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-label {
    font-weight: 600;
    color: #495057;
    min-width: 110px;
    margin-right: 12px;
    font-size: 11px;
    line-height: 1.3;
    padding-top: 3px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.info-value {
    flex: 1;
    font-size: 12px;
    line-height: 1.3;
}

.toner-model {
    font-weight: 600;
    color: #667eea;
    font-size: 14px;
    padding: 6px 10px;
    background: #f8f9fa;
    border-radius: 3px;
    border-left: 3px solid #667eea;
}

.stock-info {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.stock-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 11px;
    white-space: nowrap;
}

.stock-badge.jct {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.stock-badge.uct {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.stock-badge.total {
    background: #667eea;
    color: white;
    border: 1px solid #667eea;
}

.color-display {
    padding: 6px 10px;
    border-radius: 15px;
    text-align: center;
    font-weight: 600;
    min-width: 80px;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 11px;
}

.color-display.black {
    background: #212529;
    color: white;
}

.color-display.cyan {
    background: #17a2b8;
    color: white;
}

.color-display.magenta {
    background: #e83e8c;
    color: white;
}

.color-display.yellow {
    background: #ffc107;
    color: #212529;
}

.color-display.tri-color, .color-display.tricolor {
    background: linear-gradient(45deg, #17a2b8, #e83e8c, #ffc107);
    color: white;
}

.date-value {
    padding: 4px 8px;
    background: #f8f9fa;
    border-radius: 3px;
    font-family: monospace;
    font-size: 12px;
    color: #495057;
}

.reorder-value {
    padding: 4px 8px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 3px;
    font-weight: 600;
    color: #856404;
    font-size: 13px;
}

.status-value {
    padding: 4px 8px;
    border-radius: 3px;
    font-weight: 600;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    font-size: 12px;
}

.printers-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: flex-start;
    max-height: 80px;
    overflow-y: auto;
}

.printer-tag {
    padding: 4px 8px;
    background: #e9ecef;
    border: 1px solid #ced4da;
    border-radius: 3px;
    font-size: 11px;
    color: #495057;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.printer-tag:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 10px;
    padding: 10px 15px;
    border-top: 1px solid #e0e0e0;
    background: #f8f9fa;
    flex-shrink: 0;
}

.modal-footer .btn {
    padding: 6px 12px;
    border: none;
    border-radius: 3px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 12px;
}

.modal-footer .btn-secondary {
    background: #6c757d;
    color: white;
}

.modal-footer .btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.modal-footer .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modal-footer .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(102, 126, 234, 0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-large {
        width: 95%;
        max-height: 90vh;
    }
    
    .modal-content {
        margin: 1% auto !important;
    }
    
    .info-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .info-label {
        min-width: auto;
        margin-bottom: 3px;
    }
    
    .stock-info {
        flex-direction: column;
        gap: 4px;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .modal-footer .btn {
        width: 100%;
    }
}

/* Checkbox styles for print modal */
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 8px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #495057;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 18px;
    height: 18px;
    background-color: #fff;
    border: 2px solid #ddd;
    border-radius: 3px;
    position: relative;
    transition: all 0.2s ease;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
    background-color: #667eea;
    border-color: #667eea;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark:after {
    content: '';
    position: absolute;
    left: 5px;
    top: 2px;
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.form-text {
    font-size: 11px;
    color: #6c757d;
    margin-top: 3px;
}
</style>

<script>
// Delete confirmation
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this toner? This action cannot be undone.')) {
        window.location.href = '?delete=' + id;
    }
}

// Filter table function
function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const brandFilter = document.getElementById('brandFilter').value.toLowerCase();
    const colorFilter = document.getElementById('colorFilter').value.toLowerCase();
    
    const table = document.getElementById('tonerTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        
        if (cells.length > 0) {
            const tonerModel = cells[0].textContent.toLowerCase();
            const compatiblePrinters = cells[1].textContent.toLowerCase();
            const color = cells[2].textContent.toLowerCase();
            
            const matchesSearch = tonerModel.includes(searchInput) || compatiblePrinters.includes(searchInput);
            const matchesBrand = !brandFilter || tonerModel.includes(brandFilter);
            const matchesColor = !colorFilter || color.includes(colorFilter);
            
            if (matchesSearch && matchesBrand && matchesColor) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }
}

// Filter by stock level
function filterByStock(type) {
    // Remove active class from all filter buttons
    const filterButtons = document.querySelectorAll('.btn-filter');
    filterButtons.forEach(btn => btn.classList.remove('active'));
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    const table = document.getElementById('tonerTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const stockCell = row.getElementsByTagName('td')[5];
        const minStockCell = row.getElementsByTagName('td')[6];
        
        if (stockCell && minStockCell) {
            const currentStock = parseInt(stockCell.textContent.trim());
            const minStock = parseInt(minStockCell.textContent.trim());
            
            let showRow = false;
            
            switch(type) {
                case 'all':
                    showRow = true;
                    break;
                case 'good':
                    showRow = currentStock > minStock;
                    break;
                case 'low':
                    showRow = currentStock <= minStock && currentStock > 0;
                    break;
                case 'out':
                    showRow = currentStock === 0;
                    break;
            }
            
            row.style.display = showRow ? '' : 'none';
        }
    }
}

// Auto-hide alert after 5 seconds
setTimeout(function() {
    const alert = document.getElementById('alert-message');
    if (alert) {
        alert.style.opacity = '0';
        setTimeout(() => alert.style.display = 'none', 300);
    }
}, 5000);

// Generate print report function
function generatePrintReport() {
    // Get form values
    const fromDate = document.getElementById('print_from_date').value;
    const toDate = document.getElementById('print_to_date').value;
    const filterMonth = document.getElementById('print_filter_month').value;
    const filterYear = document.getElementById('print_filter_year').value;
    const includeZeroStock = document.querySelector('input[name="include_zero_stock"]').checked;
    const includeSummary = document.querySelector('input[name="include_summary"]').checked;
    
    // Build URL parameters
    const params = new URLSearchParams();
    if (fromDate) params.append('from_date', fromDate);
    if (toDate) params.append('to_date', toDate);
    if (filterMonth) params.append('filter_month', filterMonth);
    if (filterYear) params.append('filter_year', filterYear);
    if (includeZeroStock) params.append('include_zero_stock', '1');
    if (includeSummary) params.append('include_summary', '1');
    
    // Create the print URL
    const printUrl = 'toner_master_print.php?' + params.toString();
    
    // Open print page in new window
    window.open(printUrl, '_blank');
    
    // Close the modal
    closeModal('printDateModal');
}
</script>

<?php include '../includes/footer.php'; ?>