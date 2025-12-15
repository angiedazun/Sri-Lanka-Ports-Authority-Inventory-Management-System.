// Papers Issuing Page JavaScript Functions

// Store paper data globally
let selectedPaperData = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== PAPERS-ISSUING.JS LOADED ===');
    initializePapersIssuing();
});

function initializePapersIssuing() {
    // Initialize modal functionality
    initializeModals();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Ensure stock info box is hidden on page load
    const stockInfoBox = document.getElementById('stockInfoBox');
    if (stockInfoBox) {
        stockInfoBox.style.display = 'none';
        console.log('✅ Stock info box initialized (hidden)');
    }
}

// Update paper details when selected
function updatePaperDetails() {
    const paperSelect = document.getElementById('paperSelect');
    const selectedOption = paperSelect.options[paperSelect.selectedIndex];
    const stockInfoBox = document.getElementById('stockInfoBox');
    
    if (selectedOption && selectedOption.value) {
        selectedPaperData = {
            paper_id: selectedOption.value,
            paper_type: selectedOption.dataset.type || '',
            lot: selectedOption.dataset.lot || '',
            jct_quantity: parseInt(selectedOption.dataset.jct) || 0,
            uct_quantity: parseInt(selectedOption.dataset.uct) || 0
        };
        
        console.log('✅ Paper selected:', selectedPaperData);
        
        // Update display fields
        const paperTypeDisplay = document.getElementById('paperTypeDisplay');
        const lotDisplay = document.getElementById('lotDisplay');
        
        if (paperTypeDisplay) paperTypeDisplay.value = selectedPaperData.paper_type;
        if (lotDisplay) lotDisplay.value = selectedPaperData.lot;
        
        // Show and populate stock info box
        const jctQty = document.getElementById('jctQty');
        const uctQty = document.getElementById('uctQty');
        const totalQty = document.getElementById('totalQty');
        
        if (stockInfoBox && jctQty && uctQty && totalQty) {
            jctQty.textContent = selectedPaperData.jct_quantity;
            uctQty.textContent = selectedPaperData.uct_quantity;
            totalQty.textContent = selectedPaperData.jct_quantity + selectedPaperData.uct_quantity;
            
            // Show with animation
            stockInfoBox.style.display = 'flex';
            console.log('📦 Stock info displayed - JCT:', selectedPaperData.jct_quantity, 'UCT:', selectedPaperData.uct_quantity);
        }
        
        // Reset stock location selection
        const stockSelect = document.getElementById('stockSelect');
        if (stockSelect) stockSelect.selectedIndex = 0;
        
        // Reset quantity input
        const quantityInput = document.getElementById('quantityInput');
        if (quantityInput) {
            quantityInput.value = '';
            quantityInput.placeholder = 'Enter quantity';
            quantityInput.removeAttribute('data-max');
            quantityInput.style.borderColor = '';
            quantityInput.style.boxShadow = '';
        }
    } else {
        // Clear all fields and hide stock info box
        console.log('⚠️ No paper selected - hiding stock info');
        
        selectedPaperData = null;
        
        const paperTypeDisplay = document.getElementById('paperTypeDisplay');
        const lotDisplay = document.getElementById('lotDisplay');
        const codeDisplay = document.getElementById('codeDisplay');
        
        if (paperTypeDisplay) paperTypeDisplay.value = '';
        if (lotDisplay) lotDisplay.value = '';
        if (codeDisplay) codeDisplay.value = '';
        
        if (stockInfoBox) {
            stockInfoBox.style.display = 'none';
        }
        
        // Reset stock location
        const stockSelect = document.getElementById('stockSelect');
        if (stockSelect) stockSelect.selectedIndex = 0;
        
        // Reset quantity input
        const quantityInput = document.getElementById('quantityInput');
        if (quantityInput) {
            quantityInput.value = '';
            quantityInput.placeholder = 'Enter quantity';
            quantityInput.removeAttribute('data-max');
            quantityInput.style.borderColor = '';
            quantityInput.style.boxShadow = '';
        }
    }
}

// Update available stock when stock location is selected
function updateAvailableStock() {
    if (!selectedPaperData) {
        alert('Please select a paper first!');
        document.getElementById('stockSelect').selectedIndex = 0;
        return;
    }
    
    const stockSelect = document.getElementById('stockSelect');
    const quantityInput = document.getElementById('quantityInput');
    let maxStock = 0;
    
    if (stockSelect.value === 'JCT') {
        maxStock = selectedPaperData.jct_quantity;
    } else if (stockSelect.value === 'UCT') {
        maxStock = selectedPaperData.uct_quantity;
    }
    
    // Set max validation on quantity input
    if (quantityInput && maxStock > 0) {
        quantityInput.setAttribute('max', maxStock);
        quantityInput.setAttribute('data-max', maxStock);
        quantityInput.value = ''; // Clear previous value
        quantityInput.placeholder = `Max: ${maxStock} units available`;
        
        // Add real-time validation
        quantityInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            const max = parseInt(this.getAttribute('data-max'));
            
            if (value > max) {
                this.style.borderColor = '#dc3545';
                this.style.boxShadow = '0 0 0 0.2rem rgba(220,53,69,.25)';
                this.setCustomValidity(`Maximum available stock is ${max} units`);
            } else if (value < 1 && this.value !== '') {
                this.style.borderColor = '#dc3545';
                this.style.boxShadow = '0 0 0 0.2rem rgba(220,53,69,.25)';
                this.setCustomValidity('Quantity must be at least 1');
            } else {
                this.style.borderColor = '#28a745';
                this.style.boxShadow = '0 0 0 0.2rem rgba(40,167,69,.25)';
                this.setCustomValidity('');
            }
        });
    }
}

// Form validation
function initializeFormValidation() {
    const issueForms = document.querySelectorAll('form');
    issueForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const quantityInput = this.querySelector('#quantityInput');
            if (quantityInput && quantityInput.hasAttribute('data-max')) {
                const quantity = parseInt(quantityInput.value);
                const maxStock = parseInt(quantityInput.getAttribute('data-max'));
                
                if (quantity > maxStock) {
                    e.preventDefault();
                    alert(`Insufficient stock! Maximum available: ${maxStock} units`);
                    quantityInput.focus();
                    return false;
                }
                
                if (quantity < 1) {
                    e.preventDefault();
                    alert('Quantity must be at least 1');
                    quantityInput.focus();
                    return false;
                }
            }
        });
    });
}

// Modal Functions
function initializeModals() {
    // Modal close on outside click
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    };
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        
        // Reset form when opening issue modal
        if (modalId === 'issuePaperModal') {
            const paperSelect = document.getElementById('paperSelect');
            const stockSelect = document.getElementById('stockSelect');
            const stockInfoBox = document.getElementById('stockInfoBox');
            
            if (paperSelect) paperSelect.selectedIndex = 0;
            if (stockSelect) stockSelect.selectedIndex = 0;
            if (stockInfoBox) stockInfoBox.style.display = 'none';
            
            selectedPaperData = null;
            console.log('📋 Issue modal opened - form reset');
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Edit Issue Function
function editIssue(issue) {
    console.log('Editing issue:', issue);
    
    // Populate edit form fields
    document.getElementById('editIssueId').value = issue.issue_id || '';
    document.getElementById('editPaperId').value = issue.paper_id || '';
    document.getElementById('editPaperType').value = issue.paper_type || '';
    document.getElementById('editCode').value = issue.code || '';
    document.getElementById('editLot').value = issue.lot || '';
    document.getElementById('editStock').value = issue.stock || '';
    document.getElementById('editDivision').value = issue.division || '';
    document.getElementById('editSection').value = issue.section || '';
    document.getElementById('editStore').value = issue.store || '';
    document.getElementById('editRequestOfficer').value = issue.request_officer || '';
    document.getElementById('editReceiverName').value = issue.receiver_name || '';
    document.getElementById('editReceiverEmpNo').value = issue.receiver_emp_no || '';
    document.getElementById('editQuantity').value = issue.quantity || '';
    document.getElementById('editIssueDate').value = issue.issue_date || '';
    document.getElementById('editRemarks').value = issue.remarks || '';
    
    // Open edit modal
    openModal('editIssueModal');
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields!');
            }
        });
    });
}

// Print functionality
function printTable() {
    window.print();
}

// Export to CSV
function exportToCSV() {
    const table = document.querySelector('.data-table');
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => {
            csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
        });
        csv.push(csvRow.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'papers_issuing_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Refresh page
function refreshData() {
    location.reload();
}

// Delete confirmation
function confirmDelete(issueId) {
    if (confirm('Are you sure you want to delete this paper issue record? This action cannot be undone.')) {
        window.location.href = '?delete=' + issueId;
    }
}

// View issue details
function viewIssue(issue) {
    let details = `
        <div style="padding: 20px;">
            <h3>Paper Issue Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr><td style="padding: 8px; font-weight: bold;">Issue Date:</td><td style="padding: 8px;">${issue.issue_date}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Paper Type:</td><td style="padding: 8px;">${issue.paper_type || 'N/A'}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Code:</td><td style="padding: 8px;">${issue.code || 'N/A'}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Stock:</td><td style="padding: 8px;">${issue.stock}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">LOT:</td><td style="padding: 8px;">${issue.lot || 'N/A'}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Division:</td><td style="padding: 8px;">${issue.division}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Section:</td><td style="padding: 8px;">${issue.section}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Store:</td><td style="padding: 8px;">${issue.store || 'N/A'}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Request Officer:</td><td style="padding: 8px;">${issue.request_officer || 'N/A'}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Receiver Name:</td><td style="padding: 8px;">${issue.receiver_name || 'N/A'}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Receiver Emp No:</td><td style="padding: 8px;">${issue.receiver_emp_no || 'N/A'}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Quantity:</td><td style="padding: 8px;">${issue.quantity}</td></tr>
                <tr><td style="padding: 8px; font-weight: bold;">Remarks:</td><td style="padding: 8px;">${issue.remarks || 'No remarks'}</td></tr>
            </table>
        </div>
    `;
    
    let modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> View Paper Issue</h2>
                <span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span>
            </div>
            <div class="modal-body">${details}</div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}
