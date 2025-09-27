<?php
/**
 * Admin Header - Konsisten untuk semua halaman admin
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - API Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom animations for Tailwind */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        .slide-in {
            animation: slideIn 0.3s ease-out;
        }
        .bounce-in {
            animation: bounceIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        /* Skeleton Loading */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
    <script>
        // Enhanced UI/UX JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Add animations to cards
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
            
            // Enhanced form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('btn-loading');
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                    }
                });
            });
            
            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
            
            // Enhanced table interactions
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Loading states for buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.type === 'submit' || this.classList.contains('btn-loading')) {
                        this.classList.add('btn-loading');
                        const originalText = this.innerHTML;
                        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
                        
                        // Reset after 3 seconds if no response
                        setTimeout(() => {
                            this.classList.remove('btn-loading');
                            this.innerHTML = originalText;
                        }, 3000);
                    }
                });
            });
            
            // Toast notifications
            window.showToast = function(message, type = 'success') {
                const toastContainer = document.querySelector('.toast-container') || createToastContainer();
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
            };
            
            function createToastContainer() {
                const container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
                return container;
            }
            
            // Enhanced error handling
            window.showError = function(message) {
                showToast(message, 'error');
            };
            
            window.showSuccess = function(message) {
                showToast(message, 'success');
            };
            
            // Skeleton loading for dynamic content
            window.showSkeleton = function(container, count = 3) {
                const skeleton = document.createElement('div');
                skeleton.innerHTML = Array(count).fill(0).map(() => `
                    <div class="skeleton skeleton-text"></div>
                `).join('');
                container.innerHTML = skeleton.innerHTML;
            };
            
            // Enhanced form validation feedback
            const inputs = document.querySelectorAll('.form-control');
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
                const pattern = field.getAttribute('pattern');
                
                let isValid = true;
                let message = '';
                
                if (required && !value) {
                    isValid = false;
                    message = 'This field is required';
                } else if (minLength && value.length < parseInt(minLength)) {
                    isValid = false;
                    message = `Minimum ${minLength} characters required`;
                } else if (pattern && !new RegExp(pattern).test(value)) {
                    isValid = false;
                    message = 'Invalid format';
                }
                
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
            
            // Enhanced empty states
            window.showEmptyState = function(container, icon = 'inbox', title = 'No Data', message = 'There are no items to display') {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-${icon}"></i>
                        <h5>${title}</h5>
                        <p>${message}</p>
                    </div>
                `;
            };
            
            // Enhanced error states
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
        });
    </script>
</head>
<body class="bg-gray-50">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Main Layout Container -->
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="flex-1 ml-64">
            <div class="h-full overflow-y-auto">
