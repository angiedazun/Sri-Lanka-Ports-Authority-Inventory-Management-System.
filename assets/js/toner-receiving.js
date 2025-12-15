// Toner Receiving Page JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    initializeTonerReceiving();
});

function initializeTonerReceiving() {
    // Initialize search functionality
    initializeSearch();
    
    // Initialize modal functionality
    initializeModals();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize toner selection functionality
    initializeTonerSelection();
    
    // Initialize supplier filter
    initializeSupplierFilter();
    
    // Set default dates
    setDefaultDates();
    
    // Check for success messages and close modals
    checkSuccessMessageAndCloseModal();
    
    // Initialize stock number auto-generation
    initializeStockAutoGeneration();
    
    // Enhanced toner selection for new layout
    initializeEnhancedTonerSelection();
}

// Enhanced Toner Selection Functions
function initializeEnhancedTonerSelection() {
    const tonerSelect = document.getElementById('tonerSelect');
    const editTonerSelect = document.getElementById('editTonerSelect');
    
    // Add styling to toner select dropdowns
    if (tonerSelect) {
        tonerSelect.addEventListener('change', function() {
            updateTonerDetails();
            highlightTonerInfo();
        });
    }
    
    if (editTonerSelect) {
        editTonerSelect.addEventListener('change', function() {
            updateEditTonerDetails();
            highlightTonerInfo();
        });
    }
}

function highlightTonerInfo() {
    // Add visual feedback when toner is selected
    const tonerModelDisplay = document.getElementById('tonerModelDisplay') || document.getElementById('editTonerModelDisplay');
    const colorDisplay = document.getElementById('colorDisplay') || document.getElementById('editColorDisplay');
    
    if (tonerModelDisplay && tonerModelDisplay.value) {
        tonerModelDisplay.style.background = 'linear-gradient(135deg, #e6f3ff 0%, #ccedff 100%)';
        tonerModelDisplay.style.borderColor = '#667eea';
    }
    
    if (colorDisplay && colorDisplay.value) {
        colorDisplay.style.background = 'linear-gradient(135deg, #e6fffa 0%, #b3f0e6 100%)';
        colorDisplay.style.borderColor = '#38a169';
    }
}

// Update toner details for main form
function updateTonerDetails() {
    const select = document.getElementById('tonerSelect');
    const modelDisplay = document.getElementById('tonerModelDisplay');
    const colorDisplay = document.getElementById('colorDisplay');
    
    if (select && select.value) {
        const selectedOption = select.options[select.selectedIndex];
        const model = selectedOption.getAttribute('data-model');
        const color = selectedOption.getAttribute('data-color');
        
        if (modelDisplay) {
            modelDisplay.value = model || '';
        }
        if (colorDisplay) {
            colorDisplay.value = color || '';
        }
        
        // Highlight the selection
        highlightTonerInfo();
    } else {
        // Clear fields if no selection
        if (modelDisplay) modelDisplay.value = '';
        if (colorDisplay) colorDisplay.value = '';
        
        // Reset styling
        if (modelDisplay) {
            modelDisplay.style.background = 'linear-gradient(135deg, #f8f9fc 0%, #e6f3ff 100%)';
            modelDisplay.style.borderColor = '#e2e8f0';
        }
        if (colorDisplay) {
            colorDisplay.style.background = 'linear-gradient(135deg, #f0fff4 0%, #e6fffa 100%)';
            colorDisplay.style.borderColor = '#e2e8f0';
        }
    }
}

// Update toner details for edit form
function updateEditTonerDetails() {
    const select = document.getElementById('editTonerSelect');
    const modelDisplay = document.getElementById('editTonerModelDisplay');
    const colorDisplay = document.getElementById('editColorDisplay');
    
    if (select && select.value) {
        const selectedOption = select.options[select.selectedIndex];
        const model = selectedOption.getAttribute('data-model');
        const color = selectedOption.getAttribute('data-color');
        
        if (modelDisplay) {
            modelDisplay.value = model || '';
        }
        if (colorDisplay) {
            colorDisplay.value = color || '';
        }
        
        // Highlight the selection
        highlightTonerInfo();
    } else {
        // Clear fields if no selection
        if (modelDisplay) modelDisplay.value = '';
        if (colorDisplay) colorDisplay.value = '';
        
        // Reset styling
        if (modelDisplay) {
            modelDisplay.style.background = 'linear-gradient(135deg, #f8f9fc 0%, #e6f3ff 100%)';
            modelDisplay.style.borderColor = '#e2e8f0';
        }
        if (colorDisplay) {
            colorDisplay.style.background = 'linear-gradient(135deg, #f0fff4 0%, #e6fffa 100%)';
            colorDisplay.style.borderColor = '#e2e8f0';
        }
    }
}

// Search and Filter Functions
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        // Add real-time search with debouncing
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(filterTable, 300);
        });
    }
}

function filterTable() {
    const searchInput = document.getElementById('searchInput');
    const supplierFilter = document.getElementById('supplierFilter');
    const stockFilter = document.getElementById('stockFilter');
    
    if (!searchInput) return;
    
    const searchValue = searchInput.value.toLowerCase();
    const supplierValue = supplierFilter ? supplierFilter.value.toLowerCase() : '';
    const stockValue = stockFilter ? stockFilter.value.toLowerCase() : '';
    
    const table = document.getElementById('receivingsTable');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    Array.from(rows).forEach(row => {
        const cells = row.getElementsByTagName('td');
        if (cells.length === 0) return;
        
        // Extract text content from cells
        const receiveDate = cells[0].textContent.toLowerCase();
        const tonerModel = cells[1].textContent.toLowerCase();
        const lot = cells[2].textContent.toLowerCase();
        const supplier = cells[3].textContent.toLowerCase();
        const prNo = cells[4].textContent.toLowerCase();
        const invoice = cells[9].textContent.toLowerCase();
        
        // Check search criteria
        const matchesSearch = searchValue === '' || 
            tonerModel.includes(searchValue) || 
            supplier.includes(searchValue) || 
            prNo.includes(searchValue) || 
            invoice.includes(searchValue);
        
        const matchesSupplier = supplierValue === '' || supplier.includes(supplierValue);
        const matchesStock = stockValue === '' || lot.includes(stockValue);
        
        // Show/hide row based on all criteria
        if (matchesSearch && matchesSupplier && matchesStock) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Modal Functions
function initializeModals() {
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Focus first input
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Reset form if it exists
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            // Clear any error states
            const errorFields = form.querySelectorAll('.error');
            errorFields.forEach(field => field.classList.remove('error'));
        }
    }
}

// Form Validation Functions
function initializeFormValidation() {
    // Add real-time validation for quantity fields
    const quantityInputs = document.querySelectorAll('input[name="jct_quantity"], input[name="uct_quantity"]');
    quantityInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            validateQuantities();
            // Re-validate unit price to update total calculation
            const form = e.target.closest('form');
            const unitPriceInput = form.querySelector('input[name="unit_price"]');
            if (unitPriceInput && unitPriceInput.value.trim()) {
                validateUnitPriceField({ target: unitPriceInput });
            }
        });
    });
    
    // Add real-time validation for unit price fields
    const unitPriceInputs = document.querySelectorAll('input[name="unit_price"]');
    unitPriceInputs.forEach(input => {
        input.addEventListener('input', validateUnitPriceField);
        input.addEventListener('blur', validateUnitPriceField);
        input.addEventListener('keyup', validateUnitPriceField);
        input.addEventListener('change', validateUnitPriceField);
        
        // Trigger validation for fields that already have values
        if (input.value.trim()) {
            validateUnitPriceField({ target: input });
        }
    });
}

function validateUnitPriceField(event) {
    const input = event.target;
    const value = parseFloat(input.value);
    
    // Always clear previous errors and styles first
    input.classList.remove('error');
    input.style.borderColor = '';
    clearUnitPriceError(input);
    
    // Show validation message for any filled field
    if (input.value.trim()) {
        if (isNaN(value)) {
            input.classList.add('error');
            showUnitPriceError(input, 'Please enter a valid number for unit price', 'error');
        } else if (value < 0) {
            input.classList.add('error');
            showUnitPriceError(input, 'Unit price cannot be negative', 'error');
        } else if (value > 1000000) {
            input.classList.add('error');
            showUnitPriceError(input, 'Unit price seems too high (over 1,000,000). Please check the value', 'error');
        } else if (value === 0) {
            // Show warning for zero price
            showUnitPriceError(input, 'Unit price is 0. Is this correct?', 'warning');
            input.style.borderColor = '#ffc107'; // Yellow warning color
        } else {
            // Calculate total price if quantities are available
            const form = input.closest('form');
            const jctQtyInput = form.querySelector('input[name="jct_quantity"]');
            const uctQtyInput = form.querySelector('input[name="uct_quantity"]');
            
            let jctQty = 0, uctQty = 0;
            if (jctQtyInput && jctQtyInput.value.trim()) {
                jctQty = parseInt(jctQtyInput.value) || 0;
            }
            if (uctQtyInput && uctQtyInput.value.trim()) {
                uctQty = parseInt(uctQtyInput.value) || 0;
            }
            
            const totalQty = jctQty + uctQty;
            const totalPrice = totalQty * value;
            
            let message = `Unit price: Rs. ${value.toFixed(2)} - Valid`;
            
            // Add total price calculation if quantities are available
            if (totalQty > 0) {
                message += `<br><strong style="font-size: 1.1em; color: #155724;">🎯 FULL PAYMENT: Rs. ${totalPrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>`;
                message += `<br><small style="color: #6c757d;">Calculation: ${jctQty > 0 ? jctQty + ' JCT' : ''}${jctQty > 0 && uctQty > 0 ? ' + ' : ''}${uctQty > 0 ? uctQty + ' UCT' : ''} × Rs. ${value.toFixed(2)} = Rs. ${totalPrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</small>`;
            }
            
            // Show success message with total calculation
            showUnitPriceError(input, message, 'success');
            input.style.borderColor = '#28a745'; // Green success color
        }
    }
}

function validateQuantities() {
    const jctQty = parseInt(document.querySelector('input[name="jct_quantity"]').value) || 0;
    const uctQty = parseInt(document.querySelector('input[name="uct_quantity"]').value) || 0;
    
    const totalQty = jctQty + uctQty;
    
    if (totalQty === 0) {
        // Show warning that at least one quantity should be greater than 0
        const warningMsg = document.getElementById('quantityWarning');
        if (!warningMsg) {
            const warning = document.createElement('div');
            warning.id = 'quantityWarning';
            warning.className = 'alert alert-warning';
            warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> At least one quantity (JCT or UCT) must be greater than 0';
            
            const quantitySection = document.querySelector('.form-section h4 i.fa-boxes').parentElement.parentElement;
            quantitySection.appendChild(warning);
        }
    } else {
        // Remove warning if exists
        const warningMsg = document.getElementById('quantityWarning');
        if (warningMsg) {
            warningMsg.remove();
        }
    }
}

// Toner Selection Functions
function initializeTonerSelection() {
    const tonerSelect = document.getElementById('tonerSelect');
    if (tonerSelect) {
        tonerSelect.addEventListener('change', updateTonerDetails);
    }
    
    const editTonerSelect = document.getElementById('editTonerSelect');
    if (editTonerSelect) {
        editTonerSelect.addEventListener('change', updateEditTonerDetails);
    }
}

function updateTonerDetails() {
    const tonerSelect = document.getElementById('tonerSelect');
    const selectedOption = tonerSelect.options[tonerSelect.selectedIndex];
    
    if (selectedOption.value) {
        const tonerModel = selectedOption.getAttribute('data-model');
        const color = selectedOption.getAttribute('data-color');
        
        document.getElementById('tonerModelDisplay').value = tonerModel || '';
        document.getElementById('colorDisplay').value = color || '';
    } else {
        document.getElementById('tonerModelDisplay').value = '';
        document.getElementById('colorDisplay').value = '';
    }
}

function updateEditTonerDetails() {
    const select = document.getElementById('editTonerSelect');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById('editTonerModelDisplay').value = option.getAttribute('data-model') || '';
        document.getElementById('editColorDisplay').value = option.getAttribute('data-color') || '';
    } else {
        document.getElementById('editTonerModelDisplay').value = '';
        document.getElementById('editColorDisplay').value = '';
    }
}

function setDefaultDates() {
    const today = new Date().toISOString().split('T')[0];
    const receiveDateInput = document.getElementById('receiveDate');
    const editReceiveDateInput = document.getElementById('editReceiveDate');
    
    if (receiveDateInput && !receiveDateInput.value) {
        receiveDateInput.value = today;
    }
    if (editReceiveDateInput && !editReceiveDateInput.value) {
        editReceiveDateInput.value = today;
    }
}

// Supplier Filter Initialization
function initializeSupplierFilter() {
    const supplierFilter = document.getElementById('supplierFilter');
    if (!supplierFilter || typeof receivingsData === 'undefined') return;
    
    // Get unique suppliers
    const suppliers = [...new Set(receivingsData
        .map(r => r.supplier_name)
        .filter(s => s && s.trim() !== '')
    )].sort();
    
    // Add supplier options
    suppliers.forEach(supplier => {
        const option = document.createElement('option');
        option.value = supplier;
        option.textContent = supplier;
        supplierFilter.appendChild(option);
    });
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    // Validate that at least one quantity is greater than 0
    const jctQty = parseInt(form.querySelector('input[name="jct_quantity"]').value) || 0;
    const uctQty = parseInt(form.querySelector('input[name="uct_quantity"]').value) || 0;
    
    if (jctQty <= 0 && uctQty <= 0) {
        alert('At least one quantity (JCT or UCT) must be greater than 0!');
        isValid = false;
    }
    
    // Validate unit price
    const unitPriceInput = form.querySelector('input[name="unit_price"]');
    if (unitPriceInput && unitPriceInput.value.trim()) {
        const unitPrice = parseFloat(unitPriceInput.value);
        if (isNaN(unitPrice)) {
            unitPriceInput.classList.add('error');
            showUnitPriceError(unitPriceInput, 'Please enter a valid number for unit price', 'error');
            isValid = false;
        } else if (unitPrice < 0) {
            unitPriceInput.classList.add('error');
            showUnitPriceError(unitPriceInput, 'Unit price cannot be negative', 'error');
            isValid = false;
        } else if (unitPrice > 1000000) {
            unitPriceInput.classList.add('error');
            showUnitPriceError(unitPriceInput, 'Unit price seems too high (over 1,000,000). Please check the value', 'error');
            isValid = false;
        } else {
            unitPriceInput.classList.remove('error');
            clearUnitPriceError(unitPriceInput);
        }
    }
    
    if (!isValid) {
        alert('Please fill in all required fields correctly.');
        return false;
    }
    
    // Add loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        // Reset button after 5 seconds in case of error
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.dataset.originalText || 'Submit';
        }, 5000);
    }
    
    return true;
}

// CRUD Functions
function viewReceiving(receiveId) {
    // Find the receiving data from global variable
    if (typeof receivingsData === 'undefined') {
        alert('Receiving data not loaded');
        return;
    }
    
    const receiving = receivingsData.find(r => r.receive_id == receiveId);
    if (!receiving) {
        alert('Receiving not found');
        return;
    }
    
    // Create detailed view content
    const content = `
        <div class="view-section">
            <h4><i class="fas fa-toner"></i> Toner Information</h4>
            <div class="view-grid">
                <div class="view-item">
                    <label>Toner Model:</label>
                    <span>${receiving.toner_model}</span>
                </div>
                <div class="view-item">
                    <label>Stock Location:</label>
                    <span class="badge" style="background: linear-gradient(135deg, rgb(40,167,69) 0%, rgb(32,201,151) 100%); color: white; padding: 5px 12px; border-radius: 20px;">${receiving.stock || 'N/A'}</span>
                </div>
                <div class="view-item">
                    <label>LOT Number:</label>
                    <span class="badge" style="background: linear-gradient(135deg, rgb(102,126,234) 0%, rgb(118,75,162) 100%); color: white; padding: 5px 12px; border-radius: 20px;">${receiving.lot || 'N/A'}</span>
                </div>
                <div class="view-item">
                    <label>Color:</label>
                    <span class="color-badge" style="background-color: ${receiving.color.toLowerCase()}">${receiving.color}</span>
                </div>
            </div>
        </div>
        
        <div class="view-section">
            <h4><i class="fas fa-building"></i> Supplier Information</h4>
            <div class="view-grid">
                <div class="view-item">
                    <label>Supplier Name:</label>
                    <span>${receiving.supplier_name || 'N/A'}</span>
                </div>
                <div class="view-item">
                    <label>PR Number:</label>
                    <span>${receiving.pr_no || 'N/A'}</span>
                </div>
                <div class="view-item">
                    <label>Tender File Number:</label>
                    <span>${receiving.tender_file_no || 'N/A'}</span>
                </div>
                <div class="view-item">
                    <label>Invoice:</label>
                    <span>${receiving.invoice || 'N/A'}</span>
                </div>
            </div>
        </div>
        
        <div class="view-section">
            <h4><i class="fas fa-boxes"></i> Quantity & Pricing</h4>
            <div class="view-grid">
                <div class="view-item">
                    <label>JCT Quantity:</label>
                    <span class="quantity-badge">${receiving.jct_quantity}</span>
                </div>
                <div class="view-item">
                    <label>UCT Quantity:</label>
                    <span class="quantity-badge">${receiving.uct_quantity}</span>
                </div>
                <div class="view-item">
                    <label>Total Quantity:</label>
                    <span class="quantity-badge">${parseInt(receiving.jct_quantity) + parseInt(receiving.uct_quantity)}</span>
                </div>
                <div class="view-item">
                    <label>Unit Price:</label>
                    <span>Rs. ${parseFloat(receiving.unit_price).toFixed(2)}</span>
                </div>
                <div class="view-item">
                    <label>Total Value:</label>
                    <span style="font-weight: bold; color: #28a745;">Rs. ${((parseInt(receiving.jct_quantity) + parseInt(receiving.uct_quantity)) * parseFloat(receiving.unit_price)).toFixed(2)}</span>
                </div>
            </div>
        </div>
        
        <div class="view-section">
            <h4><i class="fas fa-info-circle"></i> Additional Information</h4>
            <div class="view-grid">
                <div class="view-item">
                    <label>Receive Date:</label>
                    <span>${new Date(receiving.receive_date).toLocaleDateString()}</span>
                </div>
                <div class="view-item full-width">
                    <label>Remarks:</label>
                    <span>${receiving.remarks || 'N/A'}</span>
                </div>
            </div>
        </div>
    `;
    
    const viewContent = document.getElementById('viewReceivingContent');
    if (viewContent) {
        viewContent.innerHTML = content;
        openModal('viewReceivingModal');
    }
}

function editReceiving(receiveId) {
    // Find the receiving data from global variable
    if (typeof receivingsData === 'undefined') {
        alert('Receiving data not loaded');
        return;
    }
    
    const receiving = receivingsData.find(r => r.receive_id == receiveId);
    if (!receiving) {
        alert('Receiving not found');
        return;
    }
    
    // Populate the edit form
    const setFieldValue = (id, value) => {
        const field = document.getElementById(id);
        if (field) field.value = value || '';
    };
    
    setFieldValue('editReceiveId', receiving.receive_id);
    setFieldValue('editTonerSelect', receiving.toner_id);
    setFieldValue('editTonerModelDisplay', receiving.toner_model);
    
    // Set stock location
    setFieldValue('editStock', receiving.stock);
    
    // Extract number from LOT string (e.g., "LOT 1" -> "1")
    if (receiving.lot) {
        const lotNumber = receiving.lot.replace(/[^0-9]/g, '');
        setFieldValue('editStockNumber', lotNumber);
        setFieldValue('editStockHidden', receiving.lot);
    }
    
    setFieldValue('editColorDisplay', receiving.color);
    setFieldValue('editSupplierName', receiving.supplier_name);
    setFieldValue('editPrNo', receiving.pr_no);
    setFieldValue('editTenderFileNo', receiving.tender_file_no);
    setFieldValue('editJctQuantity', receiving.jct_quantity);
    setFieldValue('editUctQuantity', receiving.uct_quantity);
    setFieldValue('editUnitPrice', receiving.unit_price);
    setFieldValue('editInvoice', receiving.invoice);
    setFieldValue('editReceiveDate', receiving.receive_date);
    setFieldValue('editRemarks', receiving.remarks);
    
    // Show the modal
    openModal('editReceivingModal');
}

function confirmDelete(receiveId) {
    if (confirm('Are you sure you want to delete this receiving record? This action cannot be undone and will adjust the stock levels.')) {
        window.location.href = '?delete=' + receiveId;
    }
}

// Utility Functions
function refreshTable() {
    window.location.reload();
}

function printTable() {
    const printContent = document.getElementById('receivingsTable').outerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Toner Receivings Report</title>
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h2>Toner Receivings Report</h2>
            <p>Generated on: ${new Date().toLocaleDateString()}</p>
            ${printContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Export Functions (can be extended)
function exportToCSV() {
    const table = document.getElementById('receivingsTable');
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    Array.from(rows).forEach(row => {
        if (row.style.display === 'none') return; // Skip hidden rows
        
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        
        Array.from(cols).forEach((col, index) => {
            // Skip actions column
            if (index === cols.length - 1) return;
            
            let cellText = col.textContent.trim();
            // Escape quotes and wrap in quotes if contains comma
            if (cellText.includes(',') || cellText.includes('"')) {
                cellText = '"' + cellText.replace(/"/g, '""') + '"';
            }
            csvRow.push(cellText);
        });
        
        if (csvRow.length > 0) {
            csv.push(csvRow.join(','));
        }
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `toner_receivings_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Unit Price Validation Helper Functions
function showUnitPriceError(field, message, type = 'error') {
    clearUnitPriceError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error unit-price-error';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '5px';
    errorDiv.style.display = 'block';
    errorDiv.style.fontWeight = '500';
    errorDiv.style.borderRadius = '4px';
    errorDiv.style.padding = '8px';
    
    // Set colors and icons based on message type
    if (type === 'error') {
        errorDiv.style.color = '#dc3545';
        errorDiv.style.backgroundColor = '#f8d7da';
        errorDiv.style.border = '1px solid #f5c6cb';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle" style="margin-right: 5px;"></i>${message}`;
    } else if (type === 'warning') {
        errorDiv.style.color = '#856404';
        errorDiv.style.backgroundColor = '#fff3cd';
        errorDiv.style.border = '1px solid #ffeaa7';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle" style="margin-right: 5px;"></i>${message}`;
    } else if (type === 'success') {
        errorDiv.style.color = '#155724';
        errorDiv.style.backgroundColor = '#d4edda';
        errorDiv.style.border = '1px solid #c3e6cb';
        errorDiv.innerHTML = `<i class="fas fa-check-circle" style="margin-right: 5px;"></i>${message}`;
    }
    
    field.parentNode.appendChild(errorDiv);
}

function clearUnitPriceError(field) {
    const existingError = field.parentNode.querySelector('.unit-price-error');
    if (existingError) {
        existingError.remove();
    }
}

// Function to manually trigger validation for all unit price fields
function validateAllUnitPriceFields() {
    const unitPriceInputs = document.querySelectorAll('input[name="unit_price"]');
    unitPriceInputs.forEach(input => {
        if (input.value.trim()) {
            validateUnitPriceField({ target: input });
        }
    });
}

// Function to show validation on form open (for modals)
function showValidationOnFormOpen(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Add a small delay to ensure modal is fully rendered
        setTimeout(() => {
            validateAllUnitPriceFields();
        }, 100);
    }
}

// Function to check for success messages and close modals automatically
function checkSuccessMessageAndCloseModal() {
    // Check if there's a success alert on the page
    const successAlert = document.querySelector('.alert.alert-success');
    if (successAlert) {
        // Close any open modals
        const openModals = document.querySelectorAll('.modal[style*="display: block"], .modal[style*="display:block"]');
        openModals.forEach(modal => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            
            // Reset form if it exists
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                // Clear any error states
                const errorFields = form.querySelectorAll('.error');
                errorFields.forEach(field => field.classList.remove('error'));
                
                // Clear unit price validation messages
                const errorMessages = form.querySelectorAll('.unit-price-error');
                errorMessages.forEach(msg => msg.remove());
            }
        });
        
        // Scroll to the success message to make it visible
        successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto-hide the success message after 5 seconds
        setTimeout(() => {
            if (successAlert.parentNode) {
                successAlert.style.transition = 'opacity 0.5s ease-out';
                successAlert.style.opacity = '0';
                setTimeout(() => {
                    if (successAlert.parentNode) {
                        successAlert.remove();
                    }
                }, 500);
            }
        }, 5000);
    }
}

// Stock Auto-Generation Functions
function initializeStockAutoGeneration() {
    // Add form stock number handler
    const stockNumber = document.getElementById('stockNumber');
    const stockHidden = document.getElementById('stockHidden');
    
    if (stockNumber && stockHidden) {
        stockNumber.addEventListener('input', function() {
            updateStockValue(this.value, stockHidden);
        });
        
        // Update on page load if there's a value
        if (stockNumber.value) {
            updateStockValue(stockNumber.value, stockHidden);
        }
    }
    
    // Edit form stock number handler
    const editStockNumber = document.getElementById('editStockNumber');
    const editStockHidden = document.getElementById('editStockHidden');
    
    if (editStockNumber && editStockHidden) {
        editStockNumber.addEventListener('input', function() {
            updateStockValue(this.value, editStockHidden);
        });
        
        // Update on page load if there's a value
        if (editStockNumber.value) {
            updateStockValue(editStockNumber.value, editStockHidden);
        }
    }
}

function updateStockValue(number, hiddenField) {
    if (number && parseInt(number) > 0) {
        const currentYear = new Date().getFullYear();
        hiddenField.value = currentYear + '/LOT ' + parseInt(number);
    } else {
        hiddenField.value = '';
    }
}

// Print Modal Functions
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
            break;
        case 'monthly':
            document.getElementById('monthlySection').style.display = 'flex';
            break;
        case 'yearly':
            document.getElementById('yearlySection').style.display = 'flex';
            break;
        case 'custom':
            document.getElementById('customSection').style.display = 'flex';
            break;
    }
}

function generatePrintReport() {
    const printType = document.getElementById('printType').value;
    const reportFormat = document.getElementById('reportFormat').value;
    
    // Get filter criteria
    let filteredData = [...receivingsData];
    let reportTitle = 'Toner Receiving Report';
    let dateRange = '';
    
    // Filter data based on print type
    switch(printType) {
        case 'daily':
            const dailyDate = document.getElementById('dailyDate').value;
            if (dailyDate) {
                filteredData = filteredData.filter(item => item.receive_date === dailyDate);
                reportTitle = 'Daily Toner Receiving Report';
                dateRange = new Date(dailyDate).toLocaleDateString();
            }
            break;
            
        case 'monthly':
            const month = document.getElementById('monthSelect').value;
            const monthlyYear = document.getElementById('monthlyYear').value;
            filteredData = filteredData.filter(item => {
                const itemDate = new Date(item.receive_date);
                return itemDate.getMonth() + 1 === parseInt(month) && 
                       itemDate.getFullYear() === parseInt(monthlyYear);
            });
            reportTitle = 'Monthly Toner Receiving Report';
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            dateRange = monthNames[parseInt(month) - 1] + ' ' + monthlyYear;
            break;
            
        case 'yearly':
            const year = document.getElementById('yearSelect').value;
            filteredData = filteredData.filter(item => {
                const itemDate = new Date(item.receive_date);
                return itemDate.getFullYear() === parseInt(year);
            });
            reportTitle = 'Yearly Toner Receiving Report';
            dateRange = year;
            break;
            
        case 'custom':
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            if (fromDate && toDate) {
                filteredData = filteredData.filter(item => {
                    return item.receive_date >= fromDate && item.receive_date <= toDate;
                });
                reportTitle = 'Custom Toner Receiving Report';
                dateRange = new Date(fromDate).toLocaleDateString() + ' - ' + new Date(toDate).toLocaleDateString();
            }
            break;
            
        case 'all':
        default:
            reportTitle = 'Complete Toner Receiving Report';
            dateRange = 'All Records';
            break;
    }
    
    // Get print options
    const includeStatistics = document.getElementById('includeStatistics').checked;
    const includeSupplier = document.getElementById('includeSupplier').checked;
    const includePricing = document.getElementById('includePricing').checked;
    const includeRemarks = document.getElementById('includeRemarks').checked;
    
    // Calculate statistics
    let totalReceivings = filteredData.length;
    let totalQuantity = 0;
    let totalValue = 0;
    let supplierStats = {};
    
    filteredData.forEach(item => {
        totalQuantity += parseInt(item.jct_quantity) + parseInt(item.uct_quantity);
        totalValue += (parseInt(item.jct_quantity) + parseInt(item.uct_quantity)) * parseFloat(item.unit_price);
        
        if (item.supplier_name) {
            supplierStats[item.supplier_name] = (supplierStats[item.supplier_name] || 0) + 1;
        }
    });
    
    // Generate print content
    let printContent = generatePrintHTML(
        reportTitle, 
        dateRange, 
        filteredData, 
        reportFormat,
        {
            includeStatistics,
            includeSupplier,
            includePricing,
            includeRemarks,
            totalReceivings,
            totalQuantity,
            totalValue,
            supplierStats
        }
    );
    
    // Open print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Trigger print dialog
    setTimeout(() => {
        printWindow.print();
        // Close modal after printing
        closeModal('printModal');
    }, 500);
}

function generatePrintHTML(title, dateRange, data, format, options) {
    const {
        includeStatistics,
        includeSupplier, 
        includePricing,
        includeRemarks,
        totalReceivings,
        totalQuantity,
        totalValue,
        supplierStats
    } = options;
    
    let html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                    line-height: 1.4;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .header h1 {
                    color: #667eea;
                    margin: 0;
                    font-size: 24px;
                }
                .header p {
                    margin: 5px 0;
                    color: #666;
                }
                .statistics {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                }
                .stat-item {
                    text-align: center;
                    padding: 15px;
                    background: white;
                    border-radius: 6px;
                    border: 1px solid #e0e0e0;
                }
                .stat-item h3 {
                    margin: 0 0 10px 0;
                    font-size: 20px;
                    color: #667eea;
                }
                .stat-item p {
                    margin: 0;
                    color: #666;
                    font-size: 14px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 11px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #667eea;
                    color: white;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                    border-top: 1px solid #e0e0e0;
                    padding-top: 20px;
                }
                @media print {
                    body { margin: 0; }
                    .header { page-break-after: avoid; }
                    table { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>SLPA ${title}</h1>
                <p><strong>Period:</strong> ${dateRange}</p>
                <p><strong>Generated on:</strong> ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}</p>
            </div>
    `;
    
    // Add statistics section if requested
    if (includeStatistics) {
        html += `
            <div class="statistics">
                <div class="stat-item">
                    <h3>${totalReceivings}</h3>
                    <p>Total Receivings</p>
                </div>
                <div class="stat-item">
                    <h3>${totalQuantity}</h3>
                    <p>Total Quantity</p>
                </div>
                <div class="stat-item">
                    <h3>Rs. ${totalValue.toLocaleString()}</h3>
                    <p>Total Value</p>
                </div>
                <div class="stat-item">
                    <h3>${Object.keys(supplierStats).length}</h3>
                    <p>Unique Suppliers</p>
                </div>
            </div>
        `;
    }
    
    // Add data table if not statistics-only format
    if (format !== 'statistics') {
        html += '<table><thead><tr>';
        
        // Define columns based on your requirements
        let columns = [
            { key: 'receive_date', label: 'RECEIVE DATE', show: true },
            { key: 'toner_model', label: 'TONER MODEL', show: true },
            { key: 'stock', label: 'STOCK', show: true },
            { key: 'color', label: 'COLOR', show: true },
            { key: 'supplier_name', label: 'SUPPLIER NAME', show: true },
            { key: 'pr_no', label: 'PR NO', show: true },
            { key: 'tender_file_no', label: 'TENDER FILE NO', show: true },
            { key: 'jct_quantity', label: 'JCT QUANTITY', show: true },
            { key: 'uct_quantity', label: 'UCT QUANTITY', show: true },
            { key: 'unit_price', label: 'UNIT PRICE', show: true },
            { key: 'invoice', label: 'INVOICE', show: true },
            { key: 'remarks', label: 'REMARKS', show: true }
        ];
        
        // Add headers
        columns.forEach(col => {
            if (col.show) {
                html += `<th>${col.label}</th>`;
            }
        });
        html += '</tr></thead><tbody>';
        
        // Add data rows
        data.forEach(item => {
            html += '<tr>';
            columns.forEach(col => {
                if (col.show) {
                    let cellValue = '';
                    switch(col.key) {
                        case 'receive_date':
                            cellValue = new Date(item.receive_date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit'
                            });
                            break;
                        case 'toner_model':
                            cellValue = item.toner_model || 'N/A';
                            break;
                        case 'stock':
                            cellValue = item.stock || 'LOT 1';
                            break;
                        case 'color':
                            cellValue = item.color || 'N/A';
                            break;
                        case 'supplier_name':
                            cellValue = item.supplier_name || 'User';
                            break;
                        case 'pr_no':
                            cellValue = item.pr_no || 'TEx123';
                            break;
                        case 'tender_file_no':
                            cellValue = item.tender_file_no || 'File123';
                            break;
                        case 'jct_quantity':
                            cellValue = item.jct_quantity || '0';
                            break;
                        case 'uct_quantity':
                            cellValue = item.uct_quantity || '0';
                            break;
                        case 'unit_price':
                            const unitPrice = parseFloat(item.unit_price) || 0;
                            const totalQty = (parseInt(item.jct_quantity) || 0) + (parseInt(item.uct_quantity) || 0);
                            const totalValue = unitPrice * totalQty;
                            cellValue = `
                                <div style="text-align: left;">
                                    <div><strong>Rs. ${unitPrice.toLocaleString()}</strong></div>
                                    <div style="margin-top: 5px; color: #28a745; font-weight: bold;">
                                        🎯 FULL PAYMENT: Rs. ${totalValue.toLocaleString()}
                                    </div>
                                    <small style="color: #666;">
                                        Calculation: ${totalQty} × Rs. ${unitPrice.toLocaleString()} = Rs. ${totalValue.toLocaleString()}
                                    </small>
                                </div>
                            `;
                            break;
                        case 'invoice':
                            cellValue = item.invoice || '123';
                            break;
                        case 'remarks':
                            cellValue = item.remarks || 'Urgent';
                            break;
                        default:
                            cellValue = item[col.key] || 'N/A';
                    }
                    html += `<td>${cellValue}</td>`;
                }
            });
            html += '</tr>';
        });
        
        html += '</tbody></table>';
    }
    
    html += `
            <div class="footer">
                <p>This report was generated by SLPA Toner Management System</p>
                <p>For technical support, please contact the IT department.</p>
            </div>
        </body>
        </html>
    `;
    
    return html;
}