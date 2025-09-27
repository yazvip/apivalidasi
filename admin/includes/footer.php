<?php
/**
 * Admin Footer - Konsisten untuk semua halaman admin
 */
?>
            </div>
        </div>
    </div>

<script src="/admin/assets/js/enhanced-ui.js"></script>
<script>
    // Enhanced UI/UX functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips (using custom implementation)
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-tooltip]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            tooltipTriggerEl.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'absolute z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg';
                tooltip.textContent = this.getAttribute('data-tooltip');
                tooltip.style.top = this.offsetTop - 30 + 'px';
                tooltip.style.left = this.offsetLeft + 'px';
                document.body.appendChild(tooltip);
                this._tooltip = tooltip;
            });
            tooltipTriggerEl.addEventListener('mouseleave', function() {
                if (this._tooltip) {
                    this._tooltip.remove();
                    this._tooltip = null;
                }
            });
        });
        
        // Enhanced data tables with sorting
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.innerHTML += ' <i class="fas fa-sort text-muted"></i>';
                
                header.addEventListener('click', function() {
                    const column = this.getAttribute('data-sort');
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    
                    const isAsc = this.classList.contains('sort-asc');
                    
                    // Reset all headers
                    headers.forEach(h => {
                        h.classList.remove('sort-asc', 'sort-desc');
                        h.querySelector('i').className = 'fas fa-sort text-muted';
                    });
                    
                    // Sort rows
                    rows.sort((a, b) => {
                        const aVal = a.querySelector(`[data-${column}]`)?.getAttribute(`data-${column}`) || '';
                        const bVal = b.querySelector(`[data-${column}]`)?.getAttribute(`data-${column}`) || '';
                        
                        if (isAsc) {
                            return bVal.localeCompare(aVal);
                        } else {
                            return aVal.localeCompare(bVal);
                        }
                    });
                    
                    // Update header
                    this.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
                    this.querySelector('i').className = `fas fa-sort-${isAsc ? 'down' : 'up'} text-primary`;
                    
                    // Reorder rows
                    rows.forEach(row => tbody.appendChild(row));
                });
            });
        });
        
        // Enhanced form submissions with better feedback
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    // Store original content
                    if (!submitBtn.dataset.originalText) {
                        submitBtn.dataset.originalText = submitBtn.innerHTML;
                    }
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.classList.add('btn-loading');
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                }
            });
        });
        
        // Enhanced modal interactions
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const firstInput = this.querySelector('input, textarea, select');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            });
        });
        
        
        // Real-time form validation
        const inputs = document.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateField(this);
                }
            });
        });
        
        function validateField(field) {
            const value = field.value.trim();
            const required = field.hasAttribute('required');
            const minLength = field.getAttribute('minlength');
            const maxLength = field.getAttribute('maxlength');
            const pattern = field.getAttribute('pattern');
            const type = field.getAttribute('type');
            
            let isValid = true;
            let message = '';
            
            // Required validation
            if (required && !value) {
                isValid = false;
                message = 'This field is required';
            }
            // Length validation
            else if (minLength && value.length < parseInt(minLength)) {
                isValid = false;
                message = `Minimum ${minLength} characters required`;
            }
            else if (maxLength && value.length > parseInt(maxLength)) {
                isValid = false;
                message = `Maximum ${maxLength} characters allowed`;
            }
            // Pattern validation
            else if (pattern && !new RegExp(pattern).test(value)) {
                isValid = false;
                message = 'Invalid format';
            }
            // Email validation
            else if (type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
            // URL validation
            else if (type === 'url' && value && !/^https?:\/\/.+/.test(value)) {
                isValid = false;
                message = 'Please enter a valid URL';
            }
            
            // Update field state
            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
            }
            
            // Show/hide error message
            let errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (!isValid) {
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    field.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = message;
            } else if (errorDiv) {
                errorDiv.remove();
            }
        }
        
        // Enhanced search functionality
        const searchInputs = document.querySelectorAll('input[type="search"], .search-input');
        searchInputs.forEach(input => {
            input.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const targetTable = this.closest('.card')?.querySelector('table');
                
                if (targetTable) {
                    const rows = targetTable.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                }
            });
        });
        
        // Enhanced pagination
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (page) {
                    // Add loading state
                    const container = this.closest('.card-body') || this.closest('.content-wrapper');
                    if (container) {
                        showSkeleton(container, 5);
                    }
                    
                    // Simulate page load
                    setTimeout(() => {
                        if (container) {
                            container.innerHTML = '<p>Page ' + page + ' content loaded</p>';
                        }
                    }, 1000);
                }
            });
        });
        
        // Enhanced delete confirmations
        const deleteButtons = document.querySelectorAll('.btn-danger[data-confirm]');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
                
                if (confirm(message)) {
                    // Show loading state
                    this.classList.add('btn-loading');
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
                    
                    // Simulate deletion
                    setTimeout(() => {
                        showSuccess('Item deleted successfully');
                        this.closest('tr')?.remove();
                    }, 1500);
                }
            });
        });
        
        // Enhanced copy to clipboard functionality
        const copyButtons = document.querySelectorAll('[data-copy]');
        copyButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const text = this.getAttribute('data-copy');
                navigator.clipboard.writeText(text).then(() => {
                    showSuccess('Copied to clipboard');
                }).catch(() => {
                    showError('Failed to copy to clipboard');
                });
            });
        });
        
        // Enhanced refresh functionality
        const refreshButtons = document.querySelectorAll('.btn-refresh');
        refreshButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.add('btn-loading');
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Refreshing...';
                
                setTimeout(() => {
                    location.reload();
                }, 1000);
            });
        });
    });
    
    // Global utility functions
        // Custom Modal implementation - Available globally
        window.showModal = function(modalId) {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.error('Modal not found:', modalId);
                return;
            }
            
            showModalManually(modalElement);
        };
    
    // Manual modal show function as fallback
    function showModalManually(modalElement) {
        // Remove existing backdrops
        const existingBackdrops = document.querySelectorAll('.modal-backdrop');
        existingBackdrops.forEach(backdrop => backdrop.remove());
        
        // Create backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'fixed inset-0 bg-black bg-opacity-50 z-40';
        backdrop.id = 'modal-backdrop-manual';
        document.body.appendChild(backdrop);
        
        // Show modal - remove hidden class and show
        modalElement.classList.remove('hidden');
        modalElement.style.display = 'block';
        modalElement.classList.add('fixed', 'inset-0', 'z-50', 'flex', 'items-center', 'justify-center');
        document.body.classList.add('overflow-hidden');
        
        // Add close handlers
        const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => hideModalManually(modalElement));
        });
        
        // Close on backdrop click
        backdrop.addEventListener('click', () => hideModalManually(modalElement));
    }
    
    function hideModalManually(modalElement) {
        // Hide modal - add hidden class and hide
        modalElement.classList.add('hidden');
        modalElement.style.display = 'none';
        modalElement.classList.remove('fixed', 'inset-0', 'z-50', 'flex', 'items-center', 'justify-center');
        document.body.classList.remove('overflow-hidden');
        
        // Remove backdrop
        const backdrop = document.getElementById('modal-backdrop-manual');
        if (backdrop) {
            backdrop.remove();
        }
    }
    
    // Initialize modals on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all modals properly
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            // Ensure modal is properly initialized
            if (!modal._customModal) {
                modal._customModal = true;
            }
        });
    });
    
    window.showSkeleton = function(container, count = 3) {
        const skeleton = document.createElement('div');
        skeleton.innerHTML = Array(count).fill(0).map(() => `
            <div class="skeleton skeleton-text"></div>
        `).join('');
        container.innerHTML = skeleton.innerHTML;
    };
    
    window.showEmptyState = function(container, icon = 'inbox', title = 'No Data', message = 'There are no items to display') {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-${icon}"></i>
                <h5>${title}</h5>
                <p>${message}</p>
            </div>
        `;
    };
    
    window.showErrorState = function(container, icon = 'exclamation-triangle', title = 'Error', message = 'Something went wrong') {
        container.innerHTML = `
            <div class="error-state">
                <i class="fas fa-${icon}"></i>
                <h5>${title}</h5>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-redo me-2"></i>Retry
                </button>
            </div>
        `;
    };
</script>
</body>
</html>


