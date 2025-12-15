// Toner Master Page JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    initializeTonerMaster();
});

function initializeTonerMaster() {
    // Initialize search functionality
    initializeSearch();
    
    // Initialize modal functionality
    initializeModals();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize stock level animations
    initializeStockAnimations();
    
    // Initialize auto-complete
    initializeAutoComplete();
    
    // Initialize date validation
    initializeDateValidation();
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
    const colorFilter = document.getElementById('colorFilter');
    const locationFilter = document.getElementById('locationFilter');
    
    if (!searchInput) return;
    
    const searchValue = searchInput.value.toLowerCase();
    const colorValue = colorFilter ? colorFilter.value.toLowerCase() : '';
    const locationValue = locationFilter ? locationFilter.value.toLowerCase() : '';
    
    const table = document.getElementById('tonerTable');
    if (!table) return;
    
    const tbody = table.getElementsByTagName('tbody')[0];
    if (!tbody) return;
    
    const rows = tbody.getElementsByTagName('tr');
    let visibleRows = 0;
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        
        if (cells.length > 0) {
            // Get cell values based on new table structure
            const tonerId = cells[0].textContent.toLowerCase();
            const tonerModel = cells[1].textContent.toLowerCase();
            const compatiblePrinters = cells[2].textContent.toLowerCase();
            const color = cells[3].textContent.toLowerCase();
            const jctStock = parseInt(cells[4].querySelector('.stock-number').textContent);
            const uctStock = parseInt(cells[5].querySelector('.stock-number').textContent);
            
            // Check matches
            const matchesSearch = !searchValue || 
                tonerId.includes(searchValue) || 
                tonerModel.includes(searchValue) || 
                compatiblePrinters.includes(searchValue);
            
            const matchesColor = !colorValue || color.includes(colorValue);
            
            let matchesLocation = true;
            if (locationValue) {
                switch(locationValue) {
                    case 'jct':
                        matchesLocation = jctStock > 0 && uctStock === 0;
                        break;
                    case 'uct':
                        matchesLocation = uctStock > 0 && jctStock === 0;
                        break;
                    case 'both':
                        matchesLocation = jctStock > 0 && uctStock > 0;
                        break;
                }
            }
            
            // Show/hide row
            const isVisible = matchesSearch && matchesColor && matchesLocation;
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) {
                visibleRows++;
                // Add highlight effect for search terms
                highlightSearchTerms(row, searchValue);
            }
        }
    }
    
    // Show/hide empty state
    updateEmptyState(visibleRows === 0);
}

function highlightSearchTerms(row, searchTerm) {
    if (!searchTerm) return;
    
    const cells = row.getElementsByTagName('td');
    const searchableColumns = [0, 1, 2]; // toner_id, toner_model, compatible_printers
    
    searchableColumns.forEach(index => {
        if (cells[index]) {
            const originalText = cells[index].textContent;
            const highlightedText = originalText.replace(
                new RegExp(`(${searchTerm})`, 'gi'),
                '<mark>$1</mark>'
            );
            if (originalText !== highlightedText) {
                cells[index].innerHTML = highlightedText;
            }
        }
    });
}

function updateEmptyState(isEmpty) {
    const tableContainer = document.querySelector('.table-responsive');
    let emptyState = document.querySelector('.search-empty-state');
    
    if (isEmpty) {
        if (!emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'search-empty-state empty-state';
            emptyState.innerHTML = `
                <i class="fas fa-search"></i>
                <h3>No Results Found</h3>
                <p>No toner items match your current search criteria.</p>
                <button class="btn btn-secondary" onclick="clearAllFilters()">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            `;
            tableContainer.appendChild(emptyState);
        }
        emptyState.style.display = 'block';
    } else if (emptyState) {
        emptyState.style.display = 'none';
    }
}

function clearAllFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('colorFilter').value = '';
    document.getElementById('locationFilter').value = '';
    
    // Remove active class from filter buttons
    document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
    
    filterTable();
}

// Stock Level Filter Functions
function filterByStock(type) {
    // Remove active class from all filter buttons
    const filterButtons = document.querySelectorAll('.btn-filter');
    filterButtons.forEach(btn => btn.classList.remove('active'));
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    const table = document.getElementById('tonerTable');
    if (!table) return;
    
    const tbody = table.getElementsByTagName('tbody')[0];
    if (!tbody) return;
    
    const rows = tbody.getElementsByTagName('tr');
    let visibleRows = 0;
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        
        if (cells.length >= 8) {
            const jctStock = parseInt(cells[4].querySelector('.stock-number').textContent);
            const uctStock = parseInt(cells[5].querySelector('.stock-number').textContent);
            const totalStock = parseInt(cells[6].querySelector('.stock-number').textContent);
            const reorderLevel = parseInt(cells[7].textContent.trim());
            
            let showRow = false;
            
            switch(type) {
                case 'all':
                    showRow = true;
                    break;
                case 'good':
                    showRow = totalStock > reorderLevel;
                    break;
                case 'low':
                    showRow = totalStock <= reorderLevel && totalStock > 0;
                    break;
                case 'out':
                    showRow = totalStock === 0;
                    break;
            }
            
            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleRows++;
        }
    }
    
    updateEmptyState(visibleRows === 0);
}

// Modal Functions
function initializeModals() {
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    
    // Form validation for add toner modal
    const addForm = document.querySelector('#addTonerModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            if (!validateTonerForm(this)) {
                e.preventDefault();
            }
        });
    }
}

function validateTonerForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            showFieldError(field, 'This field is required');
        } else {
            field.classList.remove('error');
            hideFieldError(field);
        }
    });
    
    // Validate numeric fields
    const numericFields = form.querySelectorAll('input[type="number"]');
    numericFields.forEach(field => {
        if (field.value && parseFloat(field.value) < 0) {
            isValid = false;
            field.classList.add('error');
            showFieldError(field, 'Value must be non-negative');
        }
    });
    
    // Validate reorder level
    const reorderLevel = form.querySelector('input[name="reorder_level"]');
    if (reorderLevel && reorderLevel.value && parseInt(reorderLevel.value) < 1) {
        isValid = false;
        reorderLevel.classList.add('error');
        showFieldError(reorderLevel, 'Reorder level must be at least 1');
    }
    
    // Validate purchase date
    const purchaseDate = form.querySelector('input[name="purchase_date"]');
    if (purchaseDate && purchaseDate.value) {
        const selectedDate = new Date(purchaseDate.value);
        selectedDate.setHours(0, 0, 0, 0);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (selectedDate > today) {
            isValid = false;
            purchaseDate.classList.add('error');
            showFieldError(purchaseDate, 'Purchase date cannot be in the future');
        }
    }
    
    return isValid;
}

function showFieldError(field, message) {
    hideFieldError(field); // Remove existing error
    
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    field.parentNode.appendChild(errorElement);
}

function hideFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// Initialize real-time date validation
function initializeDateValidation() {
    // Add event listener to purchase date fields
    document.addEventListener('change', function(e) {
        if (e.target.name === 'purchase_date') {
            const dateInput = e.target;
            const selectedDate = new Date(dateInput.value);
            selectedDate.setHours(0, 0, 0, 0);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Clear any existing errors first
            dateInput.classList.remove('error');
            hideFieldError(dateInput);
            
            // Only show error if date is in the future
            if (selectedDate > today) {
                dateInput.classList.add('error');
                showFieldError(dateInput, 'Purchase date cannot be in the future');
            }
        }
    });
}

// Tooltip Functions
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const title = element.getAttribute('title');
    
    if (title) {
        // Hide browser default tooltip
        element.setAttribute('data-original-title', title);
        element.removeAttribute('title');
        
        // Create custom tooltip
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = title;
        
        document.body.appendChild(tooltip);
        
        // Position tooltip
        const rect = element.getBoundingClientRect();
        tooltip.style.position = 'absolute';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
        tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
        
        element._tooltip = tooltip;
    }
}

function hideTooltip(event) {
    const element = event.target;
    
    if (element._tooltip) {
        element._tooltip.remove();
        delete element._tooltip;
    }
    
    // Restore original title
    const originalTitle = element.getAttribute('data-original-title');
    if (originalTitle) {
        element.setAttribute('title', originalTitle);
        element.removeAttribute('data-original-title');
    }
}

// Stock Level Animation Functions
function initializeStockAnimations() {
    const stockIndicators = document.querySelectorAll('.stock-indicator');
    
    stockIndicators.forEach(indicator => {
        if (indicator.classList.contains('stock-critical')) {
            // Add pulsing animation for critical stock
            indicator.style.animation = 'pulse 2s infinite';
        }
    });
}

// Auto-complete Functions
function initializeAutoComplete() {
    const brandSelect = document.querySelector('select[name="brand"]');
    const modelInput = document.querySelector('input[name="model"]');
    
    if (brandSelect && modelInput) {
        brandSelect.addEventListener('change', function() {
            updateModelSuggestions(this.value, modelInput);
        });
    }
}

function updateModelSuggestions(brand, modelInput) {
    const modelSuggestions = {
        'HP': ['LaserJet Pro', 'Color LaserJet', 'OfficeJet', 'DeskJet', 'PageWide'],
        'Canon': ['PIXMA', 'ImageCLASS', 'Color imageCLASS', 'MAXIFY'],
        'Epson': ['WorkForce', 'Expression', 'EcoTank', 'SureColor'],
        'Brother': ['HL-L', 'MFC-L', 'DCP-L', 'QL'],
        'Samsung': ['ProXpress', 'Xpress', 'MultiXpress']
    };
    
    const suggestions = modelSuggestions[brand] || [];
    
    // Create datalist for autocomplete
    let datalist = document.getElementById('modelSuggestions');
    if (!datalist) {
        datalist = document.createElement('datalist');
        datalist.id = 'modelSuggestions';
        document.body.appendChild(datalist);
        modelInput.setAttribute('list', 'modelSuggestions');
    }
    
    datalist.innerHTML = '';
    suggestions.forEach(suggestion => {
        const option = document.createElement('option');
        option.value = suggestion;
        datalist.appendChild(option);
    });
}

// Export Functions
function exportToCSV(tableId, filename = 'toner_master_export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const csvContent = Array.from(rows).map(row => {
        const cells = row.querySelectorAll('th, td');
        return Array.from(cells).map(cell => {
            // Get text content and clean it
            let text = cell.textContent.trim();
            // Remove action buttons content
            if (cell.classList.contains('table-actions-cell')) {
                return 'Actions';
            }
            // Escape quotes and wrap in quotes if contains comma
            if (text.includes(',') || text.includes('"')) {
                text = '"' + text.replace(/"/g, '""') + '"';
            }
            return text;
        }).join(',');
    }).join('\n');
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    // Show success message
    showNotification('Export completed successfully!', 'success');
}

// Print Functions
function printTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    // Create print window
    const printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Toner Master Report - ${new Date().toLocaleDateString()}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; text-align: center; margin-bottom: 30px; }
                table { border-collapse: collapse; width: 100%; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #667eea; color: white; font-weight: bold; }
                tbody tr:nth-child(even) { background-color: #f9f9f9; }
                .print-info { text-align: center; margin-top: 30px; font-size: 10px; color: #666; }
                .table-actions-cell { display: none; }
            </style>
        </head>
        <body>
            <h1>🖨️ Toner Master Inventory Report</h1>
            <p style="text-align: center; margin-bottom: 20px;">
                Generated on: ${new Date().toLocaleString()} | 
                Total Items: ${table.querySelectorAll('tbody tr').length}
            </p>
            ${table.outerHTML}
            <div class="print-info">
                <p>SLPA System - Supply Logistics & Procurement Administration</p>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

// Delete Confirmation
function confirmDelete(id) {
    const confirmation = confirm('⚠️ Delete Toner Item\n\nAre you sure you want to delete this toner item?\nThis action cannot be undone.');
    
    if (confirmation) {
        // Show loading state
        showNotification('Deleting toner item...', 'info');
        
        // Redirect to delete
        setTimeout(() => {
            window.location.href = '?delete=' + id;
        }, 500);
    }
}

// Notification System
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icon = type === 'success' ? 'check-circle' : 
                 type === 'error' ? 'exclamation-triangle' : 
                 type === 'warning' ? 'exclamation-triangle' : 'info-circle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Utility Functions
function openModal(modalId) {
    console.log('Opening modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Scroll modal to top
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.scrollTop = 0;
        }
        
        // For add toner modal, set today's date
        if (modalId === 'addTonerModal') {
            const purchaseDateInput = modal.querySelector('input[name="purchase_date"]');
            if (purchaseDateInput) {
                const today = new Date();
                const formattedDate = today.toISOString().split('T')[0];
                purchaseDateInput.value = formattedDate;
                purchaseDateInput.max = formattedDate; // Prevent future dates
            }
        }
        
        // For view modal, ensure proper positioning
        if (modalId === 'viewTonerModal') {
            modal.style.paddingTop = '20px';
            modal.style.paddingBottom = '20px';
        }
        
        // Focus first input (but not for view modal)
        if (modalId !== 'viewTonerModal') {
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
        
        // Add click outside to close functionality
        const clickHandler = function(e) {
            if (e.target === modal) {
                closeModal(modalId);
                modal.removeEventListener('click', clickHandler);
            }
        };
        modal.addEventListener('click', clickHandler);
        
        // Add ESC key to close functionality
        const keyHandler = function(e) {
            if (e.key === 'Escape') {
                closeModal(modalId);
                document.removeEventListener('keydown', keyHandler);
            }
        };
        document.addEventListener('keydown', keyHandler);
        
        console.log('Modal opened successfully:', modalId);
    } else {
        console.error('Modal not found:', modalId);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Clear form if exists
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            
            // Clear validation errors
            const errorElements = form.querySelectorAll('.field-error');
            errorElements.forEach(error => error.remove());
            
            const errorFields = form.querySelectorAll('.error');
            errorFields.forEach(field => field.classList.remove('error'));
        }
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    // Ctrl + N to add new toner
    if (event.ctrlKey && event.key === 'n') {
        event.preventDefault();
        openModal('addTonerModal');
    }
    
    // Escape to close modals
    if (event.key === 'Escape') {
        const openModal = document.querySelector('.modal[style*="block"]');
        if (openModal) {
            closeModal(openModal.id);
        }
    }
    
    // Ctrl + F to focus search
    if (event.ctrlKey && event.key === 'f') {
        event.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
        }
    }
});

// Edit Modal Functions
function openEditModal(index, tonerId) {
    console.log('openEditModal called with:', {index, tonerId});
    
    // Validate tonerId
    if (!tonerId) {
        console.error('tonerId is missing!');
        alert('Error: Unable to identify toner. Please refresh the page and try again.');
        return;
    }
    
    // Get toner data from the table row
    const table = document.getElementById('tonerTable');
    if (!table) {
        console.error('tonerTable not found');
        return;
    }
    
    const tbody = table.getElementsByTagName('tbody')[0];
    if (!tbody) {
        console.error('tbody not found');
        return;
    }
    
    const rows = tbody.getElementsByTagName('tr');
    if (!rows[index]) {
        console.error('Row not found at index:', index);
        return;
    }
    
    const row = rows[index];
    const cells = row.getElementsByTagName('td');
    
    // Extract data from table cells (excluding actions column)
    const tonerModel = cells[0].textContent.trim();
    const compatiblePrinters = cells[1].querySelector('div').title || cells[1].textContent.trim();
    const color = cells[2].querySelector('.badge').textContent.trim();
    const jctStock = cells[3].querySelector('.stock-number').textContent.trim();
    const uctStock = cells[4].querySelector('.stock-number').textContent.trim();
    const reorderLevel = cells[6].textContent.trim();
    const purchaseDate = cells[7].textContent.trim();
    
    console.log('Extracted data:', {tonerModel, jctStock, uctStock, tonerId});
    
    // Populate edit form with toner_id
    const editTonerIdField = document.getElementById('editTonerId');
    if (editTonerIdField) {
        editTonerIdField.value = tonerId;
    } else {
        console.error('editTonerId field not found');
    }
    
    document.getElementById('editTonerModel').value = tonerModel;
    document.getElementById('editCompatiblePrinters').value = compatiblePrinters;
    document.getElementById('editColor').value = color;
    document.getElementById('editJctStock').value = jctStock;
    document.getElementById('editUctStock').value = uctStock;
    document.getElementById('editReorderLevel').value = reorderLevel;
    
    // Convert date format from "MMM dd, yyyy" to "yyyy-mm-dd"
    try {
        const dateObj = new Date(purchaseDate);
        const formattedDate = dateObj.toISOString().split('T')[0];
        document.getElementById('editPurchaseDate').value = formattedDate;
    } catch (e) {
        console.error('Date conversion error:', e);
        document.getElementById('editPurchaseDate').value = '';
    }
    
    // Open the edit modal
    console.log('Opening edit modal...');
    openModal('editTonerModal');
}

// Enhanced form validation for both add and edit forms
function validateForm(formElement) {
    const requiredFields = formElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            showFieldError(field, 'This field is required');
        } else {
            field.classList.remove('error');
            hideFieldError(field);
        }
    });
    
    // Validate numeric fields
    const numericFields = formElement.querySelectorAll('input[type="number"]');
    numericFields.forEach(field => {
        if (field.value && parseFloat(field.value) < 0) {
            isValid = false;
            field.classList.add('error');
            showFieldError(field, 'Value must be non-negative');
        }
    });
    
    // Validate reorder level
    const reorderLevel = formElement.querySelector('input[name="reorder_level"]');
    if (reorderLevel && reorderLevel.value && parseInt(reorderLevel.value) < 1) {
        isValid = false;
        reorderLevel.classList.add('error');
        showFieldError(reorderLevel, 'Reorder level must be at least 1');
    }
    
    // Validate purchase date
    const purchaseDate = formElement.querySelector('input[name="purchase_date"]');
    if (purchaseDate && purchaseDate.value) {
        const selectedDate = new Date(purchaseDate.value);
        selectedDate.setHours(0, 0, 0, 0);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (selectedDate > today) {
            isValid = false;
            purchaseDate.classList.add('error');
            showFieldError(purchaseDate, 'Purchase date cannot be in the future');
        }
    }
    
    return isValid;
}

// View Toner Details Function
function viewToner(tonerIndex) {
    console.log('viewToner called with index:', tonerIndex);
    
    try {
        // Get toner data from the table
        const table = document.getElementById('tonerTable');
        if (!table) {
            console.error('Table not found');
            alert('Table not found');
            return;
        }
        
        const tbody = table.getElementsByTagName('tbody')[0];
        if (!tbody) {
            console.error('Table body not found');
            alert('Table body not found');
            return;
        }
        
        const rows = tbody.getElementsByTagName('tr');
        console.log('Found rows:', rows.length);
        
        if (tonerIndex >= rows.length || tonerIndex < 0) {
            console.error('Index out of bounds:', tonerIndex, 'max:', rows.length);
            alert('Invalid toner index');
            return;
        }
        
        const row = rows[tonerIndex];
        const cells = row.getElementsByTagName('td');
        
        if (cells.length === 0) {
            console.error('No cells found in row');
            alert('No data found in row');
            return;
        }
        
        console.log('Row cells:', cells.length);
        
        // Extract data from table cells with enhanced error checking
        const tonerModel = cells[0] ? cells[0].textContent.trim() : 'N/A';
        
        // Handle compatible printers (might have HTML formatting)
        const compatiblePrinters = cells[1] ? cells[1].textContent.trim() : 'N/A';
        
        // Extract color from badge or plain text
        const colorBadge = cells[2] ? cells[2].querySelector('.badge') : null;
        const color = colorBadge ? colorBadge.textContent.trim() : 
                     (cells[2] ? cells[2].textContent.trim() : 'N/A');
        
        // Extract stock numbers from stock-number spans or fallback to text content
        const jctStockElement = cells[3] ? cells[3].querySelector('.stock-number') : null;
        const jctStock = jctStockElement ? jctStockElement.textContent.trim() : 
                        (cells[3] ? cells[3].textContent.trim().replace(/[^0-9]/g, '') || '0' : '0');
        
        const uctStockElement = cells[4] ? cells[4].querySelector('.stock-number') : null;
        const uctStock = uctStockElement ? uctStockElement.textContent.trim() : 
                        (cells[4] ? cells[4].textContent.trim().replace(/[^0-9]/g, '') || '0' : '0');
        
        const totalStockElement = cells[5] ? cells[5].querySelector('.stock-number') : null;
        const totalStock = totalStockElement ? totalStockElement.textContent.trim() : 
                          (cells[5] ? cells[5].textContent.trim().replace(/[^0-9]/g, '') || '0' : '0');
        
        // Extract other data
        const reorderLevel = cells[6] ? cells[6].textContent.trim() : '0';
        const purchaseDate = cells[7] ? cells[7].textContent.trim() : 'N/A';
        
        // Extract status from status badge or fallback to text content
        const statusBadge = cells[8] ? cells[8].querySelector('.status-badge') : null;
        const status = statusBadge ? statusBadge.textContent.trim() : 
                      (cells[8] ? cells[8].textContent.trim() : 'N/A');
        
        console.log('Extracted data:', {
            tonerModel, 
            compatiblePrinters, 
            color, 
            jctStock, 
            uctStock, 
            totalStock, 
            reorderLevel, 
            purchaseDate, 
            status
        });
        
        // Store index for potential edit function
        window.currentTonerIndex = tonerIndex;
        
        // Populate the modal with data
        populateTonerDetailsModal(tonerModel, compatiblePrinters, color, jctStock, uctStock, totalStock, reorderLevel, purchaseDate, status);
        
        // Show the modal
        openModal('viewTonerModal');
        
    } catch (error) {
        console.error('Error in viewToner:', error);
        alert('Error loading toner details: ' + error.message);
    }
}

function populateTonerDetailsModal(tonerModel, compatiblePrinters, color, jctStock, uctStock, totalStock, reorderLevel, purchaseDate, status) {
    console.log('Populating modal with data:', {
        tonerModel, compatiblePrinters, color, jctStock, uctStock, totalStock, reorderLevel, purchaseDate, status
    });
    
    // Populate toner information with null checks
    const elements = {
        'detail-toner-model': tonerModel || 'N/A',
        'detail-jct-stock': `JCT: ${jctStock || '0'}`,
        'detail-uct-stock': `UCT: ${uctStock || '0'}`,
        'detail-total-stock': `Total: ${totalStock || '0'}`,
        'detail-purchase-date': purchaseDate || 'N/A',
        'detail-reorder-level': reorderLevel || '0',
        'detail-status': status || 'N/A'
    };
    
    // Set text content for each element
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        } else {
            console.warn(`Element not found: ${id}`);
        }
    });
    
    // Handle color display
    const colorText = color || 'N/A';
    const colorDisplay = document.getElementById('detail-color-display');
    const colorTextElement = document.getElementById('detail-color');
    
    if (colorTextElement && colorDisplay) {
        colorTextElement.textContent = colorText.toUpperCase();
        
        // Remove existing color classes
        colorDisplay.className = 'color-display';
        
        // Add appropriate color class
        const colorClass = colorText.toLowerCase().replace(/[^a-z]/g, '');
        if (['black', 'cyan', 'magenta', 'yellow', 'tricolor'].includes(colorClass)) {
            colorDisplay.classList.add(colorClass);
        }
    }
    
    // Handle compatible printers
    const printersContainer = document.getElementById('detail-printers-list');
    if (printersContainer) {
        if (compatiblePrinters && compatiblePrinters !== 'N/A' && compatiblePrinters.trim() !== '') {
            const printers = compatiblePrinters.split(/[,;]/).map(p => p.trim()).filter(p => p.length > 0);
            
            if (printers.length > 0) {
                printersContainer.innerHTML = printers.map(printer => 
                    `<span class="printer-tag">${escapeHtml(printer)}</span>`
                ).join('');
            } else {
                printersContainer.innerHTML = '<span class="printer-tag">No compatible printers listed</span>';
            }
        } else {
            printersContainer.innerHTML = '<span class="printer-tag">No compatible printers listed</span>';
        }
    }
    
    console.log('Modal populated successfully');
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Function to open edit modal from view modal
function openEditModalFromView() {
    if (typeof window.currentTonerIndex !== 'undefined') {
        openEditModal(window.currentTonerIndex);
    }
}

/*
// Old dynamic modal function - replaced with static modal
function showTonerDetailsModal(tonerModel, compatiblePrinters, color, jctStock, uctStock, totalStock, reorderLevel, purchaseDate, status, tonerIndex) {
    // This function is now replaced by populateTonerDetailsModal and the static modal in PHP
    console.log('showTonerDetailsModal called but using static modal instead');
}

// Old function to add modal styles dynamically - no longer needed
function addViewModalStyles() {
    // Styles are now included in the PHP file
    console.log('addViewModalStyles called but styles are now static');
}

// Old function to create printer tags - logic moved to populateTonerDetailsModal
function createPrinterTags(compatiblePrinters) {
    // Logic moved to populateTonerDetailsModal
    console.log('createPrinterTags called but logic moved to populateTonerDetailsModal');
}
*/