// Toner Returns Management JavaScript

// Global variables
let currentReturnData = null;
let issuedTonersData = window.issuedTonersData || [];

// Initialize page when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Toner Returns Page Initializing...');
    console.log('📦 Issued Toners Data:', issuedTonersData ? issuedTonersData.length : 0);
    
    // Diagnostic: Check if quantity field exists
    const quantityField = document.getElementById('quantityInput');
    console.log('🔍 Quantity field diagnostic on page load:');
    console.log('  - Field exists:', !!quantityField);
    if (quantityField) {
        console.log('  - Field type:', quantityField.type);
        console.log('  - Field name:', quantityField.name);
        console.log('  - Field min:', quantityField.min);
        console.log('  - Field required:', quantityField.required);
        console.log('  - Field placeholder:', quantityField.placeholder);
    }
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    const returnDateInput = document.getElementById('returnDate');
    if (returnDateInput) {
        returnDateInput.value = today;
    }
    
    // Initialize toner code auto-suggest
    initTonerCodeAutoSuggest();
    
    // Populate supplier filter
    populateSupplierFilter();
    
    // Initialize table sorting
    initializeTableSorting();
    
    console.log('✅ Toner Returns page initialized successfully');
});

// Modal Management
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Reinitialize autocomplete when modal opens
        if (modalId === 'returnTonerModal') {
            console.log('Return modal opened - reinitializing autocomplete');
            setTimeout(() => {
                initTonerCodeAutoSuggest();
            }, 100);
        }
        
        // Focus on first input
        const firstInput = modal.querySelector('input:not([readonly]), select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 150);
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

// Toner selection handling
function updateTonerDetails() {
    const tonerSelect = document.getElementById('tonerSelect');
    const modelInput = document.getElementById('tonerModelDisplay');
    const codeInput = document.getElementById('codeDisplay');
    
    if (tonerSelect.selectedIndex > 0) {
        const selectedOption = tonerSelect.options[tonerSelect.selectedIndex];
        modelInput.value = selectedOption.getAttribute('data-model') || '';
        codeInput.value = selectedOption.getAttribute('data-code') || '';
    } else {
        modelInput.value = '';
        codeInput.value = '';
    }
}

function updateEditTonerDetails() {
    const tonerSelect = document.getElementById('editTonerSelect');
    const modelInput = document.getElementById('editTonerModelDisplay');
    const codeInput = document.getElementById('editCodeDisplay');
    
    if (tonerSelect.selectedIndex > 0) {
        const selectedOption = tonerSelect.options[tonerSelect.selectedIndex];
        modelInput.value = selectedOption.getAttribute('data-model') || '';
        codeInput.value = selectedOption.getAttribute('data-code') || '';
    } else {
        modelInput.value = '';
        codeInput.value = '';
    }
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
            const supplierCell = cells[4]; // Supplier column
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
            const supplierCell = rows[i].getElementsByTagName('td')[4]; // Supplier column
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
                <h4><i class="fas fa-toner"></i> Toner Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Return ID:</label>
                        <span>${returnData.return_id}</span>
                    </div>
                    <div class="detail-item">
                        <label>Toner Model:</label>
                        <span>${returnData.toner_model || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <label>Code:</label>
                        <span>${returnData.code || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <label>Stock Type:</label>
                        <span class="stock-badge stock-${(returnData.stock || '').toLowerCase()}">${returnData.stock || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <label>LOT Number:</label>
                        <span class="badge" style="background: linear-gradient(135deg, rgb(102,126,234) 0%, rgb(118,75,162) 100%); color: white; padding: 5px 12px; border-radius: 20px;">${returnData.lot || 'N/A'}</span>
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
    
    console.log('Editing return:', returnData);
    
    // Populate form fields
    document.getElementById('editReturnId').value = returnData.return_id;
    document.getElementById('editTonerSelect').value = returnData.toner_id;
    document.getElementById('editTonerModelDisplay').value = returnData.toner_model || '';
    document.getElementById('editCodeDisplay').value = returnData.code || '';
    document.getElementById('editStock').value = returnData.stock || '';
    document.getElementById('editLotDisplay').value = returnData.lot || '';
    document.getElementById('editReturnDate').value = returnData.return_date;
    document.getElementById('editReceivingDate').value = returnData.receiving_date || '';
    document.getElementById('editReturnedBy').value = returnData.returned_by;
    document.getElementById('editQuantity').value = returnData.quantity;
    document.getElementById('editSupplierNameInfo').value = returnData.supplier_name || '';
    document.getElementById('editSupplierNameField').value = returnData.supplier_name || '';
    document.getElementById('editTenderFileNoInfo').value = returnData.tender_file_no || '';
    document.getElementById('editInvoiceNumber').value = returnData.invoice || '';
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
                          `Toner: ${returnData.toner_model || 'N/A'}\n` +
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
    
    // Check if this is a return form - if so, auto-fill stock and LOT from toner code
    const actionInput = form.querySelector('input[name="action"]');
    if (actionInput && actionInput.value === 'return') {
        const tonerCodeInput = document.getElementById('tonerCodeInput');
        const stockInput = document.getElementById('stockLocationInput');
        const lotInput = document.getElementById('lotDisplay');
        
        console.log('=== PRE-SUBMIT VALIDATION ===');
        console.log('Toner code:', tonerCodeInput?.value);
        console.log('Stock value:', stockInput?.value);
        console.log('LOT value:', lotInput?.value);
        
        // If stock or LOT is empty, try to auto-fill from issuedTonersData
        if (tonerCodeInput && tonerCodeInput.value && (!stockInput.value || !lotInput.value)) {
            console.log('Stock or LOT is empty - attempting auto-fill...');
            const code = tonerCodeInput.value.trim();
            
            if (typeof issuedTonersData !== 'undefined') {
                const issuedToner = issuedTonersData.find(t => t.code === code);
                console.log('Found toner for auto-fill:', issuedToner);
                
                if (issuedToner) {
                    if (!stockInput.value && issuedToner.stock) {
                        stockInput.value = issuedToner.stock;
                        const stockDisplay = document.getElementById('stockLocationDisplay');
                        if (stockDisplay) stockDisplay.value = issuedToner.stock;
                        console.log('✓ Auto-filled stock:', issuedToner.stock);
                    }
                    
                    if (!lotInput.value && issuedToner.lot) {
                        lotInput.value = issuedToner.lot;
                        const lotDisplay = document.getElementById('lotDisplayField');
                        if (lotDisplay) lotDisplay.value = issuedToner.lot;
                        console.log('✓ Auto-filled LOT:', issuedToner.lot);
                    }
                }
            }
        }
        
        console.log('Final stock value:', stockInput?.value);
        console.log('Final LOT value:', lotInput?.value);
    }
    
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
                <title>Toner Returns Report</title>
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
                    <h3>Toner Returns Report</h3>
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

// Toner code auto-suggest and autofill
function initTonerCodeAutoSuggest() {
    const input = document.getElementById('tonerCodeInput');
    const suggestionsBox = document.getElementById('tonerCodeSuggestions');
    
    console.log('🔧 Initializing toner code auto-suggest...');
    
    if (!input || !suggestionsBox) {
        console.error('❌ Missing elements for auto-suggest');
        return;
    }
    
    // Remove old listeners
    const oldInput = input.cloneNode(true);
    input.parentNode.replaceChild(oldInput, input);
    const newInput = document.getElementById('tonerCodeInput');
    
    let timer;
    
    // Input event - show suggestions
    newInput.addEventListener('input', function() {
        const val = newInput.value.trim();
        clearTimeout(timer);
        
        if (val.length < 1) {
            suggestionsBox.style.display = 'none';
            clearAllFields();
            return;
        }
        
        timer = setTimeout(() => {
            console.log('🔍 Searching for:', val);
            
            // Search in issuedTonersData first
            if (typeof issuedTonersData !== 'undefined' && issuedTonersData.length > 0) {
                const matches = issuedTonersData.filter(t => 
                    t.code && t.code.toLowerCase().includes(val.toLowerCase())
                );
                
                if (matches.length > 0) {
                    console.log('🎯 Found matches:', matches);
                    console.log('First match quantity:', matches[0].issued_quantity);
                    console.log('First match tender_file_no:', matches[0].tender_file_no);
                    console.log('First match invoice:', matches[0].invoice);
                    console.log('First match supplier_name:', matches[0].supplier_name);
                    suggestionsBox.innerHTML = matches.map(t =>
                        `<div class='suggestion-item' 
                            data-toner-id='${t.toner_id}' 
                            data-code='${t.code}' 
                            data-model='${t.toner_model}' 
                            data-stock='${t.stock || ''}' 
                            data-lot='${t.lot || ''}'
                            data-issue-date='${t.issue_date || ''}'
                            data-division='${t.division || ''}'
                            data-section='${t.section || ''}'
                            data-receiver-name='${t.receiver_name || ''}'
                            data-receiver-emp-no='${t.receiver_emp_no || ''}'
                            data-quantity='${t.issued_quantity || ''}'
                            data-tender-file-no='${t.tender_file_no || ''}'
                            data-invoice='${t.invoice || ''}'
                            data-supplier-name='${t.supplier_name || ''}'>
                            <div class="suggestion-code">
                                <i class="fas fa-barcode"></i>
                                <span>${t.code}</span>
                            </div>
                            <div class="suggestion-details">
                                <span class="suggestion-model">${t.toner_model}</span>
                                ${t.stock ? '<span class="suggestion-badge stock"><i class="fas fa-warehouse"></i> ' + t.stock + '</span>' : ''}
                                ${t.lot ? '<span class="suggestion-badge lot"><i class="fas fa-tag"></i> ' + t.lot + '</span>' : ''}
                            </div>
                        </div>`
                    ).join('');
                    suggestionsBox.style.display = 'block';
                    console.log('✅ Found', matches.length, 'matches');
                    return;
                }
            }
            
            // Fallback to API
            fetch(`../api/get_toner_suggestions.php?q=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        suggestionsBox.style.display = 'none';
                        return;
                    }
                    suggestionsBox.innerHTML = data.map(t =>
                        `<div class='suggestion-item' 
                            data-toner-id='${t.id}' 
                            data-code='${t.code}' 
                            data-model='${t.model}' 
                            data-stock='${t.stock_location || ''}' 
                            data-lot='${t.lot || ''}'
                            data-quantity='${t.quantity || ''}'>
                            <div class="suggestion-code">
                                <i class="fas fa-barcode"></i>
                                <span>${t.code}</span>
                            </div>
                            <div class="suggestion-details">
                                <span class="suggestion-model">${t.model}</span>
                                ${t.stock_location ? '<span class="suggestion-badge stock"><i class="fas fa-warehouse"></i> ' + t.stock_location + '</span>' : ''}
                                ${t.lot ? '<span class="suggestion-badge lot"><i class="fas fa-tag"></i> ' + t.lot + '</span>' : ''}
                            </div>
                        </div>`
                    ).join('');
                    suggestionsBox.style.display = 'block';
                })
                .catch(err => {
                    console.error('❌ Error fetching suggestions:', err);
                    suggestionsBox.style.display = 'none';
                });
        }, 250);
    });
    
    // Click event - select suggestion
    suggestionsBox.addEventListener('mousedown', function(e) {
        const item = e.target.closest('.suggestion-item');
        if (!item) return;
        
        e.preventDefault();
        selectTonerCode(item);
        suggestionsBox.style.display = 'none';
    });
    
    // Blur event - auto-select if exact match
    newInput.addEventListener('blur', function() {
        setTimeout(() => {
            const code = newInput.value.trim();
            if (code && typeof issuedTonersData !== 'undefined') {
                const exactMatch = issuedTonersData.find(t => 
                    t.code && t.code.toUpperCase() === code.toUpperCase()
                );
                if (exactMatch) {
                    fillTonerDetails(exactMatch);
                }
            }
            suggestionsBox.style.display = 'none';
        }, 200);
    });
    
    console.log('✅ Auto-suggest initialized successfully');
}

// Select toner code from suggestion
function selectTonerCode(item) {
    const code = item.getAttribute('data-code');
    const model = item.getAttribute('data-model');
    const tonerId = item.getAttribute('data-toner-id');
    const stock = item.getAttribute('data-stock');
    const lot = item.getAttribute('data-lot');
    const issueDate = item.getAttribute('data-issue-date');
    const division = item.getAttribute('data-division');
    const section = item.getAttribute('data-section');
    const receiverName = item.getAttribute('data-receiver-name');
    const receiverEmpNo = item.getAttribute('data-receiver-emp-no');
    const quantity = item.getAttribute('data-quantity');
    const tenderFileNo = item.getAttribute('data-tender-file-no');
    const invoice = item.getAttribute('data-invoice');
    const supplierName = item.getAttribute('data-supplier-name');
    
    console.log('📋 Selected toner:', { code, model, stock, lot });
    console.log('📊 Quantity from data attribute:', quantity);
    
    // Fill basic fields
    document.getElementById('tonerCodeInput').value = code;
    document.getElementById('tonerIdInput').value = tonerId;
    document.getElementById('tonerModelDisplay').value = model;
    document.getElementById('codeDisplay').value = code;
    
    // Fill STOCK TYPE (both display and hidden)
    const stockInput = document.getElementById('stockLocationInput');
    const stockDisplay = document.getElementById('stockLocationDisplay');
    if (stock && stock !== '' && stock !== 'null') {
        if (stockInput) stockInput.value = stock;
        if (stockDisplay) stockDisplay.value = stock;
        console.log('✅ Stock Type filled:', stock);
    } else {
        if (stockInput) stockInput.value = '';
        if (stockDisplay) stockDisplay.value = 'N/A';
        console.log('⚠️ No stock type available');
    }
    
    // Fill LOT NUMBER (both display and hidden)
    const lotInput = document.getElementById('lotDisplay');
    const lotDisplay = document.getElementById('lotDisplayField');
    if (lot && lot !== '' && lot !== 'null' && lot !== '0') {
        if (lotInput) lotInput.value = lot;
        if (lotDisplay) lotDisplay.value = lot;
        console.log('✅ LOT Number filled:', lot);
    } else {
        if (lotInput) lotInput.value = '';
        if (lotDisplay) lotDisplay.value = 'N/A';
        console.log('⚠️ No LOT number available');
    }
    
    // Fill additional issued toner information
    if (issueDate) {
        const issueDateDisplay = document.getElementById('issueDateDisplay');
        if (issueDateDisplay) issueDateDisplay.value = issueDate;
    }
    
    if (division) {
        const divisionDisplay = document.getElementById('divisionDisplay');
        if (divisionDisplay) divisionDisplay.value = division;
    }
    
    if (section) {
        const sectionDisplay = document.getElementById('sectionDisplay');
        if (sectionDisplay) sectionDisplay.value = section;
    }
    
    if (receiverName) {
        const receiverNameDisplay = document.getElementById('receiverNameDisplay');
        if (receiverNameDisplay) receiverNameDisplay.value = receiverName;
    }
    
    // Fill Tender File No from data attribute
    console.log('📋 Attempting to fill Tender File No from data attribute:', tenderFileNo);
    if (tenderFileNo && tenderFileNo !== '' && tenderFileNo !== 'null') {
        const tenderFileNoInput = document.getElementById('tenderFileNo');
        if (tenderFileNoInput) {
            tenderFileNoInput.value = tenderFileNo;
            tenderFileNoInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                tenderFileNoInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Tender File No filled from data attribute:', tenderFileNo);
        }
    }
    
    // Fill Invoice Number from data attribute
    console.log('📋 Attempting to fill Invoice Number from data attribute:', invoice);
    if (invoice && invoice !== '' && invoice !== 'null') {
        const invoiceInput = document.getElementById('invoiceNumber');
        if (invoiceInput) {
            invoiceInput.value = invoice;
            invoiceInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                invoiceInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Invoice Number filled from data attribute:', invoice);
        }
    }
    
    // Fill Supplier Name from data attribute - both hidden and visible fields
    console.log('📋 Attempting to fill Supplier Name from data attribute:', supplierName);
    if (supplierName && supplierName !== '' && supplierName !== 'null') {
        const supplierNameInputHidden = document.getElementById('supplierName');
        const supplierNameInputVisible = document.getElementById('supplierNameField');
        
        if (supplierNameInputHidden) {
            supplierNameInputHidden.value = supplierName;
            console.log('✅ Supplier Name (hidden) filled:', supplierName);
        }
        
        if (supplierNameInputVisible) {
            supplierNameInputVisible.value = supplierName;
            supplierNameInputVisible.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                supplierNameInputVisible.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Supplier Name (visible) filled from data attribute:', supplierName);
        }
    }
    
    // Fill quantity directly from data attribute - PRIORITY 1
    console.log('🔢 Attempting to fill quantity from data attribute:', quantity);
    if (quantity && quantity !== '' && quantity !== 'null' && quantity !== '0') {
        // Use setTimeout to ensure DOM is fully ready and prevent clearing
        setTimeout(() => {
            const quantityInput = document.getElementById('quantityInput') || document.querySelector('input[name="quantity"]');
            console.log('🎯 Quantity input field found:', !!quantityInput);
            if (quantityInput) {
                quantityInput.value = quantity;
                quantityInput.setAttribute('value', quantity);
                
                // Trigger input event for validation
                const event = new Event('input', { bubbles: true });
                quantityInput.dispatchEvent(event);
                
                quantityInput.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    quantityInput.style.backgroundColor = '';
                }, 2000);
                console.log('✅ Quantity filled from selectTonerCode data attribute:', quantity);
                console.log('📊 Field value after fill:', quantityInput.value);
            } else {
                console.error('❌ Quantity input field not found!');
            }
        }, 100);
    } else {
        console.warn('⚠️ No valid quantity in data attribute:', quantity);
    }
    
    // Also try to find additional info from issuedTonersData including quantity
    if (typeof issuedTonersData !== 'undefined') {
        const issuedToner = issuedTonersData.find(t => t.code === code);
        if (issuedToner) {
            console.log('🔍 Found issued toner data:', issuedToner);
            
            // Fill quantity immediately from issued toner - PRIORITY 2 (backup)
            if (issuedToner.issued_quantity) {
                const quantityInput = document.getElementById('quantityInput') || document.querySelector('input[name="quantity"]');
                if (quantityInput && !quantityInput.value) {
                    quantityInput.value = issuedToner.issued_quantity;
                    quantityInput.setAttribute('value', issuedToner.issued_quantity);
                    quantityInput.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        quantityInput.style.backgroundColor = '';
                    }, 2000);
                    console.log('✅ Quantity filled from issuedTonersData lookup (backup):', issuedToner.issued_quantity);
                }
            }
            
            fillAdditionalDetails(issuedToner);
        }
    }
}

// Fill toner details from issued toner data
function fillTonerDetails(toner) {
    console.log('📝 Filling details for:', toner.code);
    
    document.getElementById('tonerCodeInput').value = toner.code;
    document.getElementById('tonerIdInput').value = toner.toner_id;
    document.getElementById('tonerModelDisplay').value = toner.toner_model;
    document.getElementById('codeDisplay').value = toner.code;
    
    // Fill STOCK TYPE
    const stockInput = document.getElementById('stockLocationInput');
    const stockDisplay = document.getElementById('stockLocationDisplay');
    if (toner.stock && toner.stock !== '' && toner.stock !== 'null') {
        if (stockInput) stockInput.value = toner.stock;
        if (stockDisplay) stockDisplay.value = toner.stock;
        console.log('✅ Stock Type:', toner.stock);
    } else {
        if (stockInput) stockInput.value = '';
        if (stockDisplay) stockDisplay.value = 'N/A';
    }
    
    // Fill LOT NUMBER
    const lotInput = document.getElementById('lotDisplay');
    const lotDisplay = document.getElementById('lotDisplayField');
    if (toner.lot && toner.lot !== '' && toner.lot !== 'null' && toner.lot !== '0') {
        if (lotInput) lotInput.value = toner.lot;
        if (lotDisplay) lotDisplay.value = toner.lot;
        console.log('✅ LOT Number:', toner.lot);
    } else {
        if (lotInput) lotInput.value = '';
        if (lotDisplay) lotDisplay.value = 'N/A';
    }
    
    // Fill QUANTITY from issued toner
    if (toner.issued_quantity) {
        const quantityInput = document.getElementById('quantityInput') || document.querySelector('input[name="quantity"]');
        if (quantityInput) {
            quantityInput.value = toner.issued_quantity;
            quantityInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                quantityInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Quantity auto-filled from fillTonerDetails:', toner.issued_quantity);
        }
    }
    
    fillAdditionalDetails(toner);
}

// Fill additional issued toner information
function fillAdditionalDetails(toner) {
    // Fill issue date
    if (toner.issue_date) {
        const issueDateDisplay = document.getElementById('issueDateDisplay');
        if (issueDateDisplay) issueDateDisplay.value = toner.issue_date;
    }
    
    // Fill division
    if (toner.division) {
        const divisionDisplay = document.getElementById('divisionDisplay');
        if (divisionDisplay) divisionDisplay.value = toner.division;
    }
    
    // Fill section
    if (toner.section) {
        const sectionDisplay = document.getElementById('sectionDisplay');
        if (sectionDisplay) sectionDisplay.value = toner.section;
    }
    
    // Fill receiver name
    if (toner.receiver_name) {
        const receiverNameDisplay = document.getElementById('receiverNameDisplay');
        if (receiverNameDisplay) receiverNameDisplay.value = toner.receiver_name;
    }
    
    // Fill printer model
    if (toner.printer_model) {
        const printerModelDisplay = document.getElementById('printerModelDisplay');
        if (printerModelDisplay) printerModelDisplay.value = toner.printer_model;
    }
    
    // Fill printer no
    if (toner.printer_no) {
        const printerNoDisplay = document.getElementById('printerNoDisplay');
        if (printerNoDisplay) printerNoDisplay.value = toner.printer_no;
    }
    
    // Fill color
    if (toner.color) {
        const colorDisplay = document.getElementById('colorDisplay');
        if (colorDisplay) colorDisplay.value = toner.color;
    }
    
    // Fill supplier name from receiving data
    console.log('🏢 Checking supplier name from toner data:', toner.supplier_name);
    if (toner.supplier_name && toner.supplier_name !== 'null' && toner.supplier_name !== '') {
        const supplierNameField = document.getElementById('supplierNameField');
        const supplierNameHidden = document.getElementById('supplierName');
        
        console.log('🔍 Supplier field found:', !!supplierNameField);
        
        if (supplierNameField) {
            supplierNameField.value = toner.supplier_name;
            supplierNameField.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                supplierNameField.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Supplier Name auto-filled to visible field:', toner.supplier_name);
        } else {
            console.error('❌ Supplier field #supplierNameField not found!');
        }
        
        if (supplierNameHidden) {
            supplierNameHidden.value = toner.supplier_name;
            console.log('✅ Supplier Name auto-filled to hidden field');
        }
    } else {
        console.warn('⚠️ No supplier name available in toner data');
    }
    
    // Fill tender file no
    if (toner.tender_file_no && toner.tender_file_no !== 'null' && toner.tender_file_no !== '') {
        const tenderFileNoField = document.getElementById('tenderFileNo');
        if (tenderFileNoField) {
            tenderFileNoField.value = toner.tender_file_no;
            console.log('✅ Tender File No auto-filled:', toner.tender_file_no);
        }
    }
    
    // Fill invoice
    if (toner.invoice && toner.invoice !== 'null' && toner.invoice !== '') {
        const invoiceField = document.getElementById('invoiceNumber');
        if (invoiceField) {
            invoiceField.value = toner.invoice;
            console.log('✅ Invoice Number auto-filled:', toner.invoice);
        }
    }
    
    // Fill receiving date
    if (toner.receiving_date && toner.receiving_date !== 'null' && toner.receiving_date !== '') {
        const receivingDateField = document.getElementById('receivingDate');
        if (receivingDateField) {
            receivingDateField.value = toner.receiving_date;
            console.log('✅ Receiving Date auto-filled:', toner.receiving_date);
        }
    }
    
    // Fill quantity from issued toner data - PRIORITY 3 (last fallback)
    console.log('🔄 fillAdditionalDetails checking quantity:', toner.issued_quantity);
    if (toner.issued_quantity) {
        const quantityInput = document.getElementById('quantityInput') || document.querySelector('input[name="quantity"]');
        console.log('📦 Quantity field in fillAdditionalDetails:', quantityInput ? 'FOUND' : 'NOT FOUND');
        if (quantityInput && !quantityInput.value) {
            console.log('⚠️ Field was empty, filling now...');
            quantityInput.value = toner.issued_quantity;
            quantityInput.setAttribute('value', toner.issued_quantity);
            quantityInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                quantityInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Quantity auto-filled in fillAdditionalDetails:', toner.issued_quantity);
        } else if (quantityInput && quantityInput.value) {
            console.log('ℹ️ Quantity already has value:', quantityInput.value, '- skipping');
        }
    } else {
        console.log('❌ No issued_quantity in toner data');
    }
    
    // Fill supplier name from receiving data
    if (toner.supplier_name) {
        const supplierNameInput = document.getElementById('supplierName') || document.getElementById('supplierNameDisplay') || document.querySelector('input[name="supplier_name"]');
        if (supplierNameInput) {
            supplierNameInput.value = toner.supplier_name;
            supplierNameInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                supplierNameInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Supplier Name auto-filled:', toner.supplier_name);
        }
    }
    
    // Fill tender file no from receiving data
    if (toner.tender_file_no) {
        const tenderFileNoInput = document.getElementById('tenderFileNo') || document.querySelector('input[name="tender_file_no"]');
        if (tenderFileNoInput) {
            tenderFileNoInput.value = toner.tender_file_no;
            tenderFileNoInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                tenderFileNoInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Tender File No auto-filled:', toner.tender_file_no);
        }
    }
    
    // Fill invoice number from receiving data
    if (toner.invoice) {
        const invoiceInput = document.getElementById('invoiceNumber') || document.querySelector('input[name="invoice"]');
        if (invoiceInput) {
            invoiceInput.value = toner.invoice;
            invoiceInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                invoiceInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Invoice Number auto-filled:', toner.invoice);
        }
    }
    
    // Fill receiving date
    if (toner.receiving_date) {
        const receivingDateInput = document.querySelector('input[name="receiving_date"]');
        if (receivingDateInput) {
            receivingDateInput.value = toner.receiving_date;
            receivingDateInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                receivingDateInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Receiving Date auto-filled:', toner.receiving_date);
        }
    }
    
    // Fill tender file no
    if (toner.tender_file_no) {
        const tenderFileInput = document.querySelector('input[name="tender_file_no"]');
        if (tenderFileInput) {
            tenderFileInput.value = toner.tender_file_no;
            tenderFileInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                tenderFileInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Tender File No auto-filled:', toner.tender_file_no);
        }
    }
    
    // Fill invoice number
    if (toner.invoice) {
        const invoiceInput = document.querySelector('input[name="invoice"]');
        if (invoiceInput) {
            invoiceInput.value = toner.invoice;
            invoiceInput.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                invoiceInput.style.backgroundColor = '';
            }, 2000);
            console.log('✅ Invoice Number auto-filled:', toner.invoice);
        }
    }
}

// Clear all form fields
function clearAllFields() {
    const fields = [
        'tonerIdInput', 'tonerModelDisplay', 'codeDisplay',
        'stockLocationInput', 'stockLocationDisplay',
        'lotDisplay', 'lotDisplayField',
        'issueDateDisplay', 'divisionDisplay', 'sectionDisplay',
        'receiverNameDisplay', 'printerModelDisplay',
        'printerNoDisplay', 'colorDisplay'
    ];
    
    fields.forEach(id => {
        const field = document.getElementById(id);
        if (field) field.value = '';
    });
}

// Stock location auto-suggest (kept for compatibility)
function initStockLocationAutoSuggest() {
    console.log('✅ Stock location auto-suggest (compatibility mode)');
}

function updateStockLocationSuggestion() {
    const tonerCodeInput = document.getElementById('tonerCodeInput');
    const stockLocationInput = document.getElementById('stockLocationInput');
    
    if (!tonerCodeInput || !stockLocationInput) return;
    
    const code = tonerCodeInput.value.trim();
    console.log('Updating stock location for code:', code);
    
    // Find issued toner with this code
    if (typeof issuedTonersData !== 'undefined' && issuedTonersData.length > 0) {
        const issuedToner = issuedTonersData.find(t => t.code === code);
        
        if (issuedToner && issuedToner.stock) {
            stockLocationInput.value = issuedToner.stock;
            console.log('Auto-filled stock location:', issuedToner.stock);
        }
    }
}

function getStockTypesFromIssuedToners() {
    const stockTypes = new Set();
    
    // Add default options
    stockTypes.add('JCT');
    stockTypes.add('UCT');
    
    // Add stock types from issued toners
    if (typeof issuedTonersData !== 'undefined' && issuedTonersData.length > 0) {
        issuedTonersData.forEach(toner => {
            if (toner.stock) {
                stockTypes.add(toner.stock);
            }
        });
    }
    
    return Array.from(stockTypes).sort();
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
    let reportTitle = 'Toner Returns Report';
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
            reportTitle = 'Daily Toner Returns Report';
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
            reportTitle = 'Monthly Toner Returns Report';
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
            reportTitle = 'Yearly Toner Returns Report';
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
            reportTitle = 'Custom Toner Returns Report';
            dateRange = `${new Date(fromDate).toLocaleDateString()} - ${new Date(toDate).toLocaleDateString()}`;
            break;
            
        case 'all':
        default:
            reportTitle = 'Complete Toner Returns Report';
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
                    <th>Toner Model</th>
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
                <td>${item.toner_model || 'N/A'}</td>
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
            <div>Generated by SLPA Toner Management System</div>
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

console.log('Toner Returns JavaScript loaded successfully');

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