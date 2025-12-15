<?php
require_once '../includes/db.php';
require_login();

$page_title = "Papers Master - SLPA System";
$additional_css = ['../assets/css/papers-master.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/papers-master.js'];

// Handle form submissions
$message = '';
$message_type = '';

// Check for session messages first
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Add new paper
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $paper_type = sanitize_input($_POST['paper_type']);
    $reorder_level = (int)$_POST['reorder_level'];
    $jct_stock = (int)$_POST['jct_stock'];
    $uct_stock = (int)$_POST['uct_stock'];
    $purchase_date = sanitize_input($_POST['purchase_date']);
    
    // Validate required fields
    if (empty($paper_type) || empty($purchase_date)) {
        $_SESSION['message'] = 'Please fill in all required fields!';
        $_SESSION['message_type'] = 'error';
    } else {
        // Insert into database
        try {
            $stmt = $conn->prepare("INSERT INTO papers_master (paper_type, reorder_level, jct_stock, uct_stock, purchase_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siiis", $paper_type, $reorder_level, $jct_stock, $uct_stock, $purchase_date);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Paper added successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error adding paper to database: ' . $conn->error;
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

// Edit paper
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $edit_index = (int)$_POST['edit_index'];
    $paper_type = sanitize_input($_POST['paper_type']);
    $reorder_level = (int)$_POST['reorder_level'];
    $jct_stock = (int)$_POST['jct_stock'];
    $uct_stock = (int)$_POST['uct_stock'];
    $purchase_date = sanitize_input($_POST['purchase_date']);
    
    // Get paper_id from database based on index
    try {
        $result = $conn->query("SELECT paper_id FROM papers_master ORDER BY paper_id LIMIT 1 OFFSET $edit_index");
        
        if ($result && $row = $result->fetch_assoc()) {
            $paper_id = $row['paper_id'];
            
            $stmt = $conn->prepare("UPDATE papers_master SET paper_type = ?, reorder_level = ?, jct_stock = ?, uct_stock = ?, purchase_date = ? WHERE paper_id = ?");
            $stmt->bind_param("siiisi", $paper_type, $reorder_level, $jct_stock, $uct_stock, $purchase_date, $paper_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Paper updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error updating paper: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Error: Paper not found!';
            $_SESSION['message_type'] = 'error';
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Database error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Delete paper
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $paper_id = (int)$_GET['delete'];
    
    if ($paper_id > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM papers_master WHERE paper_id = ?");
            $stmt->bind_param("i", $paper_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['message'] = 'Paper deleted successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Paper not found or already deleted.';
                    $_SESSION['message_type'] = 'warning';
                }
            } else {
                $_SESSION['message'] = 'Error deleting paper: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Redirect to prevent URL manipulation
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get papers from database
$papers = [];
try {
    $result = $conn->query("SELECT * FROM papers_master ORDER BY paper_id DESC");
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
$total_papers = count($papers);
$active_papers = count($papers);
$total_jct_stock = array_sum(array_column($papers, 'jct_stock'));
$total_uct_stock = array_sum(array_column($papers, 'uct_stock'));
$low_stock = count(array_filter($papers, function($p) { 
    $total_stock = $p['jct_stock'] + $p['uct_stock'];
    return $total_stock <= $p['reorder_level'] && $total_stock > 0; 
}));
$out_of_stock = count(array_filter($papers, function($p) { 
    return ($p['jct_stock'] + $p['uct_stock']) == 0; 
}));

include '../includes/header.php';
?>

<div class="toner-master-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>
                <i class="fas fa-file-alt"></i>
                Papers Master Management
            </h1>
            <p>Manage your paper inventory, stock levels, and product information</p>
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
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-number" style="color: #667eea;"><?php echo $total_papers; ?></div>
                <div class="stat-label">Total Papers</div>
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
            <button class="btn btn-add" onclick="openModal('addPaperModal')">
                <i class="fas fa-plus"></i>
                Add New Paper
            </button>
            <button class="btn btn-import">
                <i class="fas fa-file-import"></i>
                Import from Excel
            </button>
            <button class="btn btn-export" onclick="exportToCSV('paperTable', 'papers_master_export.csv')">
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
                    <input type="text" id="searchInput" class="form-control" placeholder="Search paper type, size, brand..." onkeyup="filterTable()">
                </div>
                <div>
                    <select id="sizeFilter" class="form-control" onchange="filterTable()">
                        <option value="">All Sizes</option>
                        <option value="A4">A4</option>
                        <option value="A3">A3</option>
                        <option value="A5">A5</option>
                        <option value="Legal">Legal</option>
                        <option value="Letter">Letter</option>
                    </select>
                </div>
                <div>
                    <select id="bundleFilter" class="form-control" onchange="filterTable()">
                        <option value="">All Bundle Types</option>
                        <option value="Bundle">Bundle</option>
                        <option value="Ream">Ream</option>
                        <option value="Box">Box</option>
                        <option value="Pack">Pack</option>
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

        <!-- Paper Inventory Table -->
        <div class="inventory-table-container">
            <div class="table-header">
                <h3 class="table-title">
                    <i class="fas fa-list"></i>
                    Paper Inventory
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
                <?php if (empty($papers)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No Paper Items Found</h3>
                        <p>Start building your paper inventory by adding your first paper.</p>
                        <button class="btn btn-primary" onclick="openModal('addPaperModal')">
                            <i class="fas fa-plus"></i> Add First Paper
                        </button>
                    </div>
                <?php else: ?>
                    <table class="toner-table" id="paperTable">
                        <thead>
                            <tr>
                                <th>Paper Type</th>
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
                            <?php foreach ($papers as $index => $paper): ?>
                                <?php 
                                $total_stock = $paper['jct_stock'] + $paper['uct_stock'];
                                $stock_class = 'stock-good';
                                if ($total_stock == 0) {
                                    $stock_class = 'stock-critical';
                                } elseif ($total_stock <= $paper['reorder_level']) {
                                    $stock_class = 'stock-low';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($paper['paper_type']); ?></td>
                                    <td>
                                        <div class="stock-level">
                                            <span class="stock-indicator <?php echo $paper['jct_stock'] > 0 ? 'stock-good' : 'stock-critical'; ?>"></span>
                                            <span class="stock-number"><?php echo $paper['jct_stock']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="stock-level">
                                            <span class="stock-indicator <?php echo $paper['uct_stock'] > 0 ? 'stock-good' : 'stock-critical'; ?>"></span>
                                            <span class="stock-number"><?php echo $paper['uct_stock']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="stock-level">
                                            <span class="stock-indicator <?php echo $stock_class; ?>"></span>
                                            <span class="stock-number" style="font-weight: bold; font-size: 1.1rem;"><?php echo $total_stock; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $paper['reorder_level']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($paper['purchase_date'])); ?></td>
                                    <td>
                                        <?php
                                            $total_stock = $paper['jct_stock'] + $paper['uct_stock'];
                                            if ($total_stock == 0) {
                                                echo '<span class="status-badge status-critical">🔴 OUT OF STOCK</span>';
                                            } elseif ($total_stock <= $paper['reorder_level']) {
                                                echo '<span class="status-badge status-low">🟡 LOW STOCK</span>';
                                            } else {
                                                echo '<span class="status-badge status-good">🟢 IN STOCK</span>';
                                            }
                                        ?>
                                    </td>
                                    <td class="table-actions-cell">
                                        <button class="btn btn-sm btn-view" title="View Details" onclick="viewPaper(<?php echo $index; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-edit" title="Edit" onclick="openEditModal(<?php echo $index; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-delete" title="Delete" onclick="deletePaper(<?php echo $paper['paper_id']; ?>);">
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

<!-- Add Paper Modal -->
<div id="addPaperModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Add New Paper</h3>
            <button class="close" onclick="closeModal('addPaperModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="paper_type">Paper Type *</label>
                        <input type="text" id="paper_type" name="paper_type" class="form-control" placeholder="Enter paper type" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="jct_stock">JCT Initial Stock</label>
                        <input type="number" id="jct_stock" name="jct_stock" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-col">
                        <label for="uct_stock">UCT Initial Stock</label>
                        <input type="number" id="uct_stock" name="uct_stock" class="form-control" value="0" min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="reorder_level">Reorder Level (Minimum Stock)</label>
                        <input type="number" id="reorder_level" name="reorder_level" class="form-control" value="10" min="0">
                    </div>
                    <div class="form-col">
                        <label for="purchase_date">Purchase Date *</label>
                        <input type="date" id="purchase_date" name="purchase_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addPaperModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Paper
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Paper Modal -->
<div id="editPaperModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Paper</h3>
            <button class="close" onclick="closeModal('editPaperModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_index" id="editIndex" value="">
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="edit_paper_type">Paper Type *</label>
                        <input type="text" id="edit_paper_type" name="paper_type" class="form-control" placeholder="Enter paper type" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="edit_jct_stock">JCT Stock</label>
                        <input type="number" id="edit_jct_stock" name="jct_stock" class="form-control" min="0">
                    </div>
                    <div class="form-col">
                        <label for="edit_uct_stock">UCT Stock</label>
                        <input type="number" id="edit_uct_stock" name="uct_stock" class="form-control" min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="edit_reorder_level">Reorder Level</label>
                        <input type="number" id="edit_reorder_level" name="reorder_level" class="form-control" min="0">
                    </div>
                    <div class="form-col">
                        <label for="edit_purchase_date">Purchase Date *</label>
                        <input type="date" id="edit_purchase_date" name="purchase_date" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editPaperModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Paper
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Paper Details Modal - Exact Toner Master Style -->
<div id="viewPaperModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> Paper Details</h3>
            <button class="close" onclick="closeModal('viewPaperModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="details-container">
                <!-- Paper Information Section -->
                <div class="info-section">
                    <div class="section-header">
                        <h4><i class="fas fa-file-alt"></i> Paper Information</h4>
                    </div>
                    <div class="info-content">
                        <div class="info-row">
                            <span class="info-label">PAPER TYPE:</span>
                            <span class="info-value">
                                <span class="toner-model" id="view_paper_type"></span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">STOCK:</span>
                            <span class="info-value">
                                <span class="stock-badge jct" id="view_jct_stock"></span>
                                <span class="stock-badge uct" id="view_uct_stock"></span>
                                <span class="stock-badge total" id="view_total_stock"></span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">COLOR:</span>
                            <span class="info-value">
                                <span class="color-display" id="view_paper_size_badge"></span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">PURCHASE DATE:</span>
                            <span class="info-value">
                                <span class="date-value" id="view_purchase_date"></span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">REORDER LEVEL:</span>
                            <span class="info-value">
                                <span class="reorder-value" id="view_reorder_level"></span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">STATUS:</span>
                            <span class="info-value">
                                <span class="status-value" id="view_status"></span>
                            </span>
                        </div>
                    </div>
                </div>


            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewPaperModal')">Close</button>
            <button type="button" class="btn btn-primary" onclick="closeModal('viewPaperModal'); openEditModalFromView()">
                <i class="fas fa-edit"></i> Edit Paper
            </button>
        </div>
    </div>
</div>

<!-- Print Date Selection Modal -->
<div id="printDateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-print"></i> Print Papers Master Report</h3>
            <button class="close" onclick="closeModal('printDateModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="printForm" target="_blank">
                <div class="form-row">
                    <div class="form-col">
                        <label for="print_from_date">From Date</label>
                        <input type="date" id="print_from_date" name="from_date" class="form-control">
                    </div>
                    <div class="form-col">
                        <label for="print_to_date">To Date</label>
                        <input type="date" id="print_to_date" name="to_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="print_filter_month">Filter by Month</label>
                        <select id="print_filter_month" name="filter_month" class="form-control">
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
                        <label for="print_filter_year">Filter by Year</label>
                        <select id="print_filter_year" name="filter_year" class="form-control">
                            <option value="">All Years</option>
                            <?php for($year = date('Y'); $year >= date('Y')-5; $year--): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_zero_stock" value="1">
                        <span class="checkmark"></span>
                        Include zero stock items
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_summary" value="1" checked>
                        <span class="checkmark"></span>
                        Include summary statistics
                    </label>
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
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
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
// Store papers data in JavaScript
const papersData = <?php echo json_encode($papers); ?>;
let currentViewIndex = -1;

// Auto-update sheets per bundle based on bundle type selection
function updateSheetsPerBundle(bundleSelectId, sheetsInputId) {
    const bundleSelect = document.getElementById(bundleSelectId);
    const sheetsInput = document.getElementById(sheetsInputId);
    
    if (bundleSelect && sheetsInput) {
        const selectedOption = bundleSelect.options[bundleSelect.selectedIndex];
        const sheets = selectedOption.getAttribute('data-sheets');
        
        if (sheets) {
            sheetsInput.value = sheets;
        }
    }
}

// Delete paper function
function deletePaper(id) {
    if (confirm('Are you sure you want to delete this paper? This action cannot be undone.')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = window.location.pathname;
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
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
</script>

<?php include '../includes/footer.php'; ?>
