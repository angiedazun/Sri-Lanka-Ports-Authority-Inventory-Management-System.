// Reports Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('Reports page initialized');
    loadSupplierList();
    loadItemList();
    
    // Add event listeners for auto-generation when IS Code is entered
    const isCodeInput = document.getElementById('isCodeSearch');
    const reportTypeSelect = document.getElementById('reportType');
    
    if (isCodeInput && reportTypeSelect) {
        // Auto-generate report when IS Code is entered and report type is selected
        isCodeInput.addEventListener('input', function() {
            if (this.value.trim() && reportTypeSelect.value) {
                debounceAutoGenerate();
            }
        });
        
        // Auto-generate report when report type is changed and IS Code exists
        reportTypeSelect.addEventListener('change', function() {
            if (this.value && isCodeInput.value.trim()) {
                generateReport();
            }
        });
    }
});

// Debounce function for auto-generation
let autoGenerateTimeout;
function debounceAutoGenerate() {
    clearTimeout(autoGenerateTimeout);
    autoGenerateTimeout = setTimeout(() => {
        generateReport();
    }, 800); // Wait 800ms after user stops typing
}

// Load supplier list
async function loadSupplierList() {
    try {
        const response = await fetch('../api/get_filter_options.php?type=suppliers');
        const data = await response.json();
        const datalist = document.getElementById('supplierList');
        data.forEach(supplier => {
            const option = document.createElement('option');
            option.value = supplier;
            datalist.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading suppliers:', error);
    }
}

// Load item list
async function loadItemList() {
    try {
        const response = await fetch('../api/get_filter_options.php?type=items');
        const data = await response.json();
        const datalist = document.getElementById('itemList');
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item;
            datalist.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading items:', error);
    }
}



// Toggle advanced options (removed - using defaults)

// Toggle date filter visibility
function toggleDateFilters() {
    const filterType = document.getElementById('dateFilterType').value;
    
    // Hide all filter groups first
    document.getElementById('dateRangeGroup').style.display = 'none';
    document.getElementById('endDateGroup').style.display = 'none';
    document.getElementById('yearGroup').style.display = 'none';
    document.getElementById('monthGroup').style.display = 'none';
    
    // Show relevant filters
    if (filterType === 'daterange') {
        document.getElementById('dateRangeGroup').style.display = 'block';
        document.getElementById('endDateGroup').style.display = 'block';
    } else if (filterType === 'year') {
        document.getElementById('yearGroup').style.display = 'block';
    } else if (filterType === 'month') {
        document.getElementById('monthGroup').style.display = 'block';
        document.getElementById('yearGroup').style.display = 'block';
    }
}

// Reset filters
function resetFilters() {
    document.getElementById('reportFilterForm').reset();
    document.getElementById('dateFilterType').value = 'all';
    toggleDateFilters();
    document.getElementById('reportActionsCard').style.display = 'none';
    document.getElementById('reportPreviewCard').style.display = 'none';
}

// Generate report
async function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const filterType = document.getElementById('dateFilterType').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;
    const supplier = document.getElementById('supplierSearch').value;
    const item = document.getElementById('itemSearch').value;
    const isCode = document.getElementById('isCodeSearch').value;
    const groupBy = '';
    const sortBy = 'date_desc';
    const includeCharts = false;
    const detailLevel = 'full';
    
    // Show loading
    document.getElementById('loadingSpinner').style.display = 'flex';
    
    // Build filter params
    const params = new URLSearchParams({
        report_type: reportType,
        filter_type: filterType,
        start_date: startDate,
        end_date: endDate,
        year: year,
        month: month,
        supplier: supplier,
        item: item,
        is_code: isCode,
        group_by: groupBy,
        sort_by: sortBy,
        include_charts: includeCharts,
        detail_level: detailLevel
    });
    
    try {
        const response = await fetch(`../api/generate_report.php?${params.toString()}`);
        const data = await response.json();
        
        if (data.success) {
            displayReport(data);
            document.getElementById('reportActionsCard').style.display = 'block';
            document.getElementById('reportPreviewCard').style.display = 'block';
        } else {
            alert('Error generating report: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to generate report. Please try again.');
    } finally {
        document.getElementById('loadingSpinner').style.display = 'none';
    }
}

// Display report
function displayReport(data) {
    const reportContent = document.getElementById('reportContent');
    const reportDate = document.getElementById('reportDate');
    const isCodeSearch = document.getElementById('isCodeSearch').value.trim();
    
    // Set report date
    reportDate.textContent = `Generated on: ${new Date().toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })}`;
    
    // Check if IS Code is filled - show card view, else show table view
    if (isCodeSearch && data.records && data.records.length > 0) {
        // Card-style view for specific IS Code
        displayCardView(data, reportContent);
    } else {
        // Table-style view for general reports
        displayTableView(data, reportContent);
    }
}

// Display Card View (when IS Code is filled)
function displayCardView(data, reportContent) {
    let html = `
        <div class="report-header">
            <div class="report-logo">
                <i class="fas fa-anchor" style="font-size: 80px; color: #2c3e50;"></i>
            </div>
            <h1 class="report-title">SRI LANKA PORTS AUTHORITY</h1>
            <p class="report-subtitle">${data.report_title}</p>
        </div>
    `;
    
    // Summary cards at top
    if (data.summary) {
        html += '<div class="report-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">';
        
        if (data.summary.total_quantity) {
            html += `
                <div class="summary-card blue" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 40px; font-weight: 700;">${data.summary.total_quantity.toLocaleString()}</div>
                    <div style="font-size: 14px; opacity: 0.9; margin-top: 8px;">Total Quantity</div>
                </div>
            `;
        }
        
        if (data.summary.total_value) {
            html += `
                <div class="summary-card green" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 32px; font-weight: 700;">Rs. ${data.summary.total_value.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                    <div style="font-size: 14px; opacity: 0.9; margin-top: 8px;">Total Value</div>
                </div>
            `;
        }
        
        if (data.summary.receiving_count || data.summary.issuing_count || data.summary.return_count) {
            html += `
                <div class="summary-card orange" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 40px; font-weight: 700;">${data.summary.receiving_count || data.summary.issuing_count || data.summary.return_count || 0}</div>
                    <div style="font-size: 14px; opacity: 0.9; margin-top: 8px;">Total Records</div>
                </div>
            `;
        }
        
        html += '</div>';
    }
    
    // Display each record as information cards
    data.records.forEach((record, index) => {
        const recordValues = Object.values(record);
        
        html += `
            <div class="info-boxes-wrapper" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="info-box primary-info" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);">
                    <div class="info-box-header" style="margin-bottom: 20px;">
                        <h3 style="font-size: 20px; display: flex; align-items: center; gap: 10px; margin: 0;">
                            <i class="fas fa-info-circle"></i> Information
                        </h3>
                    </div>
                    <div class="info-box-content">
                        <div class="info-row" style="margin-bottom: 15px;">
                            <span style="font-size: 13px; opacity: 0.8; display: block; margin-bottom: 5px;">IS CODE:</span>
                            <span style="background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 700; display: inline-block;">${recordValues[2] || 'N/A'}</span>
                        </div>
                        <div class="info-row" style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.2);">
                            <span style="font-size: 13px; opacity: 0.8;">MODEL:</span>
                            <span style="font-weight: 600; float: right;">${recordValues[1] || 'N/A'}</span>
                        </div>
                        <div class="info-row" style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.2);">
                            <span style="font-size: 13px; opacity: 0.8;">LOT:</span>
                            <span style="font-weight: 600; float: right;">${recordValues[3] || 'N/A'}</span>
                        </div>
                        <div class="info-row">
                            <span style="font-size: 13px; opacity: 0.8;">SUPPLIER:</span>
                            <span style="font-weight: 600; float: right;">${recordValues[4] || recordValues[5] || 'N/A'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="info-box secondary-info" style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(236, 72, 153, 0.3);">
                    <div class="info-box-header" style="margin-bottom: 20px;">
                        <h3 style="font-size: 20px; display: flex; align-items: center; gap: 10px; margin: 0;">
                            <i class="fas fa-clipboard-list"></i> IS Information
                        </h3>
                    </div>
                    <div class="info-box-content">
                        <div class="info-row" style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.2);">
                            <span style="font-size: 13px; opacity: 0.8;">DIVISION:</span>
                            <span style="font-weight: 600; float: right;">${recordValues[7] || recordValues[8] || 'N/A'}</span>
                        </div>
                        <div class="info-row" style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.2);">
                            <span style="font-size: 13px; opacity: 0.8;">DATE ISSUING:</span>
                            <span style="font-weight: 600; float: right;">${recordValues[5] || recordValues[6] || 'N/A'}</span>
                        </div>
                        <div class="info-row" style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.2);">
                            <span style="font-size: 13px; opacity: 0.8;">RECEIVER NAME:</span>
                            <span style="font-weight: 600; float: right;">${recordValues[9] || recordValues[10] || 'N/A'}</span>
                        </div>
                        <div class="info-row" style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.2);">
                            <span style="font-size: 13px; opacity: 0.8;">RETURN DATE:</span>
                            <span style="font-weight: 600; float: right;">${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                        </div>
                        <div class="info-row">
                            <span style="font-size: 13px; opacity: 0.8;">QUANTITY:</span>
                            <span style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 18px; font-weight: 700; float: right; display: inline-block;">${recordValues[11] || recordValues[12] || 'N/A'}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add Receiving Details if available
        html += `
            <div class="info-box receiving-info" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(6, 182, 212, 0.3); margin-bottom: 30px;">
                <div class="info-box-header" style="margin-bottom: 20px;">
                    <h3 style="font-size: 20px; display: flex; align-items: center; gap: 10px; margin: 0;">
                        <i class="fas fa-truck-loading"></i> Receiving Details
                    </h3>
                </div>
                <div class="info-box-content" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="info-row">
                        <span style="font-size: 13px; opacity: 0.8; display: block; margin-bottom: 5px;">PR NO:</span>
                        <span style="font-weight: 600; font-size: 16px;">${recordValues[13] || recordValues[6] || 'Rs. 0.00'}</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    reportContent.innerHTML = html;
}

// Display Table View (when IS Code is empty)
function displayTableView(data, reportContent) {
    let html = `
        <div class="report-header">
            <div class="report-logo">
                <i class="fas fa-anchor" style="font-size: 80px; color: #2c3e50;"></i>
            </div>
            <h1 class="report-title">SRI LANKA PORTS AUTHORITY</h1>
            <p class="report-subtitle">${data.report_title}</p>
            <div class="report-info">
                <div class="report-info-item">
                    <span class="report-info-label">Report Type</span>
                    <span class="report-info-value">${data.report_type_label}</span>
                </div>
                <div class="report-info-item">
                    <span class="report-info-label">Date Range</span>
                    <span class="report-info-value">${data.date_range}</span>
                </div>
                <div class="report-info-item">
                    <span class="report-info-label">Total Records</span>
                    <span class="report-info-value">${data.total_records}</span>
                </div>
                <div class="report-info-item">
                    <span class="report-info-label">Generated By</span>
                    <span class="report-info-value">${data.generated_by}</span>
                </div>
            </div>
        </div>
    `;
    
    // Summary cards
    if (data.summary) {
        html += '<div class="report-summary">';
        
        if (data.summary.total_quantity) {
            html += `
                <div class="summary-card blue">
                    <div class="summary-label">
                        <i class="fas fa-boxes"></i> Total Quantity
                    </div>
                    <div class="summary-value">${data.summary.total_quantity.toLocaleString()}</div>
                </div>
            `;
        }
        
        if (data.summary.total_value) {
            html += `
                <div class="summary-card green">
                    <div class="summary-label">
                        <i class="fas fa-dollar-sign"></i> Total Value
                    </div>
                    <div class="summary-value">Rs. ${data.summary.total_value.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
            `;
        }
        
        if (data.summary.receiving_count) {
            html += `
                <div class="summary-card orange">
                    <div class="summary-label">
                        <i class="fas fa-arrow-down"></i> Received
                    </div>
                    <div class="summary-value">${data.summary.receiving_count}</div>
                </div>
            `;
        }
        
        if (data.summary.issuing_count) {
            html += `
                <div class="summary-card blue">
                    <div class="summary-label">
                        <i class="fas fa-arrow-up"></i> Issued
                    </div>
                    <div class="summary-value">${data.summary.issuing_count}</div>
                </div>
            `;
        }
        
        if (data.summary.return_count) {
            html += `
                <div class="summary-card red">
                    <div class="summary-label">
                        <i class="fas fa-undo"></i> Returned
                    </div>
                    <div class="summary-value">${data.summary.return_count}</div>
                </div>
            `;
        }
        
        html += '</div>';
    }
    
    // Data table
    if (data.records && data.records.length > 0) {
        html += '<div class="report-table-wrapper">';
        html += '<table class="report-table">';
        
        // Table header with column names
        html += '<thead>';
        html += '<tr>';
        
        // Use column names from data
        if (data.columns && data.columns.length > 0) {
            data.columns.forEach(columnName => {
                html += `<th>${columnName}</th>`;
            });
        } else {
            // Fallback to keys from first record
            const columns = Object.keys(data.records[0]);
            columns.forEach(col => {
                html += `<th>${col.replace(/_/g, ' ')}</th>`;
            });
        }
        
        html += '</tr>';
        html += '</thead>';
        
        // Table body
        html += '<tbody>';
        
        data.records.forEach((record, index) => {
            html += '<tr>';
            
            // Handle both array and object records
            const values = Array.isArray(record) ? record : Object.values(record);
            
            values.forEach((value, colIndex) => {
                const displayValue = value || 'N/A';
                let cellClass = '';
                
                // Special styling for specific columns
                if (colIndex === 0) {
                    // Date column - blue
                    cellClass = 'date-column';
                } else if (colIndex === 4 || colIndex === 5) {
                    // JCT Qty and UCT Qty columns - green highlight
                    cellClass = 'quantity-column';
                } else if (colIndex === 6) {
                    // Unit Price column - green highlight
                    cellClass = 'value-column';
                }
                
                html += `<td class="${cellClass}">${displayValue}</td>`;
            });
            
            html += '</tr>';
        });
        
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
    } else {
        html += `
            <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
                <i class="fas fa-inbox" style="font-size: 60px; opacity: 0.3; margin-bottom: 20px;"></i>
                <h3 style="margin: 0 0 10px 0;">No Records Found</h3>
                <p>No data available for the selected filters.</p>
            </div>
        `;
    }
    
    reportContent.innerHTML = html;
}

// Export to PDF
function exportToPDF() {
    const reportContent = document.getElementById('reportContent');
    
    // Use browser's print to PDF feature
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>SLPA Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .report-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #3498db; }
                .report-title { font-size: 24px; font-weight: bold; color: #2c3e50; margin: 10px 0; }
                .report-subtitle { font-size: 16px; color: #7f8c8d; margin-bottom: 10px; }
                .report-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; padding: 15px; background: #f8f9fa; }
                .report-info-label { font-size: 11px; color: #7f8c8d; font-weight: bold; text-transform: uppercase; }
                .report-info-value { font-size: 14px; color: #2c3e50; font-weight: bold; }
                .report-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
                .summary-card { padding: 15px; border-radius: 8px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #3498db; }
                .summary-label { font-size: 12px; color: #7f8c8d; font-weight: bold; margin-bottom: 5px; }
                .summary-value { font-size: 24px; font-weight: bold; color: #2c3e50; }
                .report-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .report-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
                .report-table th { padding: 10px; text-align: left; font-weight: 600; font-size: 12px; }
                .report-table td { padding: 8px 10px; border-bottom: 1px solid #e0e0e0; font-size: 12px; }
                @media print {
                    body { margin: 0; padding: 10px; }
                    .report-table { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            ${reportContent.innerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    
    setTimeout(() => {
        printWindow.print();
    }, 500);
}

// Print report
function printReport() {
    window.print();
}

// Save filter preset
function saveFilterPreset() {
    const presetName = prompt('Enter a name for this filter preset:');
    if (presetName) {
        const preset = {
            reportType: document.getElementById('reportType').value,
            filterType: document.getElementById('dateFilterType').value,
            supplier: document.getElementById('supplierSearch').value,
            item: document.getElementById('itemSearch').value,
            isCode: document.getElementById('isCodeSearch').value
        };
        localStorage.setItem(`report_preset_${presetName}`, JSON.stringify(preset));
        alert(`Filter preset "${presetName}" saved successfully!`);
    }
}

// Export to Excel
function exportToExcel() {
    const params = buildFilterParams();
    params.append('format', 'excel');
    window.location.href = `../api/generate_report.php?${params.toString()}`;
}

// Export to CSV
function exportToCSV() {
    const params = buildFilterParams();
    params.append('format', 'csv');
    window.location.href = `../api/generate_report.php?${params.toString()}`;
}

// Email report
function emailReport() {
    const email = prompt('Enter email address to send report:');
    if (email) {
        const params = buildFilterParams();
        params.append('format', 'email');
        params.append('email', email);
        
        fetch(`../api/generate_report.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report sent successfully to ' + email);
                } else {
                    alert('Failed to send report: ' + data.message);
                }
            });
    }
}

// Build filter params helper
function buildFilterParams() {
    return new URLSearchParams({
        report_type: document.getElementById('reportType').value,
        filter_type: document.getElementById('dateFilterType').value,
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        year: document.getElementById('yearFilter').value,
        month: document.getElementById('monthFilter').value,
        supplier: document.getElementById('supplierSearch').value,
        item: document.getElementById('itemSearch').value,
        is_code: document.getElementById('isCodeSearch').value,
        group_by: '',
        sort_by: 'date_desc',
        include_charts: false,
        detail_level: 'full'
    });
}
