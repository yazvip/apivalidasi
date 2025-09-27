/**
 * Enhanced UI/UX JavaScript for API Management System
 * Provides advanced interactions, animations, and user feedback
 */

class EnhancedUI {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupGlobalFunctions();
    }

    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeAnimations();
            this.setupFormValidation();
            this.setupTableInteractions();
            this.setupButtonStates();
            this.setupSearchFunctionality();
            this.setupPagination();
            this.setupModals();
            this.setupTooltips();
            this.setupCopyToClipboard();
            this.setupRefreshButtons();
            this.setupDeleteConfirmations();
        });
    }

    initializeComponents() {
        // Initialize Bootstrap components
        this.initializeTooltips();
        this.initializePopovers();
        this.initializeModals();
    }

    setupGlobalFunctions() {
        // Global utility functions
        window.showToast = this.showToast.bind(this);
        window.showSuccess = this.showSuccess.bind(this);
        window.showError = this.showError.bind(this);
        window.showSkeleton = this.showSkeleton.bind(this);
        window.showEmptyState = this.showEmptyState.bind(this);
        window.showErrorState = this.showErrorState.bind(this);
        window.showLoading = this.showLoading.bind(this);
        window.hideLoading = this.hideLoading.bind(this);
    }

    // Animation Methods
    initializeAnimations() {
        // Staggered card animations
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in');
        });

        // Table row animations
        const tableRows = document.querySelectorAll('.table tbody tr');
        tableRows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.05}s`;
            row.classList.add('slide-in');
        });
    }

    // Form Validation
    setupFormValidation() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                } else {
                    this.showFormLoading(form);
                }
            });
        });

        const inputs = document.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) {
                    this.validateField(input);
                }
            });
        });
    }

    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    validateField(field) {
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
        this.updateFieldFeedback(field, isValid, message);

        return isValid;
    }

    updateFieldFeedback(field, isValid, message) {
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

    showFormLoading(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            if (!submitBtn.dataset.originalText) {
                submitBtn.dataset.originalText = submitBtn.innerHTML;
            }
            
            submitBtn.disabled = true;
            submitBtn.classList.add('btn-loading');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        }
    }

    // Table Interactions
    setupTableInteractions() {
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            this.setupTableSorting(table);
            this.setupTableHover(table);
        });
    }

    setupTableSorting(table) {
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.innerHTML += ' <i class="fas fa-sort text-muted"></i>';
            
            header.addEventListener('click', () => {
                this.sortTable(table, header);
            });
        });
    }

    sortTable(table, header) {
        const column = header.getAttribute('data-sort');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAsc = header.classList.contains('sort-asc');
        
        // Reset all headers
        const headers = table.querySelectorAll('th[data-sort]');
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
        header.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
        header.querySelector('i').className = `fas fa-sort-${isAsc ? 'down' : 'up'} text-primary`;
        
        // Reorder rows with animation
        rows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.05}s`;
            row.classList.add('slide-in');
            tbody.appendChild(row);
        });
    }

    setupTableHover(table) {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.transform = 'scale(1.01)';
            });
            row.addEventListener('mouseleave', () => {
                row.style.transform = 'scale(1)';
            });
        });
    }

    // Button States
    setupButtonStates() {
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (btn.type === 'submit' || btn.classList.contains('btn-loading')) {
                    this.showButtonLoading(btn);
                }
            });
        });
    }

    showButtonLoading(btn) {
        btn.classList.add('btn-loading');
        if (!btn.dataset.originalText) {
            btn.dataset.originalText = btn.innerHTML;
        }
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        
        // Reset after 3 seconds if no response
        setTimeout(() => {
            btn.classList.remove('btn-loading');
            btn.innerHTML = btn.dataset.originalText;
        }, 3000);
    }

    // Search Functionality
    setupSearchFunctionality() {
        const searchInputs = document.querySelectorAll('input[type="search"], .search-input');
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.performSearch(e.target);
            });
        });
    }

    performSearch(input) {
        const searchTerm = input.value.toLowerCase();
        const targetTable = input.closest('.card')?.querySelector('table');
        
        if (targetTable) {
            const rows = targetTable.querySelectorAll('tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const isVisible = text.includes(searchTerm);
                row.style.display = isVisible ? '' : 'none';
                
                if (isVisible) {
                    visibleCount++;
                    row.classList.add('fade-in');
                }
            });
            
            // Show empty state if no results
            if (visibleCount === 0) {
                this.showSearchEmptyState(targetTable);
            } else {
                this.hideSearchEmptyState(targetTable);
            }
        }
    }

    showSearchEmptyState(table) {
        let emptyState = table.parentNode.querySelector('.search-empty-state');
        if (!emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'search-empty-state alert alert-info text-center';
            emptyState.innerHTML = '<i class="fas fa-search me-2"></i>No results found';
            table.parentNode.insertBefore(emptyState, table);
        }
        emptyState.style.display = 'block';
    }

    hideSearchEmptyState(table) {
        const emptyState = table.parentNode.querySelector('.search-empty-state');
        if (emptyState) {
            emptyState.style.display = 'none';
        }
    }

    // Pagination
    setupPagination() {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = link.getAttribute('data-page');
                if (page) {
                    this.loadPage(page, link);
                }
            });
        });
    }

    loadPage(page, link) {
        const container = link.closest('.card-body') || link.closest('.content-wrapper');
        if (container) {
            this.showSkeleton(container, 5);
            
            // Simulate page load
            setTimeout(() => {
                container.innerHTML = `<p>Page ${page} content loaded</p>`;
            }, 1000);
        }
    }

    // Modals
    setupModals() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', () => {
                const firstInput = modal.querySelector('input, textarea, select');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            });
        });
    }

    // Tooltips
    setupTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    initializeTooltips() {
        // Tooltips are initialized in setupTooltips
    }

    // Popovers
    initializePopovers() {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(popoverTriggerEl => {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    // Modals
    initializeModals() {
        // Modals are handled by Bootstrap
    }

    // Copy to Clipboard
    setupCopyToClipboard() {
        const copyButtons = document.querySelectorAll('[data-copy]');
        copyButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const text = btn.getAttribute('data-copy');
                navigator.clipboard.writeText(text).then(() => {
                    this.showSuccess('Copied to clipboard');
                }).catch(() => {
                    this.showError('Failed to copy to clipboard');
                });
            });
        });
    }

    // Refresh Buttons
    setupRefreshButtons() {
        const refreshButtons = document.querySelectorAll('.btn-refresh');
        refreshButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.showButtonLoading(btn);
                setTimeout(() => {
                    location.reload();
                }, 1000);
            });
        });
    }

    // Delete Confirmations
    setupDeleteConfirmations() {
        const deleteButtons = document.querySelectorAll('.btn-danger[data-confirm]');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const message = btn.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
                
                if (confirm(message)) {
                    this.showButtonLoading(btn);
                    
                    // Simulate deletion
                    setTimeout(() => {
                        this.showSuccess('Item deleted successfully');
                        btn.closest('tr')?.remove();
                    }, 1500);
                }
            });
        });
    }

    // Toast Notifications
    showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container') || this.createToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} show`;
        toast.innerHTML = `
            <div class="toast-body d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close btn-close-white ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }

    // Loading States
    showLoading(container) {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="loading-spinner"></div>
        `;
        container.appendChild(loadingOverlay);
    }

    hideLoading(container) {
        const loadingOverlay = container.querySelector('.loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
    }

    // Skeleton Loading
    showSkeleton(container, count = 3) {
        const skeleton = document.createElement('div');
        skeleton.innerHTML = Array(count).fill(0).map(() => `
            <div class="skeleton skeleton-text"></div>
        `).join('');
        container.innerHTML = skeleton.innerHTML;
    }

    // Empty States
    showEmptyState(container, icon = 'inbox', title = 'No Data', message = 'There are no items to display') {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-${icon}"></i>
                <h5>${title}</h5>
                <p>${message}</p>
            </div>
        `;
    }

    showErrorState(container, icon = 'exclamation-triangle', title = 'Error', message = 'Something went wrong') {
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
    }
}

// Initialize Enhanced UI
new EnhancedUI();
