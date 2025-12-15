// Toner Issuing Page JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== TonerS-ISSUING.JS LOADED ===');
    console.log('lotStocksData available:', typeof lotStocksData !== 'undefined');
    if (typeof lotStocksData !== 'undefined') {
        console.log('lotStocksData:', lotStocksData);
        console.log('lotStocksData length:', lotStocksData.length);
        
        // Show details of each LOT entry
        if (lotStocksData.length > 0) {
            lotStocksData.forEach((lot, index) => {
                console.log(`LOT ${index}:`, lot);
            });
        }
    } else {
        console.error('ERROR: lotStocksData is not defined!');
    }
    
    console.log('displayLotForToner function:', typeof displayLotForToner);
    console.log('==================================');
    
    initializeTonerIssuing();
});

function initializeTonerIssuing() {
    // Initialize search functionality
    initializeSearch();
    
    // Initialize modal functionality
    initializeModals();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize Toner selection functionality
    initializeTonerSelection();
    
    // Initialize date filters
    initializeDateFilters();
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
    const divisionFilter = document.getElementById('divisionFilter');
    const stockFilter = document.getElementById('stockFilter');
    const dateFilter = document.getElementById('dateFilter');
    
    if (!searchInput) return;
    
    const searchValue = searchInput.value.toLowerCase();
    const divisionValue = divisionFilter ? divisionFilter.value.toLowerCase() : '';
    const stockValue = stockFilter ? stockFilter.value.toLowerCase() : '';
    const dateValue = dateFilter ? dateFilter.value : '';
    
    const table = document.getElementById('issuesTable');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    Array.from(rows).forEach(row => {
        const cells = row.getElementsByTagName('td');
        if (cells.length === 0) return;
        
        // Extract text content from cells
        const issueDate = cells[0].textContent.toLowerCase();
        const tonerModel = cells[1].textContent.toLowerCase();
        const code = cells[2].textContent.toLowerCase();
        const stock = cells[3].textContent.toLowerCase();
        const division = cells[6].textContent.toLowerCase();
        const section = cells[7].textContent.toLowerCase();
        const receiver = cells[8].textContent.toLowerCase();
        
        // Check search criteria
        const matchesSearch = searchValue === '' || 
            tonerModel.includes(searchValue) || 
            code.includes(searchValue) || 
            division.includes(searchValue) || 
            section.includes(searchValue) || 
            receiver.includes(searchValue);
        
        const matchesDivision = divisionValue === '' || division.includes(divisionValue);
        const matchesStock = stockValue === '' || stock.includes(stockValue);
        
        // Date filtering
        let matchesDate = true;
        if (dateValue) {
            const rowDate = new Date(cells[0].textContent);
            const today = new Date();
            
            switch (dateValue) {
                case 'today':
                    matchesDate = rowDate.toDateString() === today.toDateString();
                    break;
                case 'week':
                    const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                    matchesDate = rowDate >= weekAgo;
                    break;
                case 'month':
                    const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
                    matchesDate = rowDate >= monthAgo;
                    break;
            }
        }
        
        // Show/hide row based on all criteria
        if (matchesSearch && matchesDivision && matchesStock && matchesDate) {
            row.style.display = '';
            highlightSearchTerms(row, searchValue);
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update table stats
    updateTableStats();
}

function highlightSearchTerms(row, searchTerm) {
    if (!searchTerm) return;
    
    const cells = row.getElementsByTagName('td');
    Array.from(cells).forEach(cell => {
        const content = cell.innerHTML;
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        cell.innerHTML = content.replace(regex, '<mark>$1</mark>');
    });
}

function updateTableStats() {
    const table = document.getElementById('issuesTable');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    
    // Update any stats display if needed
    console.log(`Showing ${visibleRows.length} of ${rows.length} records`);
}

// Modal Functions
function initializeModals() {
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });
    
    // Close modals with Escape key
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
        
        // Clear all validation errors
        const quantityInput = modal.querySelector('input[name="quantity"]');
        if (quantityInput) {
            quantityInput.setCustomValidity('');
            quantityInput.classList.remove('error');
            quantityInput.removeAttribute('data-max');
        }
        
        // Hide stock info box
        const stockInfoBox = document.getElementById('stockInfoBox');
        if (stockInfoBox) {
            stockInfoBox.style.display = 'none';
        }
        
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
        document.body.style.overflow = 'auto';
        
        // Reset form if it exists
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            clearFormErrors(form);
            resetTonerSelection();
        }
    }
}

// Toner Selection Functions
function initializeTonerSelection() {
    const tonerSelect = document.getElementById('tonerSelect');
    if (tonerSelect) {
        tonerSelect.addEventListener('change', updateTonerDetails);
    }
    
    const stockSelect = document.getElementById('stockSelect');
    if (stockSelect) {
        stockSelect.addEventListener('change', updateAvailableStock);
    }
    
    // Initialize edit form handlers
    const editTonerSelect = document.getElementById('editTonerSelect');
    if (editTonerSelect) {
        editTonerSelect.addEventListener('change', updateEditTonerDetails);
    }
    
    const editStockSelect = document.getElementById('editStockSelect');
    if (editStockSelect) {
        editStockSelect.addEventListener('change', updateEditAvailableStock);
    }
    
    // Set default dates
    setDefaultDates();
}

function setDefaultDates() {
    const today = new Date().toISOString().split('T')[0];
    const issueDateInput = document.getElementById('issueDate');
    const editIssueDateInput = document.getElementById('editIssueDate');
    
    if (issueDateInput && !issueDateInput.value) {
        issueDateInput.value = today;
    }
    if (editIssueDateInput && !editIssueDateInput.value) {
        editIssueDateInput.value = today;
    }
}

function updateTonerDetails() {
    console.log('--- updateTonerDetails called ---');
    const tonerSelect = document.getElementById('tonerSelect');
    const selectedOption = tonerSelect.options[tonerSelect.selectedIndex];
    
    if (selectedOption.value) {
        const tonerId = selectedOption.value;
        const tonerModel = selectedOption.getAttribute('data-model');
        const compatiblePrinters = selectedOption.getAttribute('data-printers');
        const color = selectedOption.getAttribute('data-color');
        const lot = selectedOption.getAttribute('data-lot');
        const prNo = selectedOption.getAttribute('data-pr');
        const jctQty = selectedOption.getAttribute('data-jct');
        const uctQty = selectedOption.getAttribute('data-uct');
        
        console.log('Selected Toner ID:', tonerId);
        console.log('Selected Toner Model:', tonerModel);
        console.log('Compatible Printers:', compatiblePrinters);
        console.log('Selected Color:', color);
        console.log('Selected LOT:', lot);
        console.log('Selected PR Number:', prNo);
        
        document.getElementById('tonerModelDisplay').value = tonerModel || '';
        document.getElementById('colorDisplay').value = color || '';
        document.getElementById('lotDisplay').value = lot || '';
        
        // Auto-fill PR Number
        const printerNoInput = document.querySelector('input[name="printer_no"]');
        if (printerNoInput) {
            printerNoInput.value = prNo || '';
        }
        
        // Populate printer model dropdown
        const printerModelSelect = document.getElementById('printerModelSelect');
        if (printerModelSelect && compatiblePrinters) {
            printerModelSelect.innerHTML = '<option value="">Select printer model</option>';
            const printers = compatiblePrinters.split(',').map(p => p.trim());
            printers.forEach(printer => {
                if (printer) {
                    const option = document.createElement('option');
                    option.value = printer;
                    option.textContent = printer;
                    printerModelSelect.appendChild(option);
                }
            });
        }
        
        // Enable stock select
        const stockSelect = document.getElementById('stockSelect');
        if (stockSelect) {
            stockSelect.disabled = false;
        }
        
        // Store quantities in data attributes for later use
        if (stockSelect) {
            stockSelect.setAttribute('data-jct', jctQty);
            stockSelect.setAttribute('data-uct', uctQty);
        }
        
        // Show stock info box with quantities
        const stockInfoBox = document.getElementById('stockInfoBox');
        const jctQtySpan = document.getElementById('jctQty');
        const uctQtySpan = document.getElementById('uctQty');
        const totalQtySpan = document.getElementById('totalQty');
        
        if (stockInfoBox && jctQtySpan && uctQtySpan && totalQtySpan) {
            const jctStock = parseInt(jctQty) || 0;
            const uctStock = parseInt(uctQty) || 0;
            const totalStock = jctStock + uctStock;
            
            jctQtySpan.textContent = jctStock;
            uctQtySpan.textContent = uctStock;
            totalQtySpan.textContent = totalStock;
            
            stockInfoBox.style.display = 'block';
        }
    } else {
        // Reset form fields when no toner is selected
        document.getElementById('tonerModelDisplay').value = '';
        document.getElementById('colorDisplay').value = '';
        document.getElementById('lotDisplay').value = '';
        
        // Hide stock info box
        const stockInfoBox = document.getElementById('stockInfoBox');
        if (stockInfoBox) {
            stockInfoBox.style.display = 'none';
        }
        
        const stockSelect = document.getElementById('stockSelect');
        if (stockSelect) {
            stockSelect.disabled = true;
            stockSelect.value = '';
        }
        const printerModelSelect = document.getElementById('printerModelSelect');
        if (printerModelSelect) {
            printerModelSelect.innerHTML = '<option value="">Select toner first</option>';
        }
    }
}

function updateAvailableStockForReceiving(stock, jctQty, uctQty) {
    const availableStockInput = document.getElementById('availableStock');
    if (!availableStockInput) return;
    
    if (stock === 'JCT') {
        availableStockInput.value = jctQty || '0';
    } else if (stock === 'UCT') {
        availableStockInput.value = uctQty || '0';
    } else {
        availableStockInput.value = '';
    }
}

function displayLotForToner(tonerId) {
    const lotDisplay = document.getElementById('lotDisplay');
    const lotValue = document.getElementById('lotValue');
    
    console.log('displayLotForToner called with tonerId:', tonerId, 'Type:', typeof tonerId);
    
    if (!lotDisplay) {
        console.log('ERROR: lotDisplay not found');
        return;
    }
    
    // Find LOT numbers for this toner from lot_stocks data
    if (typeof lotStocksData !== 'undefined' && Array.isArray(lotStocksData) && lotStocksData.length > 0) {
        const lotsForToner = [];
        
        console.log('Total LOT records:', lotStocksData.length);
        
        lotStocksData.forEach(function(lot, index) {
            console.log(`Checking lot[${index}]:`, lot);
            console.log(`  lot.toner_id: "${lot.toner_id}" (type: ${typeof lot.toner_id})`);
            console.log(`  tonerId: "${tonerId}" (type: ${typeof tonerId})`);
            console.log(`  lot.lot: "${lot.lot}"`);
            console.log(`  lot.stock: "${lot.stock}"`);
            console.log(`  Comparison: ${lot.toner_id} == ${tonerId} = ${lot.toner_id == tonerId}`);
            
            if (lot && lot.lot && lot.toner_id == tonerId) {
                console.log('✓ MATCH! Adding:', lot.lot);
                if (!lotsForToner.includes(lot.lot)) {
                    lotsForToner.push(lot.lot);
                }
            } else {
                console.log('✗ No match');
            }
        });
        
        console.log('Found LOTs:', lotsForToner);
        
        if (lotsForToner.length > 0) {
            // Display LOT numbers as comma-separated list
            const lotString = lotsForToner.join(', ');
            lotDisplay.value = lotString;
            lotDisplay.style.color = '#28a745';
            lotDisplay.style.fontWeight = '600';
            // Store first LOT as the value to submit
            if (lotValue) {
                lotValue.value = lotsForToner[0];
            }
            console.log('Set LOT display to:', lotString);
        } else {
            // No LOT found
            lotDisplay.value = 'No LOT available for this Toner';
            lotDisplay.style.color = '#dc3545';
            lotDisplay.style.fontWeight = 'normal';
            if (lotValue) lotValue.value = '';
            console.log('No LOTs found');
        }
    } else {
        // No data available
        lotDisplay.value = 'No LOT data available';
        lotDisplay.style.color = '#6c757d';
        lotDisplay.style.fontWeight = 'normal';
        if (lotValue) lotValue.value = '';
        console.log('No lotStocksData available');
    }
}

// Same function for edit form
function displayEditLotForToner(tonerId) {
    const lotDisplay = document.getElementById('editLotDisplay');
    const lotValue = document.getElementById('editLotValue');
    
    console.log('displayEditLotForToner called with tonerId:', tonerId, 'Type:', typeof tonerId);
    
    if (!lotDisplay) {
        console.log('ERROR: editLotDisplay not found');
        return;
    }
    
    // Find LOT numbers for this toner from lot_stocks data
    if (typeof lotStocksData !== 'undefined' && Array.isArray(lotStocksData) && lotStocksData.length > 0) {
        const lotsForToner = [];
        
        console.log('[EDIT] Total LOT records:', lotStocksData.length);
        
        lotStocksData.forEach(function(lot, index) {
            console.log(`[EDIT] Checking lot[${index}]:`, lot);
            
            if (lot && lot.lot && lot.toner_id == tonerId) {
                console.log('[EDIT] ✓ MATCH! Adding:', lot.lot);
                if (!lotsForToner.includes(lot.lot)) {
                    lotsForToner.push(lot.lot);
                }
            }
        });
        
        console.log('[EDIT] Found LOTs:', lotsForToner);
        
        if (lotsForToner.length > 0) {
            // Display LOT numbers as comma-separated list
            const lotString = lotsForToner.join(', ');
            lotDisplay.value = lotString;
            lotDisplay.style.color = '#28a745';
            lotDisplay.style.fontWeight = '600';
            // Store first LOT as the value to submit
            if (lotValue) {
                lotValue.value = lotsForToner[0];
            }
            console.log('[EDIT] Set LOT display to:', lotString);
        } else {
            // No LOT found
            lotDisplay.value = 'No LOT available for this Toner';
            lotDisplay.style.color = '#dc3545';
            lotDisplay.style.fontWeight = 'normal';
            if (lotValue) lotValue.value = '';
            console.log('[EDIT] No LOTs found');
        }
    } else {
        // No data available
        lotDisplay.value = 'No LOT data available';
        lotDisplay.style.color = '#6c757d';
        lotDisplay.style.fontWeight = 'normal';
        if (lotValue) lotValue.value = '';
        console.log('[EDIT] No lotStocksData available');
    }
}

function updateAvailableStock() {
    console.log('updateAvailableStock called');
    const stockSelect = document.getElementById('stockSelect');
    
    if (!stockSelect || !stockSelect.value) {
        console.log('No stock location selected');
        return;
    }
    
    const jctStock = parseInt(stockSelect.getAttribute('data-jct')) || 0;
    const uctStock = parseInt(stockSelect.getAttribute('data-uct')) || 0;
    
    console.log('Stock data:', { jctStock, uctStock, stockLocation: stockSelect.value });
    
    let availableStock = 0;
    if (stockSelect.value === 'JCT') {
        availableStock = jctStock;
    } else if (stockSelect.value === 'UCT') {
        availableStock = uctStock;
    }
    
    console.log('Setting available stock to:', availableStock);
    
    // Update quantity input max value for validation
    const quantityInput = document.querySelector('input[name="quantity"]');
    if (quantityInput) {
        quantityInput.setAttribute('data-max', availableStock);
        
        // Clear any existing validation error
        quantityInput.setCustomValidity('');
        quantityInput.classList.remove('error');
    }
}

function resetTonerSelection() {
    document.getElementById('TonerModelDisplay').value = '';
    document.getElementById('colorDisplay').value = '';
    document.getElementById('availableStock').value = '';
    document.getElementById('stockSelect').disabled = true;
    document.getElementById('stockSelect').value = '';
    hideStockWarning();
}

function showStockWarning(stock) {
    let warning = document.getElementById('stockWarning');
    if (!warning) {
        warning = document.createElement('div');
        warning.id = 'stockWarning';
        warning.className = 'alert alert-warning';
        warning.style.marginTop = '10px';
        
        const availableStockInput = document.getElementById('availableStock');
        availableStockInput.parentNode.appendChild(warning);
    }
    
    if (stock === 0) {
        warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Out of Stock!</strong> Cannot issue this Toner.';
        warning.className = 'alert alert-danger';
    } else {
        warning.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <strong>Low Stock Warning:</strong> Only ${stock} units available.`;
        warning.className = 'alert alert-warning';
    }
}

function hideStockWarning() {
    const warning = document.getElementById('stockWarning');
    if (warning) {
        warning.remove();
    }
}

// Form Validation
function initializeFormValidation() {
    const form = document.querySelector('#issueTonerModal form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateIssueForm(form)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(input);
            });
        });
    }
}

function validateIssueForm(form) {
    console.log('validateIssueForm called');
    let isValid = true;
    
    // Clear previous errors
    clearFormErrors(form);
    
    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            showFieldError(field, 'This field is required');
        }
    });
    
    // Validate quantity vs available stock
    const quantityInput = form.querySelector('input[name="quantity"]');
    
    if (quantityInput) {
        const quantity = parseInt(quantityInput.value) || 0;
        const maxStock = parseInt(quantityInput.getAttribute('data-max')) || 0;
        
        if (maxStock > 0 && quantity > maxStock) {
            isValid = false;
            showFieldError(quantityInput, `Quantity cannot exceed available stock (${maxStock})`);
        }
        
        if (quantity <= 0) {
            isValid = false;
            showFieldError(quantityInput, 'Quantity must be greater than 0');
        }
    }
    
    // SIMPLIFIED DATE VALIDATION - ALLOW ALL DATES FOR NOW
    const issueDateInput = form.querySelector('input[name="issue_date"]');
    console.log('Date input found:', issueDateInput);
    console.log('Date value:', issueDateInput ? issueDateInput.value : 'no input');
    
    // Remove any date validation for testing
    // Date validation is now handled by HTML min/max attributes only
    
    console.log('Validation result:', isValid);
    return isValid;
}

function validateField(field) {
    clearFieldError(field);
    
    if (field.hasAttribute('required') && !field.value.trim()) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    return true;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    let errorElement = field.parentNode.querySelector('.field-error');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

function clearFormErrors(form) {
    const errorElements = form.querySelectorAll('.field-error');
    errorElements.forEach(element => element.remove());
    
    const errorFields = form.querySelectorAll('.error');
    errorFields.forEach(field => field.classList.remove('error'));
}

// Date Filter Functions
function initializeDateFilters() {
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter) {
        dateFilter.addEventListener('change', filterTable);
    }
}

// Issue Management Functions
function viewIssue(issueId) {
    // Find the issue data from global variable
    if (typeof issuesData === 'undefined') {
        alert('Issue data not loaded');
        return;
    }
    
    const issue = issuesData.find(i => i.issue_id == issueId);
    if (!issue) {
        alert('Issue not found');
        return;
    }
    
    // Create detailed view content
    const content = `
        <div class="view-section">
            <h4><i class="fas fa-toner"></i> Toner Information</h4>
            <div class="view-grid">
                <div class="view-item">
                    <label>Toner Model:</label>
                    <span>${issue.toner_model}</span>
                </div>
                <div class="view-item">
                    <label>Code:</label>
                    <span>${issue.code}</span>
                </div>
                <div class="view-item">
                    <label>Stock Type:</label>
                    <span class="stock-badge stock-${issue.stock.toLowerCase()}">${issue.stock}</span>
                </div>
                <div class="view-item">
                    <label>LOT Number:</label>
                    <span class="badge" style="background: linear-gradient(135deg, rgb(102,126,234) 0%, rgb(118,75,162) 100%); color: white; padding: 5px 12px; border-radius: 20px;">${issue.lot || 'N/A'}</span>
                </div>
                <div class="view-item">
                    <label>Color:</label>
                    <span class="color-badge" style="background-color: ${issue.color.toLowerCase()}">${issue.color}</span>
                </div>
            </div>
        </div>
        
        <div class="view-section">
            <h4><i class="fas fa-print"></i> Printer Information</h4>
            <div class="view-grid">
                <div class="view-item">
                    <label>Printer Model:</label>
                    <span>${issue.printer_model || 'N/A'}</span>
                </div>
                <div class="view-item">
                    <label>Printer Number:</label>
                    <span>${issue.printer_no || 'N/A'}</span>
                </div>
            </div>
        </div>
        
        <div class="view-section">
            <h4><i class="fas fa-map-marker-alt"></i> Location Information</h4>
            <div class="view-grid">
                <div class="view-item">
                    <label>Division:</label>
                    <span>${issue.division}</span>
                </div>
                <div class="view-item">
                    <label>Section:</label>
                    <span>${issue.section}</span>
                </div>
                <div class="view-item">
                    <label>Code:</label>
                    <span>${issue.code || 'N/A'}</span>
                </div>
                <div class="view-item">
                    <label>Request Officer:</label>
                    <span>${issue.request_officer || 'N/A'}</span>
                </div>
            </div>
        </div>
        
        <div class="view-section">
            <h4><i class="fas fa-user"></i> Receiver Information</h4>
            <div class="view-grid">
                <div class="view-item">
                    <label>Receiver Name:</label>
                    <span>${issue.receiver_name || 'N/A'}</span>
                </div>
                <div class="view-item">
                    <label>Employee Number:</label>
                    <span>${issue.receiver_emp_no || 'N/A'}</span>
                </div>
            </div>
        </div>
        
        <div class="view-section">
            <h4><i class="fas fa-info-circle"></i> Issue Details</h4>
            <div class="view-grid">
                <div class="view-item">
                    <label>Quantity:</label>
                    <span class="quantity-badge">${issue.quantity}</span>
                </div>
                <div class="view-item">
                    <label>Issue Date:</label>
                    <span>${new Date(issue.issue_date).toLocaleDateString()}</span>
                </div>
                <div class="view-item full-width">
                    <label>Remarks:</label>
                    <span>${issue.remarks || 'N/A'}</span>
                </div>
            </div>
        </div>
    `;
    
    const viewContent = document.getElementById('viewIssueContent');
    if (viewContent) {
        viewContent.innerHTML = content;
        openModal('viewIssueModal');
    }
}

function editIssue(issueId) {
    // Find the issue data from global variable
    if (typeof issuesData === 'undefined') {
        alert('Issue data not loaded');
        return;
    }
    
    const issue = issuesData.find(i => i.issue_id == issueId);
    if (!issue) {
        alert('Issue not found');
        return;
    }
    
    // Populate the edit form
    const setFieldValue = (id, value) => {
        const field = document.getElementById(id);
        if (field) field.value = value || '';
    };
    
    setFieldValue('editIssueId', issue.issue_id);
    setFieldValue('editTonerSelect', issue.toner_id);
    setFieldValue('editTonerModelDisplay', issue.toner_model);
    setFieldValue('editCodeDisplay', issue.code);
    setFieldValue('editStockSelect', issue.stock);
    setFieldValue('editColorDisplay', issue.color);
    setFieldValue('editLotDisplay', issue.lot);
    
    // Populate printer model dropdown from compatible printers
    const editTonerSelect = document.getElementById('editTonerSelect');
    const editPrinterModel = document.getElementById('editPrinterModel');
    if (editTonerSelect && editPrinterModel && issue.toner_id) {
        const selectedOption = editTonerSelect.querySelector(`option[value="${issue.toner_id}"]`);
        if (selectedOption) {
            const compatiblePrinters = selectedOption.getAttribute('data-printers');
            
            // Clear existing options except placeholder
            editPrinterModel.innerHTML = '<option value="">-- Select Printer Model --</option>';
            
            if (compatiblePrinters && compatiblePrinters.trim() !== '') {
                const printers = compatiblePrinters.split(',').map(p => p.trim());
                printers.forEach(printer => {
                    if (printer) {
                        const option = document.createElement('option');
                        option.value = printer;
                        option.textContent = printer;
                        editPrinterModel.appendChild(option);
                    }
                });
            }
        }
    }
    
    setFieldValue('editPrinterModel', issue.printer_model);
    setFieldValue('editPrinterNo', issue.printer_no);
    setFieldValue('editDivision', issue.division);
    setFieldValue('editSection', issue.section);
    setFieldValue('editRequestOfficer', issue.request_officer);
    setFieldValue('editReceiverName', issue.receiver_name);
    setFieldValue('editReceiverEmpNo', issue.receiver_emp_no);
    setFieldValue('editQuantity', issue.quantity);
    setFieldValue('editIssueDate', issue.issue_date);
    setFieldValue('editRemarks', issue.remarks);
    
    // Update available stock display
    updateEditAvailableStock();
    
    // Show the modal
    openModal('editIssueModal');
}

// Export Function
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
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
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(url);
}

// Print Function
function printTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Toner Issues Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .actions { display: none; }
                h1 { color: #333; margin-bottom: 20px; }
                .print-date { margin-bottom: 20px; color: #666; }
            </style>
        </head>
        <body>
            <h1>Toner Issues Report</h1>
            <div class="print-date">Generated on: ${new Date().toLocaleDateString()}</div>
            ${table.outerHTML}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

// Refresh Function
function refreshTable() {
    window.location.reload();
}

// Keyboard Shortcuts
document.addEventListener('keydown', function(event) {
    // Ctrl + N to open new issue modal
    if (event.ctrlKey && event.key === 'n') {
        event.preventDefault();
        openModal('issueTonerModal');
    }
    
    // Ctrl + F to focus search
    if (event.ctrlKey && event.key === 'f') {
        event.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Escape to close modals
    if (event.key === 'Escape') {
        const openModal = document.querySelector('.modal[style*="block"]');
        if (openModal) {
            closeModal(openModal.id);
        }
    }
});

// Auto-complete functionality for common fields
function initializeAutoComplete() {
    const printerModels = [
        'HP LaserJet Pro',
        'Canon PIXMA',
        'Epson WorkForce',
        'Brother HL-L2350DW',
        'Samsung Xpress'
    ];
    
    const sections = [
        'Accounting',
        'Administration',
        'Customer Service',
        'Technical Support',
        'Sales',
        'Marketing'
    ];
    
    // Add autocomplete functionality if needed
    // Implementation depends on whether you want to use a library or custom solution
}

// Utility Functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Additional Functions for Toner Management
function confirmDelete(issueId) {
    if (confirm('Are you sure you want to delete this issue record? This action cannot be undone.')) {
        window.location.href = '?delete=' + issueId;
    }
}

function updateTonerDetailsOld() {
    const select = document.getElementById('tonerSelect');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById('tonerModelDisplay').value = option.dataset.model;
        document.getElementById('colorDisplay').value = option.dataset.color;
        updateAvailableStock();
    } else {
        document.getElementById('tonerModelDisplay').value = '';
        document.getElementById('colorDisplay').value = '';
        document.getElementById('availableStock').value = '';
    }
}

function updateAvailableStockOld() {
    const tonerSelect = document.getElementById('tonerSelect');
    const stockSelect = document.getElementById('stockSelect');
    const stockDisplay = document.getElementById('availableStock');
    
    const selectedOption = tonerSelect.options[tonerSelect.selectedIndex];
    const selectedStock = stockSelect.value;
    
    if (selectedOption.value && selectedStock) {
        let availableStock = 0;
        if (selectedStock === 'JCT') {
            availableStock = selectedOption.dataset.jct;
        } else if (selectedStock === 'UCT') {
            availableStock = selectedOption.dataset.uct;
        }
        stockDisplay.value = availableStock + ' units';
        
        // Add validation for quantity
        const quantityInput = document.querySelector('input[name="quantity"]');
        if (quantityInput) {
            quantityInput.max = availableStock;
            quantityInput.setAttribute('data-max', availableStock);
            
            // Add real-time validation
            quantityInput.addEventListener('input', function() {
                const value = parseInt(this.value);
                const max = parseInt(this.getAttribute('data-max'));
                
                if (value > max) {
                    this.setCustomValidity(`Quantity cannot exceed available stock (${max})`);
                    this.classList.add('error');
                } else if (value < 1) {
                    this.setCustomValidity('Quantity must be at least 1');
                    this.classList.add('error');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('error');
                }
            });
        }
        
        // Show warning if stock is low
        if (availableStock <= 5) {
            stockDisplay.style.color = '#dc3545';
            stockDisplay.style.fontWeight = 'bold';
        } else {
            stockDisplay.style.color = '';
            stockDisplay.style.fontWeight = '';
        }
    } else {
        stockDisplay.value = '';
    }
}

function updateEditTonerDetails() {
    console.log('--- updateEditTonerDetails called ---');
    const select = document.getElementById('editTonerSelect');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const tonerId = option.value;
        const tonerModel = option.getAttribute('data-model');
        const color = option.getAttribute('data-color');
        const lot = option.getAttribute('data-lot');
        const stock = option.getAttribute('data-stock');
        const jctQty = option.getAttribute('data-jct');
        const uctQty = option.getAttribute('data-uct');
        const compatiblePrinters = option.getAttribute('data-printers');
        
        console.log('Edit - Selected Toner ID:', tonerId);
        console.log('Edit - Selected Toner Model:', tonerModel);
        console.log('Edit - Selected LOT:', lot);
        console.log('Edit - Selected Stock:', stock);
        
        document.getElementById('editTonerModelDisplay').value = tonerModel || '';
        document.getElementById('editColorDisplay').value = color || '';
        
        // Set LOT directly from the selected receiving record
        const editLotDisplay = document.getElementById('editLotDisplay');
        const editLotValue = document.getElementById('editLotValue');
        if (editLotDisplay) editLotDisplay.value = lot || '';
        if (editLotValue) editLotValue.value = lot || '';
        
        // Set stock location directly from the selected receiving record
        const editStockSelect = document.getElementById('editStockSelect');
        if (editStockSelect && stock) {
            editStockSelect.value = stock;
        }
        
        // Populate printer model dropdown from compatible printers
        const editPrinterModel = document.getElementById('editPrinterModel');
        if (editPrinterModel) {
            // Store the current value to restore after populating
            const currentValue = editPrinterModel.value;
            
            // Clear existing options except placeholder
            editPrinterModel.innerHTML = '<option value="">-- Select Printer Model --</option>';
            
            if (compatiblePrinters && compatiblePrinters.trim() !== '') {
                const printers = compatiblePrinters.split(',').map(p => p.trim());
                printers.forEach(printer => {
                    if (printer) {
                        const option = document.createElement('option');
                        option.value = printer;
                        option.textContent = printer;
                        editPrinterModel.appendChild(option);
                    }
                });
                
                // Restore the previous value if it exists in the new options
                if (currentValue) {
                    editPrinterModel.value = currentValue;
                }
            }
        }
        
        // Update available stock display
        updateEditAvailableStockForReceiving(stock, jctQty, uctQty);
    } else {
        document.getElementById('editTonerModelDisplay').value = '';
        document.getElementById('editColorDisplay').value = '';
        document.getElementById('editAvailableStockDisplay').value = '';
        
        // Clear LOT display
        const editLotDisplay = document.getElementById('editLotDisplay');
        const editLotValue = document.getElementById('editLotValue');
        if (editLotDisplay) editLotDisplay.value = '';
        if (editLotValue) editLotValue.value = '';
        
        // Reset printer model dropdown
        const editPrinterModel = document.getElementById('editPrinterModel');
        if (editPrinterModel) {
            editPrinterModel.innerHTML = '<option value="">Select toner first</option>';
        }
    }
}

function updateEditAvailableStockForReceiving(stock, jctQty, uctQty) {
    const availableStockInput = document.getElementById('editAvailableStockDisplay');
    if (!availableStockInput) return;
    
    if (stock === 'JCT') {
        availableStockInput.value = jctQty || '0';
    } else if (stock === 'UCT') {
        availableStockInput.value = uctQty || '0';
    } else {
        availableStockInput.value = '';
    }
}

function updateEditLotSuggestions(TonerId) {
    const lotDatalist = document.getElementById('editLotDatalist');
    const lotInput = document.getElementById('editLotInput');
    
    if (!lotDatalist || !lotInput) return;
    
    // Clear existing options
    lotDatalist.innerHTML = '';
    
    // Find LOT numbers for this Toner from lot_stocks data
    if (typeof lotStocksData !== 'undefined' && Array.isArray(lotStocksData) && lotStocksData.length > 0) {
        const lotsForToner = [];
        
        lotStocksData.forEach(function(lot) {
            if (lot && lot.stock && lot.Toner_id == TonerId) {
                if (!lotsForToner.includes(lot.stock)) {
                    lotsForToner.push(lot.stock);
                    // Add option to datalist
                    const option = document.createElement('option');
                    option.value = lot.stock;
                    lotDatalist.appendChild(option);
                }
            }
        });
        
        if (lotsForToner.length > 0) {
            // Show available LOTs in placeholder (don't auto-fill in edit mode to preserve existing value)
            lotInput.placeholder = 'Available: ' + lotsForToner.join(', ');
            lotInput.style.color = '#28a745';
            lotInput.style.fontWeight = '600';
        } else {
            lotInput.placeholder = 'No LOT available';
            lotInput.style.color = '';
            lotInput.style.fontWeight = '';
        }
    } else {
        lotInput.placeholder = 'No LOT data';
    }
}

function clearEditLotSuggestions() {
    const lotDatalist = document.getElementById('editLotDatalist');
    const lotInput = document.getElementById('editLotInput');
    
    if (lotDatalist) lotDatalist.innerHTML = '';
    if (lotInput) {
        lotInput.value = '';
        lotInput.placeholder = 'Select a Toner to see available LOT numbers';
        lotInput.style.color = '';
        lotInput.style.fontWeight = '';
    }
}

function updateEditAvailableStock() {
    const TonerSelect = document.getElementById('editTonerSelect');
    const stockSelect = document.getElementById('editStockSelect');
    const stockDisplay = document.getElementById('editAvailableStockDisplay');
    
    if (!TonerSelect || !stockSelect || !stockDisplay) {
        return;
    }
    
    const selectedOption = TonerSelect.options[TonerSelect.selectedIndex];
    const selectedStock = stockSelect.value;
    
    if (selectedOption.value && selectedStock) {
        const jctStock = parseInt(selectedOption.getAttribute('data-jct')) || 0;
        const uctStock = parseInt(selectedOption.getAttribute('data-uct')) || 0;
        const totalStock = jctStock + uctStock;
        
        let availableStock = 0;
        if (selectedStock === 'JCT') {
            availableStock = jctStock;
        } else if (selectedStock === 'UCT') {
            availableStock = uctStock;
        }
        stockDisplay.value = `JCT: ${jctStock} | UCT: ${uctStock} | Total: ${totalStock} units`;
        
        // Add validation for quantity
        const quantityInput = document.getElementById('editQuantity');
        if (quantityInput) {
            quantityInput.max = availableStock;
            quantityInput.setAttribute('data-max', availableStock);
            
            // Add real-time validation
            quantityInput.addEventListener('input', function() {
                const value = parseInt(this.value);
                const max = parseInt(this.getAttribute('data-max'));
                
                if (value > max) {
                    this.setCustomValidity(`Quantity cannot exceed available stock (${max})`);
                    this.classList.add('error');
                } else if (value < 1) {
                    this.setCustomValidity('Quantity must be at least 1');
                    this.classList.add('error');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('error');
                }
            });
        }
        
        // Show warning if stock is low
        if (availableStock <= 5) {
            stockDisplay.style.color = '#dc3545';
            stockDisplay.style.fontWeight = 'bold';
        } else {
            stockDisplay.style.color = '';
            stockDisplay.style.fontWeight = '';
        }
    } else {
        stockDisplay.value = '';
    }
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
    
    // Validate quantity vs available stock
    const quantityInput = form.querySelector('input[name="quantity"]');
    if (quantityInput) {
        const value = parseInt(quantityInput.value);
        const max = parseInt(quantityInput.getAttribute('data-max') || 999);
        
        if (value > max || value < 1) {
            quantityInput.classList.add('error');
            isValid = false;
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

function refreshTable() {
    window.location.reload();
}

function printTable() {
    const printContent = document.getElementById('issuesTable').outerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Toner Issues Report</title>
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
            <h2>Toner Issues Report</h2>
            <p>Generated on: ${new Date().toLocaleDateString()}</p>
            ${printContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Print Modal Functions for Toner Issuing
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

function generateIssuingPrintReport() {
    const printType = document.getElementById('printType').value;
    const reportFormat = document.getElementById('reportFormat').value;
    
    // Get filter criteria
    let filteredData = [...issuesData];
    let reportTitle = 'Toner Issuing Report';
    let dateRange = '';
    
    // Filter data based on print type
    switch(printType) {
        case 'daily':
            const dailyDate = document.getElementById('dailyDate').value;
            if (dailyDate) {
                filteredData = filteredData.filter(item => item.issue_date === dailyDate);
                reportTitle = 'Daily Toner Issuing Report';
                dateRange = new Date(dailyDate).toLocaleDateString();
            }
            break;
            
        case 'monthly':
            const month = document.getElementById('monthSelect').value;
            const monthlyYear = document.getElementById('monthlyYear').value;
            filteredData = filteredData.filter(item => {
                const itemDate = new Date(item.issue_date);
                const itemMonth = String(itemDate.getMonth() + 1).padStart(2, '0');
                return itemMonth === month && 
                       itemDate.getFullYear() === parseInt(monthlyYear);
            });
            reportTitle = 'Monthly Toner Issuing Report';
            const monthNames = {
                '01': 'January', '02': 'February', '03': 'March', '04': 'April',
                '05': 'May', '06': 'June', '07': 'July', '08': 'August',
                '09': 'September', '10': 'October', '11': 'November', '12': 'December'
            };
            dateRange = monthNames[month] + ' ' + monthlyYear;
            break;
            
        case 'yearly':
            const year = document.getElementById('yearSelect').value;
            filteredData = filteredData.filter(item => {
                const itemDate = new Date(item.issue_date);
                return itemDate.getFullYear() === parseInt(year);
            });
            reportTitle = 'Yearly Toner Issuing Report';
            dateRange = year;
            break;
            
        case 'custom':
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            if (fromDate && toDate) {
                filteredData = filteredData.filter(item => {
                    return item.issue_date >= fromDate && item.issue_date <= toDate;
                });
                reportTitle = 'Custom Toner Issuing Report';
                dateRange = new Date(fromDate).toLocaleDateString() + ' - ' + new Date(toDate).toLocaleDateString();
            }
            break;
            
        case 'all':
        default:
            reportTitle = 'Complete Toner Issuing Report';
            dateRange = 'All Records';
            break;
    }
    
    // Get print options
    const includeStatistics = document.getElementById('includeStatistics').checked;
    const includeDivision = document.getElementById('includeDivision').checked;
    const includePrinter = document.getElementById('includePrinter').checked;
    const includeRemarks = document.getElementById('includeRemarks').checked;
    
    // Calculate statistics
    let totalIssues = filteredData.length;
    let totalQuantity = 0;
    let divisionStats = {};
    
    filteredData.forEach(item => {
        totalQuantity += parseInt(item.quantity);
        
        if (item.division) {
            divisionStats[item.division] = (divisionStats[item.division] || 0) + parseInt(item.quantity);
        }
    });
    
    // Generate print content
    let printContent = generateIssuingPrintHTML(
        reportTitle, 
        dateRange, 
        filteredData, 
        reportFormat,
        {
            includeStatistics,
            includeDivision,
            includePrinter,
            includeRemarks,
            totalIssues,
            totalQuantity,
            divisionStats
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

function generateIssuingPrintHTML(title, dateRange, data, format, options) {
    const {
        includeStatistics,
        includeDivision, 
        includePrinter,
        includeRemarks,
        totalIssues,
        totalQuantity,
        divisionStats
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
                    <h3>${totalIssues}</h3>
                    <p>Total Issues</p>
                </div>
                <div class="stat-item">
                    <h3>${totalQuantity}</h3>
                    <p>Total Quantity</p>
                </div>
                <div class="stat-item">
                    <h3>${Object.keys(divisionStats).length}</h3>
                    <p>Divisions Served</p>
                </div>
            </div>
        `;
    }
    
    // Add data table if not statistics-only format
    if (format !== 'statistics') {
        html += '<table><thead><tr>';
        
        // Define columns based on your requirements
        let columns = [
            { key: 'issue_date', label: 'ISSUE DATE', show: true },
            { key: 'Toner_model', label: 'Toner MODEL', show: true },
            { key: 'code', label: 'CODE', show: true },
            { key: 'stock', label: 'STOCK', show: true },
            { key: 'color', label: 'COLOR', show: true },
            { key: 'printer_model', label: 'PRINTER MODEL', show: includePrinter },
            { key: 'printer_no', label: 'PRINTER NO', show: includePrinter },
            { key: 'division', label: 'DIVISION', show: includeDivision },
            { key: 'section', label: 'SECTION', show: includeDivision },
            { key: 'store', label: 'STORE', show: includeDivision },
            { key: 'request_officer', label: 'REQUEST OFFICER', show: true },
            { key: 'receiver_name', label: 'RECEIVER NAME', show: true },
            { key: 'receiver_emp_no', label: 'RECEIVER EMP NO', show: true },
            { key: 'quantity', label: 'QUANTITY', show: true },
            { key: 'remarks', label: 'REMARKS', show: includeRemarks }
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
                        case 'issue_date':
                            cellValue = new Date(item.issue_date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit'
                            });
                            break;
                        case 'Toner_model':
                            cellValue = item.Toner_model || 'N/A';
                            break;
                        case 'code':
                            cellValue = item.code || 'N/A';
                            break;
                        case 'stock':
                            cellValue = item.stock || 'N/A';
                            break;
                        case 'color':
                            cellValue = item.color || 'N/A';
                            break;
                        case 'printer_model':
                            cellValue = item.printer_model || 'N/A';
                            break;
                        case 'printer_no':
                            cellValue = item.printer_no || 'N/A';
                            break;
                        case 'division':
                            cellValue = item.division || 'N/A';
                            break;
                        case 'section':
                            cellValue = item.section || 'N/A';
                            break;
                        case 'store':
                            cellValue = item.store || 'N/A';
                            break;
                        case 'request_officer':
                            cellValue = item.request_officer || 'N/A';
                            break;
                        case 'receiver_name':
                            cellValue = item.receiver_name || 'N/A';
                            break;
                        case 'receiver_emp_no':
                            cellValue = item.receiver_emp_no || 'N/A';
                            break;
                        case 'quantity':
                            cellValue = item.quantity || '0';
                            break;
                        case 'remarks':
                            cellValue = item.remarks || 'N/A';
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
