<?php
require_once '../includes/db.php';
require_login();

$page_title = "Reports - SLPA System";
$additional_css = ['../assets/css/reports.css', '../assets/css/forms.css', '../assets/css/components.css'];
$additional_js = ['../assets/js/reports.js?v=' . time()];

// Get filter parameters
$report_type = $_GET['type'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';

include '../includes/header.php';
?>

<div class="reports-page-wrapper">
    <div class="reports-hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1 class="hero-title">Reports & Analytics</h1>
            <p class="hero-subtitle">Generate comprehensive reports with professional export options</p>
            <div class="hero-stats">
                <div class="stat-item">
                    <i class="fas fa-database"></i>
                    <span>Real-time Data</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-file-export"></i>
                    <span>Multiple Formats</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Detailed Analysis</span>
                </div>
            </div>
        </div>
    </div>

<div class="page-container reports-container">

    <!-- Filter Section -->
    <div class="content-card filter-section">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Report Filters</h3>
        </div>
        <div class="card-body">
            <form id="reportFilterForm">
                <div class="filter-grid">
                    <!-- Report Type -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-file-alt"></i> Report Type
                        </label>
                        <select id="reportType" class="form-control">
                            <option value="all">All Reports (Combined)</option>
                            
                            <optgroup label="━━━ Papers Reports ━━━">
                                <option value="papers">📊 Papers Management (All)</option>
                                <option value="papers_receiving">📥 Papers Receiving</option>
                                <option value="papers_issuing">📤 Papers Issuing</option>
                                <option value="papers_return">🔙 Papers Returns</option>
                            </optgroup>
                            
                            <optgroup label="━━━ Toner Reports ━━━">
                                <option value="toner">📊 Toner Management (All)</option>
                                <option value="toner_receiving">📥 Toner Receiving</option>
                                <option value="toner_issuing">📤 Toner Issuing</option>
                                <option value="toner_return">🔙 Toner Returns</option>
                            </optgroup>
                            
                            <optgroup label="━━━ Ribbons Reports ━━━">
                                <option value="ribbons">📊 Ribbons Management (All)</option>
                                <option value="ribbons_receiving">📥 Ribbons Receiving</option>
                                <option value="ribbons_issuing">📤 Ribbons Issuing</option>
                                <option value="ribbons_return">🔙 Ribbons Returns</option>
                            </optgroup>
                            
                            <optgroup label="━━━ Time Period Reports ━━━">
                                <option value="weekly_report">📅 Weekly Report</option>
                                <option value="monthly_report">📆 Monthly Report</option>
                                <option value="yearly_report">📊 Yearly Report</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Date Filter Type -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-calendar"></i> Filter By
                        </label>
                        <select id="dateFilterType" class="form-control" onchange="toggleDateFilters()">
                            <option value="all">All Time</option>
                            <option value="daterange">Date Range</option>
                            <option value="year">Year</option>
                            <option value="month">Month & Year</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="filter-group" id="dateRangeGroup" style="display: none;">
                        <label class="filter-label">
                            <i class="fas fa-calendar-alt"></i> Start Date
                        </label>
                        <input type="date" id="startDate" class="form-control">
                    </div>

                    <div class="filter-group" id="endDateGroup" style="display: none;">
                        <label class="filter-label">
                            <i class="fas fa-calendar-check"></i> End Date
                        </label>
                        <input type="date" id="endDate" class="form-control">
                    </div>

                    <!-- Year Filter -->
                    <div class="filter-group" id="yearGroup" style="display: none;">
                        <label class="filter-label">
                            <i class="fas fa-calendar-day"></i> Year
                        </label>
                        <select id="yearFilter" class="form-control">
                            <option value="">Select Year</option>
                            <?php for($y = 2020; $y <= 2030; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($y == date('Y')) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Month Filter -->
                    <div class="filter-group" id="monthGroup" style="display: none;">
                        <label class="filter-label">
                            <i class="fas fa-calendar-week"></i> Month
                        </label>
                        <select id="monthFilter" class="form-control">
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

                    <!-- Supplier Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-building"></i> Supplier Name
                        </label>
                        <input type="text" id="supplierSearch" class="form-control" 
                               placeholder="Search by supplier name..." 
                               list="supplierList" 
                               autocomplete="off">
                        <datalist id="supplierList"></datalist>
                    </div>

                    <!-- Item/Product Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-box"></i> Item/Product Name
                        </label>
                        <input type="text" id="itemSearch" class="form-control" 
                               placeholder="Search by item name..." 
                               list="itemList" 
                               autocomplete="off">
                        <datalist id="itemList"></datalist>
                    </div>

                    <!-- IS Code Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-barcode"></i> IS Code <span style="color: #10b981; font-size: 11px; font-weight: 600;">⚡ Auto-Search</span>
                        </label>
                        <input type="text" id="isCodeSearch" class="form-control" 
                               placeholder="Enter IS code to auto-generate report..." 
                               autocomplete="off">
                        <small style="color: #6c757d; font-size: 11px; margin-top: 4px; display: block;">
                            <i class="fas fa-info-circle"></i> Report will generate automatically when you type IS Code
                        </small>
                    </div>

                    <!-- Search Button -->
                    <div class="filter-group">
                        <label class="filter-label">&nbsp;</label>
                        <button type="button" class="btn btn-success" onclick="generateReport()" style="width: 100%; height: 42px;">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                        <i class="fas fa-redo"></i> Reset All
                    </button>
                    <button type="button" class="btn btn-primary" onclick="generateReport()">
                        <i class="fas fa-chart-line"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Actions -->
    <div class="content-card" id="reportActionsCard" style="display: none;">
        <div class="card-header">
            <h3><i class="fas fa-file-export"></i> Export & Share Options</h3>
        </div>
        <div class="card-body">
            <div class="export-buttons">
                <button class="export-btn export-pdf" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i>
                    <span>Export as PDF</span>
                    <small>High-quality document</small>
                </button>
                <button class="export-btn export-print" onclick="printReport()">
                    <i class="fas fa-print"></i>
                    <span>Print Report</span>
                    <small>Direct to printer</small>
                </button>
                <button class="export-btn export-excel" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i>
                    <span>Export to Excel</span>
                    <small>Editable spreadsheet</small>
                </button>
                <button class="export-btn export-csv" onclick="exportToCSV()">
                    <i class="fas fa-file-csv"></i>
                    <span>Export to CSV</span>
                    <small>Compatible format</small>
                </button>
                <button class="export-btn export-email" onclick="emailReport()">
                    <i class="fas fa-envelope"></i>
                    <span>Email Report</span>
                    <small>Send via email</small>
                </button>
            </div>
        </div>
    </div>

    <!-- Report Preview -->
    <div class="content-card" id="reportPreviewCard" style="display: none;">
        <div class="card-header">
            <h3><i class="fas fa-file-alt"></i> Report Preview</h3>
            <span class="report-date" id="reportDate"></span>
        </div>
        <div class="card-body">
            <div id="reportContent" class="report-content">
                <!-- Report will be dynamically loaded here -->
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="loading-spinner" style="display: none;">
        <div class="spinner"></div>
        <p>Generating report...</p>
    </div>
</div>
</div>

<?php include '../includes/footer.php'; ?>
