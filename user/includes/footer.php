            </div>
        </div>
    </div>

<script src="/user/assets/js/enhanced-ui.js"></script>
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
                header.addEventListener('click', () => {
                    const column = header.getAttribute('data-sort');
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    
                    rows.sort((a, b) => {
                        const aVal = a.querySelector(`td:nth-child(${column})`).textContent.trim();
                        const bVal = b.querySelector(`td:nth-child(${column})`).textContent.trim();
                        return aVal.localeCompare(bVal);
                    });
                    
                    rows.forEach(row => tbody.appendChild(row));
                });
            });
        });
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                if (alert.classList.contains('auto-hide')) {
                    alert.style.transition = 'opacity 0.5s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
        
        // Form validation
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-500');
                        isValid = false;
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    showToast('Please fill in all required fields', 'error');
                }
            });
        });
        
        // Loading states for buttons
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (this.form && this.form.checkValidity()) {
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                }
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
        container.appendChild(skeleton);
    };
    
    window.hideSkeleton = function(container) {
        const skeletons = container.querySelectorAll('.skeleton');
        skeletons.forEach(skeleton => skeleton.remove());
    };
    
    window.showToast = function(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
        const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
        
        toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg flex items-center fade-in`;
        toast.innerHTML = `
            <i class="fas ${icon} mr-3"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.getElementById('toast-container').appendChild(toast);
        
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s ease-out';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, duration);
    };
    
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard!', 'success');
        }).catch(() => {
            showToast('Failed to copy to clipboard', 'error');
        });
    };
</script>
</body>
</html>
