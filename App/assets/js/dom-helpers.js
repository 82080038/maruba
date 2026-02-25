<?php
// DOM Helper Functions for Indonesian UI
// Provides utility functions for form handling, validation, and UI interactions
?>

// MarubaDOM - DOM Helper Functions
const MarubaDOM = {
    /**
     * Show loading state on button
     */
    showButtonLoading: function(buttonId, loadingText = 'Memuat...') {
        const button = document.getElementById(buttonId);
        if (!button) return;

        // Store original text and state
        button.dataset.originalText = button.innerHTML;
        button.dataset.originalDisabled = button.disabled;

        // Set loading state
        button.disabled = true;
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            ${loadingText}
        `;
    },

    /**
     * Hide loading state on button
     */
    hideButtonLoading: function(buttonId) {
        const button = document.getElementById(buttonId);
        if (!button) return;

        // Restore original state
        button.disabled = button.dataset.originalDisabled === 'true';
        button.innerHTML = button.dataset.originalText || button.innerHTML;

        // Clean up data attributes
        delete button.dataset.originalText;
        delete button.dataset.originalDisabled;
    },

    /**
     * Show alert message
     */
    showAlert: function(message, type = 'info', dismissible = true) {
        const alertContainer = document.getElementById('alertContainer') ||
                              document.querySelector('.alert-container') ||
                              document.body;

        const alertId = 'alert-' + Date.now();
        let alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
        `;

        if (dismissible) {
            alertHtml += `
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
        }

        alertHtml += '</div>';

        // Add to container
        alertContainer.insertAdjacentHTML('afterbegin', alertHtml);

        // Auto-dismiss after 5 seconds for success/info alerts
        if ((type === 'success' || type === 'info') && dismissible) {
            setTimeout(() => {
                const alertElement = document.getElementById(alertId);
                if (alertElement) {
                    const bsAlert = new bootstrap.Alert(alertElement);
                    bsAlert.close();
                }
            }, 5000);
        }
    },

    /**
     * Format phone input field
     */
    formatPhoneInput: function(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits

            // Format Indonesian phone number
            if (value.length >= 3) {
                if (value.startsWith('62')) {
                    // International format: +62xx-xxxx-xxxx
                    value = value.replace(/^62/, '62-');
                    if (value.length >= 6) {
                        value = value.replace(/^(\d{2})-(\d{3})/, '$1-$2-');
                    }
                    if (value.length >= 11) {
                        value = value.replace(/^(\d{2})-(\d{3})-(\d{4})/, '$1-$2-$3-');
                    }
                } else if (value.startsWith('08')) {
                    // Local format: 08xx-xxxx-xxxx
                    value = value.replace(/^(\d{4})/, '$1-');
                    if (value.length >= 9) {
                        value = value.replace(/^(\d{4})-(\d{4})/, '$1-$2-');
                    }
                } else if (value.length === 10 && value.startsWith('8')) {
                    // Short format: 8xxxxxxxxx -> 08xx-xxxx-xxxx
                    value = '08' + value;
                    value = value.replace(/^(\d{4})/, '$1-');
                    value = value.replace(/^(\d{4})-(\d{4})/, '$1-$2-');
                }
            }

            e.target.value = value;
        });
    },

    /**
     * Format NIK input field (16 digits)
     */
    formatNikInput: function(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits

            // Format NIK: xxxx-xxxx-xxxx-xxxx
            if (value.length >= 4) {
                value = value.replace(/^(\d{4})/, '$1-');
            }
            if (value.length >= 9) {
                value = value.replace(/^(\d{4})-(\d{4})/, '$1-$2-');
            }
            if (value.length >= 14) {
                value = value.replace(/^(\d{4})-(\d{4})-(\d{4})/, '$1-$2-$3-');
            }

            // Limit to 16 digits + separators
            if (value.replace(/-/g, '').length > 16) {
                value = value.substring(0, 19); // 16 digits + 3 separators
            }

            e.target.value = value;
        });
    },

    /**
     * Format currency input field
     */
    formatCurrencyInput: function(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, ''); // Remove non-digits
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
            }
            e.target.value = value;
        });

        input.addEventListener('blur', function(e) {
            const value = e.target.value;
            if (value && window.IndonesianFormat) {
                e.target.value = window.IndonesianFormat.currency(
                    window.IndonesianFormat.parseCurrency(value),
                    false
                );
            }
        });
    },

    /**
     * Validate form fields
     */
    validateForm: function(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;

                // Show error message
                const errorElement = field.parentNode.querySelector('.invalid-feedback');
                if (errorElement) {
                    errorElement.style.display = 'block';
                }
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');

                // Hide error message
                const errorElement = field.parentNode.querySelector('.invalid-feedback');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        });

        return isValid;
    },

    /**
     * Show/hide loading overlay
     */
    showLoading: function(message = 'Memuat...') {
        let overlay = document.getElementById('maruba-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'maruba-loading-overlay';
            overlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
            overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
            overlay.style.zIndex = '9999';
            overlay.innerHTML = `
                <div class="bg-white p-4 rounded shadow">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div>${message}</div>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    },

    hideLoading: function() {
        const overlay = document.getElementById('maruba-loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    },

    /**
     * Confirm dialog
     */
    confirm: function(message, title = 'Konfirmasi') {
        return new Promise((resolve) => {
            const modalHtml = `
                <div class="modal fade" id="maruba-confirm-modal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">${message}</div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-primary" id="confirm-yes">Ya</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            const modal = new bootstrap.Modal(document.getElementById('maruba-confirm-modal'));
            modal.show();

            document.getElementById('confirm-yes').addEventListener('click', () => {
                modal.hide();
                resolve(true);
            });

            document.getElementById('maruba-confirm-modal').addEventListener('hidden.bs.modal', () => {
                document.getElementById('maruba-confirm-modal').remove();
                resolve(false);
            });
        });
    },

    /**
     * Show toast notification
     */
    showToast: function(message, type = 'info', duration = 3000) {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1060';
            document.body.appendChild(container);
        }

        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHtml);

        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { autohide: duration > 0, delay: duration });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    },

    /**
     * Initialize all form enhancements
     */
    initializeForms: function() {
        // Initialize Bootstrap validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Auto-format inputs with data attributes
        document.querySelectorAll('[data-format="phone"]').forEach(input => {
            this.formatPhoneInput(input.id);
        });

        document.querySelectorAll('[data-format="nik"]').forEach(input => {
            this.formatNikInput(input.id);
        });

        document.querySelectorAll('[data-format="currency"]').forEach(input => {
            this.formatCurrencyInput(input.id);
        });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    MarubaDOM.initializeForms();
});

// Make globally available
window.MarubaDOM = MarubaDOM;
