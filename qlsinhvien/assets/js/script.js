// Utility Functions
document.addEventListener('DOMContentLoaded', function() {
    console.log('Hệ thống Quản lý Sinh viên đã sẵn sàng!');
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Confirm before form submission for dangerous actions
function confirmAction(message) {
    return confirm(message || 'Bạn có chắc chắn muốn thực hiện hành động này?');
}

// Format number with Vietnamese locale
function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

// Format date to Vietnamese format
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

// Validate email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate phone number (Vietnamese format)
function validatePhone(phone) {
    const re = /^(0|\+84)[0-9]{9}$/;
    return re.test(phone);
}

// Show loading spinner
function showLoading() {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loading-spinner';
    loadingDiv.innerHTML = '<div class="spinner"></div>';
    loadingDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:9999;';
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    const loading = document.getElementById('loading-spinner');
    if (loading) {
        loading.remove();
    }
}

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;background:#333;color:#fff;border-radius:5px;z-index:10000;animation:slideInRight 0.3s;';
    
    if (type === 'success') toast.style.background = '#4CAF50';
    if (type === 'error') toast.style.background = '#f44336';
    if (type === 'warning') toast.style.background = '#ff9800';
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Table sort functionality
function sortTable(table, column, asc = true) {
    const dirModifier = asc ? 1 : -1;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const sortedRows = rows.sort((a, b) => {
        const aColText = a.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
        const bColText = b.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
        
        return aColText > bColText ? (1 * dirModifier) : (-1 * dirModifier);
    });
    
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    
    tbody.append(...sortedRows);
}

// Search in table
function searchTable(input, tableId) {
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let txtValue = tr[i].textContent || tr[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = '';
        } else {
            tr[i].style.display = 'none';
        }
    }
}

// Print function
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>In ấn</title>');
    printWindow.document.write('<link rel="stylesheet" href="/assets/css/style.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(element.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'data.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let row of rows) {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        for (let col of cols) {
            csvRow.push(col.textContent);
        }
        csv.push(csvRow.join(','));
    }
    
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let valid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#f44336';
            valid = false;
        } else {
            input.style.borderColor = '#e0e0e0';
        }
    });
    
    return valid;
}

// Auto-save form data to localStorage
function autoSaveForm(formId, key) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', () => {
            const formData = {};
            inputs.forEach(inp => {
                formData[inp.name] = inp.value;
            });
            localStorage.setItem(key, JSON.stringify(formData));
        });
    });
    
    // Load saved data
    const savedData = localStorage.getItem(key);
    if (savedData) {
        const data = JSON.parse(savedData);
        inputs.forEach(input => {
            if (data[input.name]) {
                input.value = data[input.name];
            }
        });
    }
}

// Clear localStorage on successful form submission
function clearAutoSave(key) {
    localStorage.removeItem(key);
}

// Smooth scroll to element
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Toggle sidebar on mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// Debounce function for search
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

// Initialize tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.cssText = 'position:absolute;background:#333;color:#fff;padding:0.5rem;border-radius:3px;font-size:0.875rem;z-index:1000;';
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                delete this._tooltip;
            }
        });
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"], input[name="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // ESC to close modals
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
});

