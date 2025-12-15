// Paper Returns Management JavaScript

// Global variables
let currentReturnData = null;

// Initialize page when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    const returnDateInput = document.getElementById('returnDate');
    if (returnDateInput) {
        returnDateInput.value = today;
    }
    
    // Initialize Paper code auto-suggest
    initPaperCodeAutoSuggest();
    
    // Initialize IS Code autocomplete with suggestions
    initISCodeAutocomplete();
    
    // Initialize LOT Number autocomplete with suggestions
    initLOTAutocomplete();
    
    // Initialize paper type auto-suggest
    initPaperTypeAutoSuggest();
    
    // Initialize code auto-fill
    initCodeAutoFill();
    
    // Initialize stock location auto-suggest
    initStockLocationAutoSuggest();
    
    // Populate supplier filter
    populateSupplierFilter();
    
    // Initialize table sorting
    initializeTableSorting();
    
    // Debug: Check if data is loaded
    console.log('Paper Returns page initialized');
    console.log('issuingRecords available:', typeof issuingRecords !== 'undefined');
    if (typeof issuingRecords !== 'undefined') {
        console.log('issuingRecords count:', issuingRecords.length);
        console.log('Sample record:', issuingRecords[0]);
    }
});

// Modal Management
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Focus on first input
        const firstInput = modal.querySelector('input:not([readonly]), select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset form if it exists
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            clearFormErrors(form);
        }
    }
}

// Paper selection handling
function updatePaperDetails() {
    const PaperSelect = document.getElementById('PaperSelect');
    const modelInput = document.getElementById('PaperModelDisplay');
    const codeInput = document.getElementById('codeDisplay');
    
    if (PaperSelect.selectedIndex > 0) {
        const selectedOption = PaperSelect.options[PaperSelect.selectedIndex];
        modelInput.value = selectedOption.getAttribute('data-model') || '';
        codeInput.value = selectedOption.getAttribute('data-code') || '';
    } else {
        modelInput.value = '';
        codeInput.value = '';
    }
}

function updateEditPaperDetails() {
    const PaperSelect = document.getElementById('editPaperSelect');
    const modelInput = document.getElementById('editPaperModelDisplay');
    const codeInput = document.getElementById('editCodeDisplay');
    
    if (PaperSelect.selectedIndex > 0) {
        const selectedOption = PaperSelect.options[PaperSelect.selectedIndex];
        modelInput.value = selectedOption.getAttribute('data-model') || '';
        codeInput.value = selectedOption.getAttribute('data-code') || '';
    } else {
        modelInput.value = '';
        codeInput.value = '';
    }
}

// Auto-fill paper details when code is entered
function initCodeAutoFill() {
    // For Return Paper Modal
    const codeInput = document.getElementById('codeDisplay');
    console.log('initCodeAutoFill - codeInput found:', codeInput);
    
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            console.log('Code input changed:', this.value);
            autoFillFromCode(this.value.trim());
        });
        console.log('Event listener attached to codeDisplay');
    } else {
        console.error('codeDisplay element not found!');
    }
    
    // Location field removed - no longer needed
    
    // For Edit Return Modal
    const editCodeInput = document.getElementById('editCodeDisplay');
    if (editCodeInput) {
        editCodeInput.addEventListener('input', function() {
            autoFillFromCodeEdit(this.value.trim());
        });
    }
}

function autoFillFromCode(code) {
    if (!code || typeof issuingRecords === 'undefined') return;
    
    console.log('Auto-fill triggered for code:', code);
    console.log('Available issuing records:', issuingRecords);
    
    // Location filtering removed - use all records
    let filteredRecords = issuingRecords;
    
    // Find issuing record by code (case-insensitive, exact match first)
    let record = filteredRecords.find(r => {
        const recordCode = (r.code || '').toString().trim().toLowerCase();
        return recordCode === code.toLowerCase();
    });
    
    // If no exact match, try partial match
    if (!record) {
        record = filteredRecords.find(r => {
            const recordCode = (r.code || '').toString().trim().toLowerCase();
            return recordCode.includes(code.toLowerCase());
        });
    }
    
    if (record) {
        console.log('✓ Record found:', record);
        
        // Auto-fill all paper details from issuing record
        const paperIdInput = document.getElementById('paperIdInput');
        const paperTypeInput = document.getElementById('paperTypeInput');
        const paperTypeHidden = document.getElementById('paperTypeHidden');
        const paperSizeDisplay = document.getElementById('paperSizeDisplay');
        const gsmDisplay = document.getElementById('gsmDisplay');
        const sheetsBundleDisplay = document.getElementById('sheetsBundleDisplay');
        const lotSelect = document.getElementById('lotSelect');
        const supplierInput = document.querySelector('input[name="supplier_name"]');
        const receivingDateInput = document.querySelector('input[name="receiving_date"]');
        const tenderFileInput = document.querySelector('input[name="tender_file_no"]');
        const invoiceInput = document.getElementById('invoiceInput') || document.querySelector('input[name="invoice"]');
        const quantityInput = document.querySelector('input[name="quantity"]');
        
        // Fill basic paper details
        if (paperIdInput) paperIdInput.value = record.paper_id || '';
        if (paperTypeInput) paperTypeInput.value = record.paper_type || '';
        if (paperTypeHidden) paperTypeHidden.value = record.paper_type || '';
        if (paperSizeDisplay) paperSizeDisplay.value = record.paper_size || '';
        if (gsmDisplay) gsmDisplay.value = record.gsm || '';
        if (sheetsBundleDisplay) sheetsBundleDisplay.value = record.sheets_per_bundle || 500;
        
        // Fill LOT
        if (lotSelect && record.lot) {
            for (let i = 0; i < lotSelect.options.length; i++) {
                if (lotSelect.options[i].value === record.lot) {
                    lotSelect.selectedIndex = i;
                    break;
                }
            }
        }
        
        // Fill supplier name
        if (supplierInput && record.supplier_name) {
            supplierInput.value = record.supplier_name;
        }
        
        // Fill receiving date
        if (receivingDateInput && record.receiving_date) {
            receivingDateInput.value = record.receiving_date;
        }
        
        // Fill tender file no
        if (tenderFileInput && record.tender_file_no) {
            tenderFileInput.value = record.tender_file_no;
        }
        
        // Fill invoice
        if (invoiceInput && record.invoice) {
            invoiceInput.value = record.invoice;
            invoiceInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                invoiceInput.style.backgroundColor = '';
            }, 2000);
        }
        
        // Fill quantity (from issuing record as default)
        if (quantityInput && record.quantity) {
            quantityInput.value = record.quantity;
        }
        
        console.log('✓ All fields auto-filled:', {
            paperType: record.paper_type,
            lot: record.lot,
            supplier: record.supplier_name,
            receivingDate: record.receiving_date,
            tenderFile: record.tender_file_no,
            invoice: record.invoice,
            quantity: record.quantity
        });
        
        // Show success feedback
        const codeInput = document.getElementById('codeDisplay');
        if (codeInput) {
            codeInput.style.borderColor = '#28a745';
            codeInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                codeInput.style.borderColor = '';
                codeInput.style.backgroundColor = '';
            }, 1500);
        }
        
        // Show notification
        showNotification('✓ Paper details loaded from issuing record', 'success');
    } else {
        // Show warning if no match found
        const codeInput = document.getElementById('codeDisplay');
        if (codeInput && code.length > 2) {
            codeInput.style.borderColor = '#ffc107';
            setTimeout(() => {
                codeInput.style.borderColor = '';
            }, 1000);
            
            // Show warning message
            const locationMsg = selectedLocation ? ` in ${selectedLocation}` : '';
            showNotification(`⚠ No issuing record found for code "${code}"${locationMsg}`, 'warning');
        }
    }
}

// IS Code Autocomplete with Suggestions (like toner return)
function initISCodeAutocomplete() {
    const input = document.getElementById('codeInput');
    const suggestionsBox = document.getElementById('codeSuggestions');
    const codeHidden = document.getElementById('codeHidden');
    
    if (!input || !suggestionsBox) {
        console.log('IS Code autocomplete elements not found');
        return;
    }
    
    let timer;
    
    input.addEventListener('input', function() {
        const val = this.value.trim();
        clearTimeout(timer);
        
        if (val.length < 1) {
            suggestionsBox.style.display = 'none';
            return;
        }
        
        timer = setTimeout(() => {
            // Check if issuingRecords is available
            if (typeof issuingRecords === 'undefined' || !issuingRecords) {
                console.log('No issuing records available');
                suggestionsBox.style.display = 'none';
                return;
            }
            
            // Filter issuing records by code
            const matches = issuingRecords.filter(record => {
                const code = (record.code || '').toString().toLowerCase();
                return code.includes(val.toLowerCase());
            });
            
            if (!matches.length) {
                suggestionsBox.style.display = 'none';
                return;
            }
            
            // Display suggestions with enhanced design
            suggestionsBox.innerHTML = matches.slice(0, 10).map(record => `
                <div class="code-suggestion-item" 
                     data-code="${record.code || ''}"
                     data-paper-id="${record.paper_id || ''}"
                     data-paper-type="${record.paper_type || ''}"
                     data-lot="${record.lot || ''}"
                     data-stock="${record.stock || ''}"
                     data-division="${record.division || ''}"
                     data-section="${record.section || ''}"
                     data-issue-date="${record.issue_date || ''}"
                     data-supplier="${record.supplier_name || ''}"
                     data-receiving-date="${record.receiving_date || ''}"
                     data-tender-file="${record.tender_file_no || ''}"
                     data-invoice="${record.invoice || ''}">
                    <div class="code-number">
                        <i class="fas fa-barcode"></i> <strong>${record.code || 'N/A'}</strong>
                    </div>
                    <div class="code-meta">
                        <span class="meta-item">${record.paper_type || 'N/A'}</span>
                        <span class="meta-badge stock-${(record.stock || 'good').toLowerCase()}">
                            <i class="fas fa-box"></i> ${record.stock || 'N/A'}
                        </span>
                        <span class="meta-badge lot-info">
                            <i class="fas fa-layer-group"></i> ${record.lot || 'N/A'}
                        </span>
                    </div>
                </div>
            `).join('');
            suggestionsBox.style.display = 'block';
        }, 250);
    });
    
    // Handle suggestion click
    suggestionsBox.addEventListener('click', function(e) {
        const item = e.target.closest('.code-suggestion-item');
        if (!item) return;
        
        const code = item.dataset.code;
        const paperId = item.dataset.paperId;
        const paperType = item.dataset.paperType;
        const lot = item.dataset.lot;
        const stock = item.dataset.stock;
        const division = item.dataset.division;
        const section = item.dataset.section;
        const issueDate = item.dataset.issueDate;
        const supplier = item.dataset.supplier;
        const receivingDate = item.dataset.receivingDate;
        const tenderFile = item.dataset.tenderFile;
        const invoice = item.dataset.invoice;
        
        // Fill the form fields
        input.value = code;
        if (codeHidden) codeHidden.value = code;
        
        // Fill other fields
        const paperIdInput = document.getElementById('paperIdInput');
        const paperTypeDisplay = document.getElementById('paperTypeDisplay');
        const paperTypeHidden = document.getElementById('paperTypeHidden');
        const lotInput = document.getElementById('lotInput');
        const lotHidden = document.getElementById('lotHidden');
        const stockInput = document.getElementById('stockInput');
        const divisionInput = document.getElementById('divisionInput');
        const sectionInput = document.getElementById('sectionInput');
        const issueDateInput = document.getElementById('issueDateInput');
        const locationInput = document.getElementById('locationInput');
        const supplierInput = document.querySelector('input[name="supplier_name"]');
        const receivingDateInput = document.querySelector('input[name="receiving_date"]');
        const tenderFileInput = document.querySelector('input[name="tender_file_no"]');
        const invoiceInput = document.querySelector('input[name="invoice"]');
        
        if (paperIdInput) paperIdInput.value = paperId;
        if (paperTypeDisplay) paperTypeDisplay.value = paperType;
        if (paperTypeHidden) paperTypeHidden.value = paperType;
        if (lotInput) lotInput.value = lot;
        if (lotHidden) lotHidden.value = lot;
        if (stockInput) stockInput.value = stock;
        if (divisionInput) divisionInput.value = division;
        if (sectionInput) sectionInput.value = section;
        if (issueDateInput) issueDateInput.value = issueDate;
        if (locationInput) locationInput.value = stock; // Location is the stock location
        if (supplierInput) supplierInput.value = supplier;
        if (receivingDateInput) receivingDateInput.value = receivingDate;
        if (tenderFileInput) tenderFileInput.value = tenderFile;
        if (invoiceInput) invoiceInput.value = invoice;
        
        suggestionsBox.style.display = 'none';
        showNotification('✓ Paper details loaded from IS code', 'success');
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
}

// LOT Number Autocomplete with Suggestions
function initLOTAutocomplete() {
    const input = document.getElementById('lotInput');
    const suggestionsBox = document.getElementById('lotSuggestions');
    const lotHidden = document.getElementById('lotHidden');
    
    if (!input || !suggestionsBox) {
        console.log('LOT autocomplete elements not found');
        return;
    }
    
    let timer;
    
    input.addEventListener('input', function() {
        const val = this.value.trim();
        clearTimeout(timer);
        
        if (val.length < 1) {
            suggestionsBox.style.display = 'none';
            return;
        }
        
        timer = setTimeout(() => {
            // Check if issuingRecords is available
            if (typeof issuingRecords === 'undefined' || !issuingRecords) {
                console.log('No issuing records available');
                suggestionsBox.style.display = 'none';
                return;
            }
            
            // Filter issuing records by LOT
            const matches = issuingRecords.filter(record => {
                const lot = (record.lot || '').toString().toLowerCase();
                return lot.includes(val.toLowerCase());
            });
            
            if (!matches.length) {
                suggestionsBox.style.display = 'none';
                return;
            }
            
            // Remove duplicates by LOT number
            const uniqueLots = [];
            const seenLots = new Set();
            matches.forEach(record => {
                const lot = record.lot || '';
                if (lot && !seenLots.has(lot)) {
                    seenLots.add(lot);
                    uniqueLots.push(record);
                }
            });
            
            // Display suggestions with enhanced design
            suggestionsBox.innerHTML = uniqueLots.slice(0, 10).map(record => `
                <div class="code-suggestion-item" 
                     data-code="${record.code || ''}"
                     data-paper-id="${record.paper_id || ''}"
                     data-paper-type="${record.paper_type || ''}"
                     data-lot="${record.lot || ''}"
                     data-stock="${record.stock || ''}"
                     data-supplier="${record.supplier_name || ''}"
                     data-receiving-date="${record.receiving_date || ''}"
                     data-tender-file="${record.tender_file_no || ''}"
                     data-invoice="${record.invoice || ''}">
                    <div class="code-number">
                        <i class="fas fa-layer-group"></i> <strong>${record.lot || 'N/A'}</strong>
                    </div>
                    <div class="code-meta">
                        <span class="meta-item">${record.paper_type || 'N/A'}</span>
                        <span class="meta-badge stock-${(record.stock || 'good').toLowerCase()}">
                            <i class="fas fa-box"></i> ${record.stock || 'N/A'}
                        </span>
                        <span class="meta-badge lot-info">
                            <i class="fas fa-barcode"></i> ${record.code || 'N/A'}
                        </span>
                    </div>
                </div>
            `).join('');
            suggestionsBox.style.display = 'block';
        }, 250);
    });
    
    // Handle suggestion click
    suggestionsBox.addEventListener('click', function(e) {
        const item = e.target.closest('.code-suggestion-item');
        if (!item) return;
        
        const lot = item.dataset.lot;
        const code = item.dataset.code;
        const paperId = item.dataset.paperId;
        const paperType = item.dataset.paperType;
        const stock = item.dataset.stock;
        const supplier = item.dataset.supplier;
        const receivingDate = item.dataset.receivingDate;
        const tenderFile = item.dataset.tenderFile;
        const invoice = item.dataset.invoice;
        
        // Fill the LOT field
        input.value = lot;
        if (lotHidden) lotHidden.value = lot;
        
        // Fill other fields
        const codeInput = document.getElementById('codeInput');
        const codeHidden = document.getElementById('codeHidden');
        const paperIdInput = document.getElementById('paperIdInput');
        const paperTypeDisplay = document.getElementById('paperTypeDisplay');
        const paperTypeHidden = document.getElementById('paperTypeHidden');
        const stockInput = document.getElementById('stockInput');
        const supplierInput = document.querySelector('input[name="supplier_name"]');
        const receivingDateInput = document.querySelector('input[name="receiving_date"]');
        const tenderFileInput = document.querySelector('input[name="tender_file_no"]');
        const invoiceInput = document.querySelector('input[name="invoice"]');
        
        if (codeInput) codeInput.value = code;
        if (codeHidden) codeHidden.value = code;
        if (paperIdInput) paperIdInput.value = paperId;
        if (paperTypeDisplay) paperTypeDisplay.value = paperType;
        if (paperTypeHidden) paperTypeHidden.value = paperType;
        if (stockInput) stockInput.value = stock;
        if (supplierInput) supplierInput.value = supplier;
        if (receivingDateInput) receivingDateInput.value = receivingDate;
        if (tenderFileInput) tenderFileInput.value = tenderFile;
        if (invoiceInput) invoiceInput.value = invoice;
        
        suggestionsBox.style.display = 'none';
        showNotification('✓ Paper details loaded from LOT number', 'success');
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
}

function autoFillFromCodeEdit(code) {
    if (!code || typeof issuingRecords === 'undefined') return;
    
    // Find issuing record by code (case-insensitive, exact match first)
    let record = issuingRecords.find(r => {
        const recordCode = (r.code || '').toString().trim().toLowerCase();
        return recordCode === code.toLowerCase();
    });
    
    // If no exact match, try partial match
    if (!record) {
        record = issuingRecords.find(r => {
            const recordCode = (r.code || '').toString().trim().toLowerCase();
            return recordCode.includes(code.toLowerCase());
        });
    }
    
    if (record) {
        // Auto-fill all paper details from issuing record
        const editPaperSelect = document.getElementById('editPaperSelect');
        const editPaperSizeDisplay = document.getElementById('editPaperSizeDisplay');
        const editGsmDisplay = document.getElementById('editGsmDisplay');
        const editSheetsBundle = document.getElementById('editSheetsBundle');
        const editLotSelect = document.getElementById('editLotSelect');
        const editSupplierInput = document.getElementById('editSupplierName');
        const editReceivingDateInput = document.getElementById('editReceivingDate');
        const editTenderFileInput = document.getElementById('editTenderFileNo');
        const editInvoiceInput = document.getElementById('editInvoice');
        const editQuantityInput = document.getElementById('editQuantity');
        
        // Fill basic paper details
        if (editPaperSelect) editPaperSelect.value = record.paper_id || '';
        if (editPaperSizeDisplay) editPaperSizeDisplay.value = record.paper_size || '';
        if (editGsmDisplay) editGsmDisplay.value = record.gsm || '';
        if (editSheetsBundle) editSheetsBundle.value = record.sheets_per_bundle || 500;
        
        // Fill LOT
        if (editLotSelect && record.lot) {
            for (let i = 0; i < editLotSelect.options.length; i++) {
                if (editLotSelect.options[i].value === record.lot) {
                    editLotSelect.selectedIndex = i;
                    break;
                }
            }
        }
        
        // Fill supplier name
        if (editSupplierInput && record.supplier_name) {
            editSupplierInput.value = record.supplier_name;
        }
        
        // Fill receiving date
        if (editReceivingDateInput && record.receiving_date) {
            editReceivingDateInput.value = record.receiving_date;
        }
        
        // Fill tender file no
        if (editTenderFileInput && record.tender_file_no) {
            editTenderFileInput.value = record.tender_file_no;
        }
        
        // Fill invoice
        if (editInvoiceInput && record.invoice) {
            editInvoiceInput.value = record.invoice;
            editInvoiceInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                editInvoiceInput.style.backgroundColor = '';
            }, 2000);
        }
        
        // Fill quantity
        if (editQuantityInput && record.quantity) {
            editQuantityInput.value = record.quantity;
        }
        
        // Show success feedback
        const editCodeInput = document.getElementById('editCodeDisplay');
        if (editCodeInput) {
            editCodeInput.style.borderColor = '#28a745';
            editCodeInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                editCodeInput.style.borderColor = '';
                editCodeInput.style.backgroundColor = '';
            }, 1500);
        }
        
        // Show notification
        showNotification('✓ All fields auto-filled from issuing record', 'success');
    } else {
        // Show warning if no match found
        const editCodeInput = document.getElementById('editCodeDisplay');
        if (editCodeInput && code.length > 2) {
            editCodeInput.style.borderColor = '#ffc107';
            setTimeout(() => {
                editCodeInput.style.borderColor = '';
            }, 1000);
        }
    }
}

// Show notification
function showNotification(message, type) {
    // Create notification element if it doesn't exist
    let notification = document.getElementById('autoFillNotification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'autoFillNotification';
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 4px; z-index: 10000; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: all 0.3s;';
        document.body.appendChild(notification);
    }
    
    // Set color based on type
    if (type === 'success') {
        notification.style.backgroundColor = '#28a745';
        notification.style.color = 'white';
    } else if (type === 'warning') {
        notification.style.backgroundColor = '#ffc107';
        notification.style.color = '#000';
    }
    
    notification.textContent = message;
    notification.style.display = 'block';
    notification.style.opacity = '1';
    
    // Hide after 2 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 300);
    }, 2000);
}

// Table filtering and searching
function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const supplierFilter = document.getElementById('supplierFilter').value;
    const table = document.getElementById('returnsTable');
    const tbody = table.getElementsByTagName('tbody')[0];
    const rows = tbody.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let showRow = true;
        
        // Search filter
        if (searchTerm) {
            let rowText = '';
            for (let j = 0; j < cells.length - 1; j++) { // Exclude actions column
                rowText += cells[j].textContent.toLowerCase() + ' ';
            }
            if (!rowText.includes(searchTerm)) {
                showRow = false;
            }
        }
        
        // Supplier filter
        if (showRow && supplierFilter) {
            const supplierCell = cells[8]; // Supplier column (updated index after adding Code)
            if (supplierCell && !supplierCell.textContent.includes(supplierFilter)) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    }
    
    // Update table info
    updateTableInfo();
}

function populateSupplierFilter() {
    const supplierFilter = document.getElementById('supplierFilter');
    const suppliers = new Set();
    
    // Collect unique suppliers from table
    const table = document.getElementById('returnsTable');
    if (table) {
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const supplierCell = rows[i].getElementsByTagName('td')[8]; // Supplier column (updated index after adding Code)
            if (supplierCell) {
                const supplier = supplierCell.textContent.trim();
                if (supplier && supplier !== 'N/A') {
                    suppliers.add(supplier);
                }
            }
        }
    }
    
    // Add options to filter
    Array.from(suppliers).sort().forEach(supplier => {
        const option = document.createElement('option');
        option.value = supplier;
        option.textContent = supplier;
        supplierFilter.appendChild(option);
    });
}

function updateTableInfo() {
    const table = document.getElementById('returnsTable');
    if (!table) return;
    
    const tbody = table.getElementsByTagName('tbody')[0];
    const allRows = tbody.getElementsByTagName('tr');
    const visibleRows = Array.from(allRows).filter(row => row.style.display !== 'none');
    
    // You can add table info display here if needed
    console.log(`Showing ${visibleRows.length} of ${allRows.length} returns`);
}

// View return details
function viewReturn(returnId) {
    const returnData = findReturnById(returnId);
    if (!returnData) {
        alert('Return record not found!');
        return;
    }
    
    const content = document.getElementById('viewReturnContent');
    content.innerHTML = `
        <div class="view-details">
            <div class="detail-section">
                <h4><i class="fas fa-Paper"></i> Paper Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Return ID:</label>
                        <span>${returnData.return_id}</span>
                    </div>
                    <div class="detail-item">
                        <label>Paper Model:</label>
                        <span>${returnData.Paper_model || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <label>Code:</label>
                        <span>${returnData.code || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <label>Stock Location:</label>
                        <span class="stock-badge stock-${(returnData.stock || '').toLowerCase()}">${returnData.stock || 'N/A'}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-calendar-alt"></i> Return Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Return Date:</label>
                        <span>${formatDate(returnData.return_date)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Original Receiving Date:</label>
                        <span>${returnData.receiving_date ? formatDate(returnData.receiving_date) : 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <label>Returned By:</label>
                        <span>${returnData.returned_by}</span>
                    </div>
                    <div class="detail-item">
                        <label>Quantity:</label>
                        <span class="quantity-badge">${returnData.quantity}</span>
                    </div>
                    <div class="detail-item">
                        <label>Supplier:</label>
                        <span>${returnData.supplier_name || 'N/A'}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-info-circle"></i> Additional Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Tender File No:</label>
                        <span>${returnData.tender_file_no || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <label>Invoice:</label>
                        <span>${returnData.invoice || 'N/A'}</span>
                    </div>
                    <div class="detail-item detail-full">
                        <label>Return Reason:</label>
                        <span>${returnData.reason || 'N/A'}</span>
                    </div>
                    <div class="detail-item detail-full">
                        <label>Remarks:</label>
                        <span>${returnData.remarks || 'N/A'}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    openModal('viewReturnModal');
}

// Edit return record
function editReturn(returnId) {
    const returnData = findReturnById(returnId);
    if (!returnData) {
        alert('Return record not found!');
        return;
    }
    
    // Populate form fields
    document.getElementById('editReturnId').value = returnData.return_id;
    document.getElementById('editPaperSelect').value = returnData.paper_id;
    document.getElementById('editPaperType').value = returnData.paper_type || '';
    document.getElementById('editCodeDisplay').value = returnData.code || '';
    document.getElementById('editLotSelect').value = returnData.lot || '';
    document.getElementById('editSupplierName').value = returnData.supplier_name || '';
    document.getElementById('editReturnDate').value = returnData.return_date;
    document.getElementById('editReceivingDate').value = returnData.receiving_date || '';
    document.getElementById('editTenderFileNo').value = returnData.tender_file_no || '';
    document.getElementById('editInvoice').value = returnData.invoice || '';
    document.getElementById('editReturnBy').value = returnData.return_by || '';
    document.getElementById('editQuantity').value = returnData.quantity;
    document.getElementById('editReason').value = returnData.reason || '';
    document.getElementById('editRemarks').value = returnData.remarks || '';
    
    openModal('editReturnModal');
}

// Delete confirmation
function confirmDelete(returnId) {
    const returnData = findReturnById(returnId);
    if (!returnData) {
        alert('Return record not found!');
        return;
    }
    
    const confirmMessage = `Are you sure you want to delete this return record?\n\n` +
                          `Paper: ${returnData.Paper_model || 'N/A'}\n` +
                          `Return Date: ${formatDate(returnData.return_date)}\n` +
                          `Quantity: ${returnData.quantity}\n` +
                          `Returned By: ${returnData.returned_by}`;
    
    if (confirm(confirmMessage)) {
        window.location.href = `?delete=${returnId}`;
    }
}

// Utility functions
function findReturnById(returnId) {
    if (typeof returnsData !== 'undefined') {
        return returnsData.find(returnRecord => returnRecord.return_id == returnId);
    }
    return null;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId) || document.querySelector('form');
    if (!form) return true;
    
    let isValid = true;
    const errors = [];
    
    // Clear previous errors
    clearFormErrors(form);
    
    // Required field validation
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            errors.push(`${getFieldLabel(field)} is required`);
            showFieldError(field, 'This field is required');
        }
    });
    
    // Quantity validation
    const quantityField = form.querySelector('input[name="quantity"]');
    if (quantityField && quantityField.value) {
        const quantity = parseInt(quantityField.value);
        if (quantity <= 0) {
            isValid = false;
            errors.push('Quantity must be greater than 0');
            showFieldError(quantityField, 'Quantity must be greater than 0');
        }
    }
    
    // Date validation
    const returnDateField = form.querySelector('input[name="return_date"]');
    const receivingDateField = form.querySelector('input[name="receiving_date"]');
    
    // Only validate date relationship if both dates are provided
    if (returnDateField && receivingDateField && 
        returnDateField.value && receivingDateField.value && receivingDateField.value.trim() !== '') {
        const returnDate = new Date(returnDateField.value);
        const receivingDate = new Date(receivingDateField.value);
        
        if (returnDate < receivingDate) {
            isValid = false;
            errors.push('Return date cannot be earlier than receiving date');
            showFieldError(returnDateField, 'Return date cannot be earlier than receiving date');
        }
    }
    
    if (!isValid) {
        // Show summary of errors
        alert('Please fix the following errors:\n\n' + errors.join('\n'));
    }
    
    return isValid;
}

function getFieldLabel(field) {
    const label = field.closest('.form-col, .form-col-full')?.querySelector('label');
    return label ? label.textContent.replace('*', '').trim() : field.name;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    // Create error message element
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    // Insert after field
    field.parentNode.insertBefore(errorElement, field.nextSibling);
}

function clearFormErrors(form) {
    // Remove error classes
    const errorFields = form.querySelectorAll('.error');
    errorFields.forEach(field => field.classList.remove('error'));
    
    // Remove error messages
    const errorMessages = form.querySelectorAll('.field-error');
    errorMessages.forEach(message => message.remove());
}

// Table utilities
function printTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Paper Returns Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; font-weight: bold; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .print-date { margin-top: 20px; text-align: right; font-size: 0.9em; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Sri Lanka Ports Authority</h2>
                    <h3>Paper Returns Report</h3>
                </div>
                ${table.outerHTML}
                <div class="print-date">
                    Generated on: ${new Date().toLocaleDateString()}
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function refreshTable() {
    // Clear filters
    document.getElementById('searchInput').value = '';
    document.getElementById('supplierFilter').value = '';
    
    // Show all rows
    const table = document.getElementById('returnsTable');
    if (table) {
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = tbody.getElementsByTagName('tr');
        
        for (let i = 0; i < rows.length; i++) {
            rows[i].style.display = '';
        }
    }
    
    // Reload page to get fresh data
    window.location.reload();
}

function initializeTableSorting() {
    const table = document.getElementById('returnsTable');
    if (!table) return;
    
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        if (index < headers.length - 1) { // Exclude actions column
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => sortTable(index));
        }
    });
}

function sortTable(columnIndex) {
    const table = document.getElementById('returnsTable');
    if (!table) return;
    
    const tbody = table.getElementsByTagName('tbody')[0];
    const rows = Array.from(tbody.getElementsByTagName('tr'));
    
    // Determine sort order
    const header = table.getElementsByTagName('th')[columnIndex];
    const currentOrder = header.getAttribute('data-sort-order') || 'asc';
    const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
    
    // Clear all sort indicators
    table.querySelectorAll('th').forEach(th => {
        th.removeAttribute('data-sort-order');
        th.innerHTML = th.innerHTML.replace(/ ↑| ↓/g, '');
    });
    
    // Set new sort indicator
    header.setAttribute('data-sort-order', newOrder);
    header.innerHTML += newOrder === 'asc' ? ' ↑' : ' ↓';
    
    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.getElementsByTagName('td')[columnIndex].textContent.trim();
        const bValue = b.getElementsByTagName('td')[columnIndex].textContent.trim();
        
        // Handle different data types
        if (!isNaN(aValue) && !isNaN(bValue)) {
            // Numeric sort
            return newOrder === 'asc' ? aValue - bValue : bValue - aValue;
        } else if (Date.parse(aValue) && Date.parse(bValue)) {
            // Date sort
            const aDate = new Date(aValue);
            const bDate = new Date(bValue);
            return newOrder === 'asc' ? aDate - bDate : bDate - aDate;
        } else {
            // String sort
            return newOrder === 'asc' ? 
                aValue.localeCompare(bValue) : 
                bValue.localeCompare(aValue);
        }
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

// Event listeners for modal clicks
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        const modalId = e.target.id;
        closeModal(modalId);
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Close any open modal
        const openModal = document.querySelector('.modal[style*="display: block"]');
        if (openModal) {
            closeModal(openModal.id);
        }
    }
});

// Paper type auto-suggest
function initPaperTypeAutoSuggest() {
    const input = document.getElementById('paperTypeInput');
    const suggestionsBox = document.getElementById('paperTypeSuggestions');
    
    if (!input || !suggestionsBox || typeof papersData === 'undefined') {
        return;
    }
    
    input.addEventListener('input', function() {
        const val = input.value.trim().toLowerCase();
        
        if (val.length < 1) {
            suggestionsBox.style.display = 'none';
            return;
        }
        
        // Filter papers by paper_type
        const matches = papersData.filter(paper => 
            paper.paper_type && paper.paper_type.toLowerCase().includes(val)
        );
        
        if (!matches.length) {
            suggestionsBox.style.display = 'none';
            return;
        }
        
        // Show suggestions
        suggestionsBox.innerHTML = matches.map(paper =>
            `<div class='suggestion-item' 
                data-id='${paper.paper_id}' 
                data-type='${paper.paper_type}' 
                data-size='${paper.paper_size}' 
                data-gsm='${paper.gsm}'>
                ${paper.paper_type} - ${paper.paper_size} - ${paper.gsm} GSM
            </div>`
        ).join('');
        suggestionsBox.style.display = 'block';
    });
    
    // Handle suggestion click
    suggestionsBox.addEventListener('mousedown', function(e) {
        const item = e.target.closest('.suggestion-item');
        if (!item) return;
        
        // Fill in the form fields
        input.value = item.getAttribute('data-type');
        document.getElementById('paperIdInput').value = item.getAttribute('data-id');
        document.getElementById('paperTypeHidden').value = item.getAttribute('data-type');
        document.getElementById('paperSizeDisplay').value = item.getAttribute('data-size');
        document.getElementById('gsmDisplay').value = item.getAttribute('data-gsm');
        
        suggestionsBox.style.display = 'none';
    });
    
    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-container')) {
            suggestionsBox.style.display = 'none';
        }
    });
}

// Paper code auto-suggest and autofill
function initPaperCodeAutoSuggest() {
    const input = document.getElementById('PaperCodeInput');
    const suggestionsBox = document.getElementById('PaperCodeSuggestions');
    
    console.log('Initializing Paper code auto-suggest...', {input, suggestionsBox});
    
    if (!input || !suggestionsBox) {
        console.error('Missing elements for auto-suggest:', {input, suggestionsBox});
        return;
    }
    
    let timer;
    
    input.addEventListener('input', function() {
        const val = input.value.trim();
        console.log('Input changed:', val);
        clearTimeout(timer);
        
        if (val.length < 1) {
            suggestionsBox.style.display = 'none';
            return;
        }
        
        timer = setTimeout(() => {
            console.log('Fetching suggestions for:', val);
            fetch(`../api/get_Paper_suggestions.php?q=${encodeURIComponent(val)}`)
                .then(r => {
                    console.log('Response received:', r);
                    return r.json();
                })
                .then(data => {
                    console.log('Data received:', data);
                    if (!data.length) {
                        suggestionsBox.style.display = 'none';
                        return;
                    }
                    suggestionsBox.innerHTML = data.map(t =>
                        `<div class='suggestion-item' data-id='${t.id}' data-code='${t.code}' data-model='${t.model}' data-stock='${t.stock_location}'>${t.code} - ${t.model}</div>`
                    ).join('');
                    suggestionsBox.style.display = 'block';
                })
                .catch(err => {
                    console.error('Error fetching suggestions:', err);
                    suggestionsBox.style.display = 'none';
                });
        }, 250);
    });
    
    suggestionsBox.addEventListener('mousedown', function(e) {
        const item = e.target.closest('.suggestion-item');
        if (!item) return;
        
        console.log('Suggestion clicked:', item);
        
        input.value = item.getAttribute('data-code');
        document.getElementById('PaperIdInput').value = item.getAttribute('data-id');
        document.getElementById('PaperModelDisplay').value = item.getAttribute('data-model');
        document.getElementById('codeDisplay').value = item.getAttribute('data-code');
        document.getElementById('stockLocationInput').value = item.getAttribute('data-stock');
        suggestionsBox.style.display = 'none';
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-container')) {
            suggestionsBox.style.display = 'none';
        }
    });
}

// Stock location auto-suggest
function initStockLocationAutoSuggest() {
    const input = document.getElementById('stockLocationInput');
    const suggestionsBox = document.getElementById('stockLocationSuggestions');
    if (!input || !suggestionsBox) return;
    
    const locations = ['JCT Stock', 'UCT Stock'];
    
    input.addEventListener('input', function() {
        const val = input.value.trim().toLowerCase();
        
        if (!val) {
            suggestionsBox.style.display = 'none';
            return;
        }
        
        const filtered = locations.filter(l => l.toLowerCase().includes(val));
        
        if (!filtered.length) {
            suggestionsBox.style.display = 'none';
            return;
        }
        
        suggestionsBox.innerHTML = filtered.map(l => `<div class='suggestion-item'>${l}</div>`).join('');
        suggestionsBox.style.display = 'block';
    });
    
    suggestionsBox.addEventListener('mousedown', function(e) {
        const item = e.target.closest('.suggestion-item');
        if (!item) return;
        input.value = item.textContent;
        suggestionsBox.style.display = 'none';
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-container')) {
            suggestionsBox.style.display = 'none';
        }
    });
}

// Print Report Functions
function toggleDateFields() {
    const printType = document.getElementById('printType').value;
    
    // Hide all sections first
    document.getElementById('dailySection').style.display = 'none';
    document.getElementById('monthlySection').style.display = 'none';
    document.getElementById('yearlySection').style.display = 'none';
    document.getElementById('customSection').style.display = 'none';
    
    // Show relevant section based on selection
    switch(printType) {
        case 'daily':
            document.getElementById('dailySection').style.display = 'flex';
            // Set default to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dailyDate').value = today;
            break;
        case 'monthly':
            document.getElementById('monthlySection').style.display = 'flex';
            // Set default to current month/year
            const currentDate = new Date();
            document.getElementById('monthSelect').value = currentDate.getMonth() + 1;
            document.getElementById('monthlyYear').value = currentDate.getFullYear();
            break;
        case 'yearly':
            document.getElementById('yearlySection').style.display = 'flex';
            // Set default to current year
            document.getElementById('yearSelect').value = new Date().getFullYear();
            break;
        case 'custom':
            document.getElementById('customSection').style.display = 'flex';
            // Set default to current month range
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            document.getElementById('fromDate').value = firstDay.toISOString().split('T')[0];
            document.getElementById('toDate').value = lastDay.toISOString().split('T')[0];
            break;
    }
}

function generateReturnsPrintReport() {
    console.log('Generate report function called');
    
    const printType = document.getElementById('printType').value;
    const reportFormat = document.getElementById('reportFormat').value;
    const includeStatistics = document.getElementById('includeStatistics').checked;
    const includeDivision = document.getElementById('includeDivision').checked;
    const includePrinter = document.getElementById('includePrinter').checked;
    const includeRemarks = document.getElementById('includeRemarks').checked;
    
    console.log('Print Type:', printType);
    console.log('Report Format:', reportFormat);
    
    // Check if the elements exist
    if (!document.getElementById('printType')) {
        console.error('Print type element not found');
        alert('Error: Print form elements not found');
        return;
    }
    
    // Get data based on print type
    let filteredData = [];
    let reportTitle = 'Paper Returns Report';
    let dateRange = '';
    
    // Check if returnsData exists
    if (typeof returnsData !== 'undefined' && returnsData.length > 0) {
        filteredData = [...returnsData];
        console.log('Original data count:', filteredData.length);
    } else if (typeof returnsData !== 'undefined') {
        console.warn('Returns data is empty');
        alert('No return records found in the database.');
        return;
    } else {
        console.error('Returns data is not defined');
        alert('Error: Return data not loaded. Please refresh the page.');
        return;
    }
    
    // Filter data based on print type
    switch(printType) {
        case 'daily':
            const dailyDate = document.getElementById('dailyDate').value;
            if (!dailyDate) {
                alert('Please select a date for the daily report.');
                return;
            }
            filteredData = filteredData.filter(item => item.return_date === dailyDate);
            reportTitle = 'Daily Paper Returns Report';
            dateRange = new Date(dailyDate).toLocaleDateString();
            break;
            
        case 'monthly':
            const month = document.getElementById('monthSelect').value;
            const monthlyYear = document.getElementById('monthlyYear').value;
            if (!month || !monthlyYear) {
                alert('Please select both month and year for the monthly report.');
                return;
            }
            filteredData = filteredData.filter(item => {
                const itemDate = new Date(item.return_date);
                return itemDate.getMonth() + 1 === parseInt(month) && 
                       itemDate.getFullYear() === parseInt(monthlyYear);
            });
            reportTitle = 'Monthly Paper Returns Report';
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            dateRange = `${monthNames[month - 1]} ${monthlyYear}`;
            break;
            
        case 'yearly':
            const year = document.getElementById('yearSelect').value;
            if (!year) {
                alert('Please select a year for the yearly report.');
                return;
            }
            filteredData = filteredData.filter(item => {
                const itemDate = new Date(item.return_date);
                return itemDate.getFullYear() === parseInt(year);
            });
            reportTitle = 'Yearly Paper Returns Report';
            dateRange = year;
            break;
            
        case 'custom':
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            if (!fromDate || !toDate) {
                alert('Please select both from and to dates for the custom report.');
                return;
            }
            if (new Date(fromDate) > new Date(toDate)) {
                alert('From date cannot be later than to date.');
                return;
            }
            filteredData = filteredData.filter(item => {
                return item.return_date >= fromDate && item.return_date <= toDate;
            });
            reportTitle = 'Custom Paper Returns Report';
            dateRange = `${new Date(fromDate).toLocaleDateString()} - ${new Date(toDate).toLocaleDateString()}`;
            break;
            
        case 'all':
        default:
            reportTitle = 'Complete Paper Returns Report';
            dateRange = 'All Records';
            break;
    }
    
    console.log('Final filtered data count:', filteredData.length);
    
    // Generate print HTML based on format
    const printHTML = generateReturnsPrintHTML(filteredData, reportTitle, reportFormat, {
        includeStatistics,
        includeDivision,
        includePrinter,
        includeRemarks,
        dateRange
    });
    
    console.log('Print HTML generated, length:', printHTML.length);
    
    // Open print window
    const printWindow = window.open('', '_blank');
    if (printWindow) {
        printWindow.document.write(printHTML);
        printWindow.document.close();
        printWindow.print();
        
        // Close modal after successful print
        setTimeout(() => {
            closeModal('printModal');
        }, 500);
    } else {
        alert('Could not open print window. Please check if pop-ups are blocked.');
    }
}

function generateReturnsPrintHTML(data, title, format, options) {
    const { includeStatistics, includeDivision, includePrinter, includeRemarks, dateRange } = options;
    
    let html = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>${title}</title>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                margin: 0;
                padding: 20px;
                color: #333;
                line-height: 1.4;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #007bff;
            }
            .header h1 {
                color: #2d3748;
                margin: 0;
                font-size: 28px;
                font-weight: bold;
            }
            .header h2 {
                color: #4a5568;
                margin: 10px 0;
                font-size: 20px;
                font-weight: normal;
            }
            .stats-section {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
                border-left: 4px solid #007bff;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 15px;
            }
            .stat-item {
                text-align: center;
                padding: 15px;
                background: white;
                border-radius: 6px;
                border: 1px solid #e2e8f0;
            }
            .stat-value {
                font-size: 24px;
                font-weight: bold;
                color: #2d3748;
                margin-bottom: 5px;
            }
            .stat-label {
                font-size: 12px;
                color: #718096;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            th {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 12px 8px;
                text-align: left;
                font-weight: 600;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border: 1px solid #5a67d8;
            }
            td {
                padding: 10px 8px;
                border: 1px solid #e2e8f0;
                font-size: 12px;
                vertical-align: top;
            }
            tbody tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 2px solid #e2e8f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 12px;
                color: #718096;
            }
            .no-data {
                text-align: center;
                padding: 40px;
                color: #718096;
                font-style: italic;
            }
            .stock-badge {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .stock-jct { background: #d4edda; color: #155724; }
            .stock-uct { background: #d1ecf1; color: #0c5460; }
            @media print {
                body { margin: 0; }
                .stats-section { break-inside: avoid; }
                table { break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Sri Lanka Ports Authority</h1>
            <h2>${title}</h2>
            ${dateRange ? `<div style="color: #007bff; font-size: 16px; font-weight: 600; margin: 10px 0;">${dateRange}</div>` : ''}
            <div style="color: #718096; font-size: 14px; margin: 10px 0;">
                Generated on: ${new Date().toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })}
            </div>
        </div>
    `;
    
    // Statistics section
    if (includeStatistics && format !== 'statistics') {
        const stats = calculateReturnsStatistics(data);
        html += `
        <div class="stats-section">
            <h3 style="margin: 0 0 15px 0; color: #2d3748;">Summary Statistics</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">${stats.totalReturns}</div>
                    <div class="stat-label">Total Returns</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.totalQuantity}</div>
                    <div class="stat-label">Total Quantity</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.uniqueSuppliers}</div>
                    <div class="stat-label">Suppliers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.jctReturns}</div>
                    <div class="stat-label">JCT Returns</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.uctReturns}</div>
                    <div class="stat-label">UCT Returns</div>
                </div>
            </div>
        </div>
        `;
    }
    
    // Statistics only format
    if (format === 'statistics') {
        const stats = calculateReturnsStatistics(data);
        html += `
        <div class="stats-section">
            <h3 style="margin: 0 0 15px 0; color: #2d3748;">Complete Statistics Report</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">${stats.totalReturns}</div>
                    <div class="stat-label">Total Returns</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.totalQuantity}</div>
                    <div class="stat-label">Total Quantity</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.uniqueSuppliers}</div>
                    <div class="stat-label">Unique Suppliers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.jctReturns}</div>
                    <div class="stat-label">JCT Returns</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.uctReturns}</div>
                    <div class="stat-label">UCT Returns</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${stats.averageQuantity}</div>
                    <div class="stat-label">Average Quantity</div>
                </div>
            </div>
        </div>
        `;
    } else if (data.length === 0) {
        html += `
        <div class="no-data">
            <h3>No return records found for the selected criteria.</h3>
        </div>
        `;
    } else {
        // Table format
        html += `
        <table>
            <thead>
                <tr>
                    <th>Return Date</th>
                    <th>Paper Model</th>
                    <th>Code</th>
                    <th>Stock</th>`;
        
        if (includeDivision) {
            html += `<th>Division</th>`;
        }
        
        html += `
                    <th>Receiving Date</th>
                    <th>Location</th>
                    <th>Returned By</th>
                    <th>Quantity</th>
                    <th>Reason</th>`;
        
        if (includePrinter) {
            html += `<th>Printer Details</th>`;
        }
        
        if (includeRemarks) {
            html += '<th>Remarks</th>';
        }
        
        html += `
                </tr>
            </thead>
            <tbody>
        `;
        
        data.forEach(item => {
            html += `
            <tr>
                <td>${formatDate(item.return_date)}</td>
                <td>${item.Paper_model || 'N/A'}</td>
                <td>${item.code || 'N/A'}</td>
                <td><span class="stock-badge stock-${(item.stock || '').toLowerCase()}">${item.stock || 'N/A'}</span></td>`;
                
            if (includeDivision) {
                html += `<td>${item.division || 'N/A'}</td>`;
            }
            
            html += `
                <td>${item.receiving_date ? formatDate(item.receiving_date) : 'N/A'}</td>
                <td><span class="stock-badge stock-${item.location.toLowerCase()}">${item.location}</span></td>
                <td>${item.returned_by || 'N/A'}</td>
                <td style="text-align: center; font-weight: bold;">${item.quantity}</td>
                <td>${item.reason || 'N/A'}</td>`;
                
            if (includePrinter) {
                html += `<td>${item.printer_details || 'N/A'}</td>`;
            }
            
            if (includeRemarks) {
                html += `<td>${item.remarks || 'N/A'}</td>`;
            }
            
            html += '</tr>';
        });
        
        html += `
            </tbody>
        </table>
        `;
    }
    
    html += `
        <div class="footer">
            <div>Generated by SLPA Paper Management System</div>
            <div>Report Format: ${format.charAt(0).toUpperCase() + format.slice(1)}</div>
        </div>
    </body>
    </html>
    `;
    
    return html;
}

function calculateReturnsStatistics(data) {
    const stats = {
        totalReturns: data.length,
        totalQuantity: 0,
        uniqueSuppliers: 0,
        jctReturns: 0,
        uctReturns: 0,
        averageQuantity: 0
    };
    
    if (data.length === 0) return stats;
    
    const suppliers = new Set();
    
    data.forEach(item => {
        stats.totalQuantity += parseInt(item.quantity) || 0;
        
        if (item.supplier_name) {
            suppliers.add(item.supplier_name);
        }
        
        if (item.location === 'JCT') {
            stats.jctReturns++;
        } else if (item.location === 'UCT') {
            stats.uctReturns++;
        }
    });
    
    stats.uniqueSuppliers = suppliers.size;
    stats.averageQuantity = data.length > 0 ? Math.round(stats.totalQuantity / data.length * 10) / 10 : 0;
    
    return stats;
}

// Test function to verify print functionality
function testPrintFunction() {
    console.log('Testing print function...');
    console.log('Returns data available:', typeof returnsData !== 'undefined');
    if (typeof returnsData !== 'undefined') {
        console.log('Returns data count:', returnsData.length);
    }
    
    const printModal = document.getElementById('printModal');
    console.log('Print modal exists:', !!printModal);
    
    const printButton = document.querySelector('button[onclick="generateReturnsPrintReport()"]');
    console.log('Print button exists:', !!printButton);
    
    alert('Print function test completed. Check console for details.');
}

console.log('Paper Returns JavaScript loaded successfully');

// Add event listener to ensure DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, returns data available:', typeof returnsData !== 'undefined');
    if (typeof returnsData !== 'undefined') {
        console.log('Returns data length:', returnsData.length);
    }
    
    // Set up print type change handler
    const printTypeSelect = document.getElementById('printType');
    if (printTypeSelect) {
        printTypeSelect.addEventListener('change', toggleDateFields);
        console.log('Print type change handler added');
        
        // Initialize with default selection
        toggleDateFields();
    }
    
    // Set up generate report button
    const generateButton = document.querySelector('button[onclick="generateReturnsPrintReport()"]');
    if (generateButton) {
        generateButton.addEventListener('click', function(e) {
            e.preventDefault();
            generateReturnsPrintReport();
        });
        console.log('Generate report button handler added');
    }
});
