// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        
        // Set default date to today for new return
        if (modalId === 'returnRibbonModal') {
            const returnDateInput = document.getElementById('returnDate');
            if (returnDateInput && !returnDateInput.value) {
                returnDateInput.value = new Date().toISOString().split('T')[0];
            }
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        
        // Reset form if it's the return modal
        if (modalId === 'returnRibbonModal') {
            const form = document.getElementById('returnForm');
            if (form) {
                form.reset();
                // Clear hidden fields
                document.getElementById('ribbonIdInput').value = '';
                document.getElementById('ribbonModelHidden').value = '';
                document.getElementById('codeHidden').value = '';
                document.getElementById('lotHidden').value = '';
            }
        }
    }
}

// View Return Details
function viewReturn(returnId) {
    const returnRecord = returnsData.find(r => r.return_id == returnId);
    if (!returnRecord) return;
    
    const details = `
        <div class="view-details">
            <h3><i class="fas fa-undo-alt"></i> Return Details</h3>
            
            <div class="detail-section">
                <h4>Ribbon Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Ribbon Model:</span>
                    <span class="detail-value">${returnRecord.ribbon_model || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Code:</span>
                    <span class="detail-value">${returnRecord.code || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">LOT:</span>
                    <span class="detail-value">${returnRecord.lot && returnRecord.lot != '0' ? returnRecord.lot : 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Supplier:</span>
                    <span class="detail-value">${returnRecord.supplier_name || 'N/A'}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Return Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Return Date:</span>
                    <span class="detail-value">${formatDate(returnRecord.return_date)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Issue Date:</span>
                    <span class="detail-value">${returnRecord.issue_date ? formatDate(returnRecord.issue_date) : 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Receiving Date:</span>
                    <span class="detail-value">${returnRecord.receiving_date ? formatDate(returnRecord.receiving_date) : 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Division:</span>
                    <span class="detail-value">${returnRecord.division || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Section:</span>
                    <span class="detail-value">${returnRecord.section || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tender File No:</span>
                    <span class="detail-value">${returnRecord.tender_file_no && returnRecord.tender_file_no != '0' ? returnRecord.tender_file_no : 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Invoice:</span>
                    <span class="detail-value">${returnRecord.invoice || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Return By:</span>
                    <span class="detail-value">${returnRecord.return_by || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Quantity:</span>
                    <span class="detail-value"><strong>${returnRecord.quantity}</strong></span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Return Reason</h4>
                <div class="detail-row">
                    <span class="detail-label">Reason:</span>
                    <span class="detail-value">${returnRecord.reason || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Remarks:</span>
                    <span class="detail-value">${returnRecord.remarks || 'N/A'}</span>
                </div>
            </div>
        </div>
    `;
    
    showAlert('Return Details', details, 'info');
}

// Edit Return
function editReturn(returnId) {
    const returnRecord = returnsData.find(r => r.return_id == returnId);
    if (!returnRecord) return;
    
    // Populate edit form
    document.getElementById('editReturnId').value = returnRecord.return_id;
    document.getElementById('editRibbonId').value = returnRecord.ribbon_id;
    document.getElementById('editRibbonModel').value = returnRecord.ribbon_model;
    document.getElementById('editCode').value = returnRecord.code || '';
    document.getElementById('editLot').value = returnRecord.lot || '';
    document.getElementById('editSupplier').value = returnRecord.supplier_name || '';
    document.getElementById('editReturnDate').value = returnRecord.return_date;
    document.getElementById('editReceivingDate').value = returnRecord.receiving_date || '';
    document.getElementById('editTenderFile').value = returnRecord.tender_file_no || '';
    document.getElementById('editInvoice').value = returnRecord.invoice || '';
    document.getElementById('editReturnBy').value = returnRecord.return_by;
    document.getElementById('editQuantity').value = returnRecord.quantity;
    document.getElementById('editReason').value = returnRecord.reason;
    document.getElementById('editRemarks').value = returnRecord.remarks || '';
    
    openModal('editReturnModal');
}

// Delete Confirmation
function confirmDelete(returnId) {
    const returnRecord = returnsData.find(r => r.return_id == returnId);
    if (!returnRecord) return;
    
    const message = `
        <p>Are you sure you want to delete this return record?</p>
        <div class="delete-info">
            <p><strong>Ribbon Model:</strong> ${returnRecord.ribbon_model}</p>
            <p><strong>Code:</strong> ${returnRecord.code || 'N/A'}</p>
            <p><strong>Return Date:</strong> ${formatDate(returnRecord.return_date)}</p>
            <p><strong>Quantity:</strong> ${returnRecord.quantity}</p>
        </div>
        <p class="warning-text"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone!</p>
    `;
    
    if (confirm(message.replace(/<[^>]*>/g, ''))) {
        window.location.href = `?delete=${returnId}`;
    }
}

// Autocomplete for IS Code with Professional Design
let codeTimeout;
let selectedSuggestionIndex = -1;

document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('codeInput');
    const codeSuggestions = document.getElementById('codeSuggestions');
    
    if (codeInput && codeSuggestions) {
        // Input event for searching
        codeInput.addEventListener('input', function() {
            clearTimeout(codeTimeout);
            const searchTerm = this.value.trim().toUpperCase();
            selectedSuggestionIndex = -1;
            
            if (searchTerm.length < 1) {
                codeSuggestions.style.display = 'none';
                return;
            }
            
            codeTimeout = setTimeout(() => {
                const matches = issuingRecords.filter(record => 
                    record.code && record.code.toUpperCase().includes(searchTerm)
                );
                
                displayCodeSuggestions(matches, codeSuggestions);
            }, 300);
        });
        
        // Focus event to show all suggestions
        codeInput.addEventListener('focus', function() {
            const searchTerm = this.value.trim().toUpperCase();
            if (searchTerm.length === 0 && issuingRecords.length > 0) {
                displayCodeSuggestions(issuingRecords, codeSuggestions);
            }
        });
        
        // Keyboard navigation
        codeInput.addEventListener('keydown', function(e) {
            handleCodeKeyboard(e, codeSuggestions);
        });
        
        // Click outside to close
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.autocomplete-container')) {
                codeSuggestions.style.display = 'none';
            }
        });
    }
});

// Display code suggestions with professional design
function displayCodeSuggestions(matches, codeSuggestions) {
    if (matches.length > 0) {
        codeSuggestions.innerHTML = matches.map((record, index) => {
            const lotBadge = record.lot ? `<span class="detail-badge badge-lot">LOT: ${escapeHtml(record.lot)}</span>` : '';
            const issueDateBadge = record.issue_date ? `<span class="detail-badge badge-issue">Issue: ${formatDate(record.issue_date)}</span>` : '';
            
            // Determine stock type badge
            let stockBadge = '';
            if (record.jct_quantity && record.jct_quantity > 0 && record.uct_quantity && record.uct_quantity > 0) {
                stockBadge = '<span class="detail-badge badge-stock"><i class="fas fa-layer-group"></i> JCT & UCT</span>';
            } else if (record.jct_quantity && record.jct_quantity > 0) {
                stockBadge = '<span class="detail-badge badge-jct"><i class="fas fa-box"></i> JCT</span>';
            } else if (record.uct_quantity && record.uct_quantity > 0) {
                stockBadge = '<span class="detail-badge badge-uct"><i class="fas fa-cube"></i> UCT</span>';
            }
            
            return `
                <div class="suggestion-item" data-index="${index}" onclick="selectCode('${escapeHtml(record.code)}')">
                    <div class="suggestion-code">
                        <i class="fas fa-barcode"></i> ${escapeHtml(record.code)}
                    </div>
                    <div class="suggestion-model">
                        ${escapeHtml(record.ribbon_model || 'Unknown Model')}
                    </div>
                    <div class="suggestion-details">
                        ${lotBadge}
                        ${issueDateBadge}
                        ${stockBadge}
                    </div>
                </div>
            `;
        }).join('') + `
            <div class="keyboard-hint">
                <kbd>↑↓</kbd> Navigate <kbd>Enter</kbd> Select <kbd>Esc</kbd> Close
            </div>
        `;
        codeSuggestions.style.display = 'block';
    } else {
        codeSuggestions.innerHTML = `
            <div class="no-suggestions">
                <i class="fas fa-search"></i>
                <p>No matching codes found</p>
                <small>Try a different IS code</small>
            </div>
        `;
        codeSuggestions.style.display = 'block';
    }
}

// Handle keyboard navigation
function handleCodeKeyboard(e, codeSuggestions) {
    if (codeSuggestions.style.display === 'none') return;
    
    const items = codeSuggestions.querySelectorAll('.suggestion-item');
    if (items.length === 0) return;
    
    // Arrow Down
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedSuggestionIndex = (selectedSuggestionIndex + 1) % items.length;
        updateSelectedSuggestion(items);
    }
    // Arrow Up
    else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedSuggestionIndex = selectedSuggestionIndex <= 0 ? items.length - 1 : selectedSuggestionIndex - 1;
        updateSelectedSuggestion(items);
    }
    // Enter
    else if (e.key === 'Enter' && selectedSuggestionIndex >= 0) {
        e.preventDefault();
        items[selectedSuggestionIndex].click();
    }
    // Escape
    else if (e.key === 'Escape') {
        e.preventDefault();
        codeSuggestions.style.display = 'none';
        selectedSuggestionIndex = -1;
    }
}

// Update selected suggestion styling
function updateSelectedSuggestion(items) {
    items.forEach((item, index) => {
        if (index === selectedSuggestionIndex) {
            item.classList.add('active');
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } else {
            item.classList.remove('active');
        }
    });
}

// Select Code from Autocomplete
function selectCode(code) {
    const record = issuingRecords.find(r => r.code === code);
    if (!record) return;
    
    // Set code fields
    document.getElementById('codeInput').value = code;
    document.getElementById('codeHidden').value = code;
    
    // Set ribbon fields
    document.getElementById('ribbonIdInput').value = record.ribbon_id;
    document.getElementById('ribbonModelHidden').value = record.ribbon_model;
    document.getElementById('ribbonModelDisplay').value = record.ribbon_model;
    
    // Set LOT fields
    if (record.lot) {
        document.getElementById('lotHidden').value = record.lot;
        document.getElementById('lotInput').value = record.lot;
    }
    
    // Auto-fill form fields
    const form = document.getElementById('returnForm');
    if (form) {
        if (record.supplier_name) form.querySelector('[name="supplier_name"]').value = record.supplier_name;
        if (record.issue_date) document.getElementById('issueDateInput').value = record.issue_date;
        if (record.receiving_date) form.querySelector('[name="receiving_date"]').value = record.receiving_date;
        if (record.tender_file_no) form.querySelector('[name="tender_file_no"]').value = record.tender_file_no;
        if (record.invoice && document.getElementById('invoiceInput')) document.getElementById('invoiceInput').value = record.invoice;
        if (record.division) document.getElementById('divisionInput').value = record.division;
        if (record.section) document.getElementById('sectionInput').value = record.section;
    }
    
    // Hide suggestions
    document.getElementById('codeSuggestions').style.display = 'none';
    selectedSuggestionIndex = -1;
}

// Helper functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Table Filter Functions
function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const supplierFilter = document.getElementById('supplierFilter').value.toLowerCase();
    const table = document.getElementById('returnsTable');
    const tbody = table.querySelector('tbody');
    const rows = tbody.getElementsByTagName('tr');
    
    for (let row of rows) {
        const cells = row.getElementsByTagName('td');
        let showRow = true;
        
        // Search filter
        if (searchInput) {
            const rowText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(' ');
            if (!rowText.includes(searchInput)) {
                showRow = false;
            }
        }
        
        // Supplier filter
        if (supplierFilter && showRow) {
            const supplierCell = cells[4]; // Supplier column
            if (supplierCell && !supplierCell.textContent.toLowerCase().includes(supplierFilter)) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    }
}

function populateSupplierFilter() {
    const supplierFilter = document.getElementById('supplierFilter');
    if (!supplierFilter) return;
    
    const suppliers = new Set();
    returnsData.forEach(r => {
        if (r.supplier_name && r.supplier_name.trim()) {
            suppliers.add(r.supplier_name.trim());
        }
    });
    
    const sortedSuppliers = Array.from(suppliers).sort();
    sortedSuppliers.forEach(supplier => {
        const option = document.createElement('option');
        option.value = supplier;
        option.textContent = supplier;
        supplierFilter.appendChild(option);
    });
}

function refreshTable() {
    window.location.reload();
}

// Utility Functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function showAlert(title, message, type = 'info') {
    const iconMap = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    
    const icon = iconMap[type] || 'info-circle';
    
    const alertHtml = `
        <div class="custom-alert ${type}">
            <div class="custom-alert-header">
                <i class="fas fa-${icon}"></i>
                <h3>${title}</h3>
            </div>
            <div class="custom-alert-body">
                ${message}
            </div>
            <div class="custom-alert-footer">
                <button class="btn btn-primary" onclick="closeAlert()">OK</button>
            </div>
        </div>
        <div class="custom-alert-overlay" onclick="closeAlert()"></div>
    `;
    
    const alertContainer = document.createElement('div');
    alertContainer.id = 'customAlertContainer';
    alertContainer.innerHTML = alertHtml;
    document.body.appendChild(alertContainer);
}

function closeAlert() {
    const alertContainer = document.getElementById('customAlertContainer');
    if (alertContainer) {
        alertContainer.remove();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
