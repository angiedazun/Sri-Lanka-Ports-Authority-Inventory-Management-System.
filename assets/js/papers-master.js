// Papers Master JavaScript - Exact Copy of Toner Master Functionality

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
});

// Filter table function
function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const sizeFilter = document.getElementById('sizeFilter').value.toLowerCase();
    const bundleFilter = document.getElementById('bundleFilter').value.toLowerCase();
    const locationFilter = document.getElementById('locationFilter').value.toLowerCase();
    
    const table = document.getElementById('paperTable');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        
        if (cells.length > 0) {
            const paperType = cells[0].textContent.toLowerCase();
            const paperSize = cells[1].textContent.toLowerCase();
            const bundleType = cells[3].textContent.toLowerCase();
            const jctStock = parseInt(cells[5].textContent.trim()) || 0;
            const uctStock = parseInt(cells[6].textContent.trim()) || 0;
            
            const matchesSearch = paperType.includes(searchInput) || paperSize.includes(searchInput);
            const matchesSize = !sizeFilter || paperSize.includes(sizeFilter);
            const matchesBundle = !bundleFilter || bundleType.includes(bundleFilter);
            
            let matchesLocation = true;
            if (locationFilter === 'jct') {
                matchesLocation = jctStock > 0;
            } else if (locationFilter === 'uct') {
                matchesLocation = uctStock > 0;
            } else if (locationFilter === 'both') {
                matchesLocation = jctStock > 0 && uctStock > 0;
            }
            
            if (matchesSearch && matchesSize && matchesBundle && matchesLocation) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }
}

// Filter by stock level
function filterByStock(type) {
    // Remove active class from all filter buttons
    const filterButtons = document.querySelectorAll('.btn-filter');
    filterButtons.forEach(btn => btn.classList.remove('active'));
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    const table = document.getElementById('paperTable');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const stockCell = row.getElementsByTagName('td')[7];
        const minStockCell = row.getElementsByTagName('td')[8];
        
        if (stockCell && minStockCell) {
            const currentStock = parseInt(stockCell.textContent.trim());
            const minStock = parseInt(minStockCell.textContent.trim());
            
            let showRow = false;
            
            switch(type) {
                case 'all':
                    showRow = true;
                    break;
                case 'good':
                    showRow = currentStock > minStock;
                    break;
                case 'low':
                    showRow = currentStock <= minStock && currentStock > 0;
                    break;
                case 'out':
                    showRow = currentStock === 0;
                    break;
            }
            
            row.style.display = showRow ? '' : 'none';
        }
    }
}

// View paper details - Exact Toner Master Style
function viewPaper(index) {
    if (typeof papersData === 'undefined' || !papersData[index]) {
        alert('Paper data not found!');
        return;
    }
    
    const paper = papersData[index];
    const totalStock = (paper.jct_stock || 0) + (paper.uct_stock || 0);
    
    // Fill modal with data
    document.getElementById('view_paper_type').textContent = paper.paper_type || 'N/A';
    
    // Stock badges with colored backgrounds
    document.getElementById('view_jct_stock').textContent = 'JCT: ' + (paper.jct_stock || 0);
    document.getElementById('view_uct_stock').textContent = 'UCT: ' + (paper.uct_stock || 0);
    document.getElementById('view_total_stock').textContent = 'Total: ' + totalStock;
    
    // Paper type as color badge (using paper_type instead of paper_size)
    const sizeBadge = document.getElementById('view_paper_size_badge');
    sizeBadge.textContent = paper.paper_type || 'N/A';
    sizeBadge.className = 'color-display';
    
    // Purchase date formatting
    if (paper.purchase_date) {
        const purchaseDate = new Date(paper.purchase_date);
        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        document.getElementById('view_purchase_date').textContent = 
            monthNames[purchaseDate.getMonth()] + ' ' + 
            String(purchaseDate.getDate()).padStart(2, '0') + ', ' + 
            purchaseDate.getFullYear();
    } else {
        document.getElementById('view_purchase_date').textContent = 'N/A';
    }
    
    // Reorder level
    document.getElementById('view_reorder_level').textContent = paper.reorder_level || 0;
    
    // Set status with proper styling
    const statusEl = document.getElementById('view_status');
    if (totalStock === 0) {
        statusEl.textContent = '🔴 OUT OF STOCK';
        statusEl.style.background = '#f8d7da';
        statusEl.style.color = '#721c24';
        statusEl.style.border = '1px solid #f5c6cb';
    } else if (totalStock <= (paper.reorder_level || 0)) {
        statusEl.textContent = '🟡 LOW STOCK';
        statusEl.style.background = '#fff3cd';
        statusEl.style.color = '#856404';
        statusEl.style.border = '1px solid #ffc107';
    } else {
        statusEl.textContent = '🟢 IN STOCK';
        statusEl.style.background = '#d4edda';
        statusEl.style.color = '#155724';
        statusEl.style.border = '1px solid #c3e6cb';
    }
    
    // Store current index for edit button
    window.currentViewIndex = index;
    
    openModal('viewPaperModal');
}

// Open edit modal
function openEditModal(index) {
    if (typeof papersData === 'undefined' || !papersData[index]) {
        alert('Paper data not found!');
        return;
    }
    
    const paper = papersData[index];
    
    // Fill form with data - only set fields that exist
    document.getElementById('editIndex').value = index;
    document.getElementById('edit_paper_type').value = paper.paper_type || '';
    document.getElementById('edit_reorder_level').value = paper.reorder_level || '';
    document.getElementById('edit_jct_stock').value = paper.jct_stock || '';
    document.getElementById('edit_uct_stock').value = paper.uct_stock || '';
    document.getElementById('edit_purchase_date').value = paper.purchase_date || '';
    
    openModal('editPaperModal');
}

// Open edit modal from view modal
function openEditModalFromView() {
    if (typeof window.currentViewIndex !== 'undefined' && window.currentViewIndex >= 0) {
        openEditModal(window.currentViewIndex);
    }
}

// Export to CSV
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('Table not found!');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length - 1; j++) { // Exclude Actions column
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Generate print report function
function generatePrintReport() {
    // Get form values
    const fromDate = document.getElementById('print_from_date').value;
    const toDate = document.getElementById('print_to_date').value;
    const filterMonth = document.getElementById('print_filter_month').value;
    const filterYear = document.getElementById('print_filter_year').value;
    const includeZeroStock = document.querySelector('input[name="include_zero_stock"]').checked;
    const includeSummary = document.querySelector('input[name="include_summary"]').checked;
    
    // Build URL parameters
    const params = new URLSearchParams();
    if (fromDate) params.append('from_date', fromDate);
    if (toDate) params.append('to_date', toDate);
    if (filterMonth) params.append('filter_month', filterMonth);
    if (filterYear) params.append('filter_year', filterYear);
    if (includeZeroStock) params.append('include_zero_stock', '1');
    if (includeSummary) params.append('include_summary', '1');
    
    // Create the print URL
    const printUrl = 'papers_master_print.php?' + params.toString();
    
    // Open print page in new window
    window.open(printUrl, '_blank');
    
    // Close the modal
    closeModal('printDateModal');
}

// Initialize filter buttons on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set first filter button as active by default
    const firstFilterBtn = document.querySelector('.btn-filter');
    if (firstFilterBtn) {
        firstFilterBtn.classList.add('active');
    }
});
