// =========================================
// KSP APPLICATION UTILITY LIBRARY
// =========================================

// Global KSP namespace
window.KSP = window.KSP || {};

// =========================================
// CONFIGURATION
// =========================================
KSP.Config = {
    baseUrl: '/maruba',
    apiUrl: '/maruba/api',
    csrfToken: '',
    currentUser: null,
    currentTenant: null,
    isMobile: window.innerWidth < 768,
    isTablet: window.innerWidth >= 768 && window.innerWidth < 1024,
    isDesktop: window.innerWidth >= 1024
};

// =========================================
// AJAX UTILITIES
// =========================================
KSP.Ajax = {
    /**
     * Enhanced AJAX request with loading indicators and error handling
     */
    request: function(options) {
        const defaults = {
            method: 'GET',
            dataType: 'json',
            timeout: 30000,
            showLoading: true,
            showError: true,
            headers: {
                'X-CSRF-Token': KSP.Config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = $.extend({}, defaults, options);

        // Add loading indicator
        if (config.showLoading) {
            KSP.UI.showLoading();
        }

        // Setup error handling
        const originalError = config.error;
        config.error = function(xhr, status, error) {
            if (config.showLoading) {
                KSP.UI.hideLoading();
            }

            if (config.showError) {
                KSP.Ajax.handleError(xhr, status, error);
            }

            if (originalError) {
                originalError(xhr, status, error);
            }
        };

        // Setup success handling
        const originalSuccess = config.success;
        config.success = function(data, status, xhr) {
            if (config.showLoading) {
                KSP.UI.hideLoading();
            }

            // Check for session expiry
            if (data && data.session_expired) {
                KSP.Auth.redirectToLogin();
                return;
            }

            if (originalSuccess) {
                originalSuccess(data, status, xhr);
            }
        };

        return $.ajax(config);
    },

    /**
     * GET request wrapper
     */
    get: function(url, data, success, error) {
        return this.request({
            url: url,
            method: 'GET',
            data: data,
            success: success,
            error: error
        });
    },

    /**
     * POST request wrapper
     */
    post: function(url, data, success, error) {
        return this.request({
            url: url,
            method: 'POST',
            data: data,
            success: success,
            error: error
        });
    },

    /**
     * PUT request wrapper
     */
    put: function(url, data, success, error) {
        return this.request({
            url: url,
            method: 'PUT',
            data: data,
            success: success,
            error: error,
            headers: {
                'X-CSRF-Token': KSP.Config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'X-HTTP-Method-Override': 'PUT'
            }
        });
    },

    /**
     * DELETE request wrapper
     */
    delete: function(url, data, success, error) {
        return this.request({
            url: url,
            method: 'DELETE',
            data: data,
            success: success,
            error: error,
            headers: {
                'X-CSRF-Token': KSP.Config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
    },

    /**
     * Handle AJAX errors
     */
    handleError: function(xhr, status, error) {
        let message = 'Terjadi kesalahan pada sistem';

        if (xhr.status === 401) {
            message = 'Sesi Anda telah berakhir. Silakan login kembali.';
            setTimeout(() => KSP.Auth.redirectToLogin(), 2000);
        } else if (xhr.status === 403) {
            message = 'Anda tidak memiliki akses untuk melakukan tindakan ini.';
        } else if (xhr.status === 404) {
            message = 'Data atau halaman yang dicari tidak ditemukan.';
        } else if (xhr.status === 422) {
            // Validation errors
            const response = xhr.responseJSON;
            if (response && response.errors) {
                message = Object.values(response.errors).join('<br>');
            }
        } else if (xhr.status === 500) {
            message = 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
        } else if (status === 'timeout') {
            message = 'Permintaan timeout. Silakan coba lagi.';
        }

        KSP.UI.showAlert(message, 'danger');
    },

    /**
     * Load page content via AJAX
     */
    loadPage: function(url, target = '#dynamicContent', pushState = true) {
        return this.request({
            url: url,
            data: { partial: 1 },
            success: function(html) {
                $(target).html(html);

                // Update page title if available
                const titleMatch = html.match(/<title>(.*?)<\/title>/i);
                if (titleMatch) {
                    document.title = titleMatch[1];
                }

                // Update URL
                if (pushState && history.pushState) {
                    history.pushState({ url: url }, '', url);
                }

                // Reinitialize components
                KSP.UI.initializeComponents();
            },
            dataType: 'html'
        });
    }
};

// =========================================
// UI COMPONENTS LIBRARY
// =========================================
KSP.UI = {
    /**
     * Show loading spinner
     */
    showLoading: function(message = 'Memuat...') {
        if (!$('#ksp-loading-spinner').length) {
            $('body').append(`
                <div id="ksp-loading-spinner" class="ksp-loading-overlay">
                    <div class="ksp-loading-content">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2">${message}</div>
                    </div>
                </div>
            `);
        }
        $('#ksp-loading-spinner').fadeIn(200);
    },

    /**
     * Hide loading spinner
     */
    hideLoading: function() {
        $('#ksp-loading-spinner').fadeOut(200, function() {
            $(this).remove();
        });
    },

    /**
     * Show toast notification
     */
    showToast: function(message, type = 'info', duration = 5000) {
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        // Create toast container if not exists
        if (!$('#ksp-toast-container').length) {
            $('body').append('<div id="ksp-toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>');
        }

        $('#ksp-toast-container').append(toastHtml);

        const toast = new bootstrap.Toast(document.getElementById(toastId), {
            autohide: duration > 0,
            delay: duration
        });
        toast.show();

        // Remove from DOM after hide
        $(`#${toastId}`).on('hidden.bs.toast', function() {
            $(this).remove();
        });
    },

    /**
     * Show alert/notification
     */
    showAlert: function(message, type = 'info', dismissible = true) {
        const alertId = 'alert-' + Date.now();
        let alertHtml = `<div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">`;

        alertHtml += message;

        if (dismissible) {
            alertHtml += `<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        }

        alertHtml += '</div>';

        // Add to alert container
        if (!$('#ksp-alert-container').length) {
            $('#dynamicContent').prepend('<div id="ksp-alert-container"></div>');
        }

        $('#ksp-alert-container').prepend(alertHtml);

        // Auto remove after 10 seconds for non-error alerts
        if (type !== 'danger' && dismissible) {
            setTimeout(() => {
                $(`#${alertId}`).fadeOut(500, function() {
                    $(this).remove();
                });
            }, 10000);
        }
    },

    /**
     * Show confirmation modal
     */
    showConfirm: function(title, message, onConfirm, onCancel = null) {
        const modalId = 'ksp-confirm-modal';
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${message}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-primary" id="ksp-confirm-btn">Ya, Lanjutkan</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal
        $(`#${modalId}`).remove();
        $('body').append(modalHtml);

        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();

        $('#ksp-confirm-btn').on('click', function() {
            modal.hide();
            if (onConfirm) onConfirm();
        });

        $(`#${modalId}`).on('hidden.bs.modal', function() {
            $(this).remove();
        });
    },

    /**
     * Create enhanced modal
     */
    createModal: function(options) {
        const defaults = {
            id: 'ksp-modal-' + Date.now(),
            title: 'Modal',
            size: 'md', // sm, md, lg, xl
            content: '',
            buttons: [],
            onShow: null,
            onHide: null
        };

        const config = $.extend({}, defaults, options);
        const modalId = config.id;

        let buttonsHtml = '';
        config.buttons.forEach(button => {
            const btnClass = button.class || 'btn-primary';
            const btnText = button.text || 'OK';
            buttonsHtml += `<button type="button" class="btn ${btnClass}" id="${button.id || ''}">${btnText}</button>`;
        });

        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog modal-${config.size}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${config.title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${config.content}
                        </div>
                        ${buttonsHtml ? `<div class="modal-footer">${buttonsHtml}</div>` : ''}
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal
        $(`#${modalId}`).remove();
        $('body').append(modalHtml);

        const modal = new bootstrap.Modal(document.getElementById(modalId));

        // Attach button handlers
        config.buttons.forEach(button => {
            if (button.id && button.onClick) {
                $(`#${button.id}`).on('click', button.onClick);
            }
        });

        // Attach modal events
        if (config.onShow) {
            $(`#${modalId}`).on('shown.bs.modal', config.onShow);
        }
        if (config.onHide) {
            $(`#${modalId}`).on('hidden.bs.modal', config.onHide);
        }

        $(`#${modalId}`).on('hidden.bs.modal', function() {
            $(this).remove();
        });

        return modal;
    },

    /**
     * Create enhanced data table
     */
    createDataTable: function(selector, options) {
        const defaults = {
            responsive: true,
            pageLength: 25,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json'
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            initComplete: function() {
                // Add search input styling
                $('.dataTables_filter input').addClass('form-control form-control-sm');
                $('.dataTables_length select').addClass('form-select form-select-sm');
            }
        };

        const config = $.extend({}, defaults, options);
        return $(selector).DataTable(config);
    },

    /**
     * Create responsive card grid
     */
    createCardGrid: function(cards, columns = 3) {
        let html = '<div class="row">';

        cards.forEach((card, index) => {
            if (index > 0 && index % columns === 0) {
                html += '</div><div class="row">';
            }

            const colClass = `col-12 col-md-${12/columns} mb-4`;
            html += `
                <div class="${colClass}">
                    <div class="card h-100 ${card.class || ''}">
                        ${card.header ? `<div class="card-header">${card.header}</div>` : ''}
                        <div class="card-body">
                            ${card.icon ? `<i class="${card.icon} fs-1 text-primary mb-3"></i>` : ''}
                            <h5 class="card-title">${card.title}</h5>
                            ${card.subtitle ? `<h6 class="card-subtitle mb-2 text-muted">${card.subtitle}</h6>` : ''}
                            <p class="card-text">${card.content}</p>
                        </div>
                        ${card.footer ? `<div class="card-footer">${card.footer}</div>` : ''}
                    </div>
                </div>
            `;
        });

        html += '</div>';
        return html;
    },

    /**
     * Initialize all UI components
     */
    initializeComponents: function() {
        // Reinitialize Bootstrap components
        $('[data-bs-toggle="tooltip"]').tooltip();
        $('[data-bs-toggle="popover"]').popover();
        $('[data-bs-toggle="dropdown"]').dropdown();

        // Initialize form validation
        this.initializeFormValidation();

        // Initialize responsive tables
        this.initializeResponsiveTables();
    },

    /**
     * Initialize form validation
     */
    initializeFormValidation: function() {
        $('form[data-validate="true"]').each(function() {
            $(this).on('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                $(this).addClass('was-validated');
            });
        });
    },

    /**
     * Initialize responsive tables
     */
    initializeResponsiveTables: function() {
        $('.table-responsive table:not(.dataTable)').each(function() {
            const $table = $(this);
            if (!$table.closest('.table-responsive').length) {
                $table.wrap('<div class="table-responsive"></div>');
            }
        });
    },

    /**
     * Update responsive classes based on screen size
     */
    updateResponsiveClasses: function() {
        const width = window.innerWidth;
        const body = $('body');

        body.removeClass('device-mobile device-tablet device-desktop');

        if (width < 768) {
            body.addClass('device-mobile');
            KSP.Config.isMobile = true;
            KSP.Config.isTablet = false;
            KSP.Config.isDesktop = false;
        } else if (width < 1024) {
            body.addClass('device-tablet');
            KSP.Config.isMobile = false;
            KSP.Config.isTablet = true;
            KSP.Config.isDesktop = false;
        } else {
            body.addClass('device-desktop');
            KSP.Config.isMobile = false;
            KSP.Config.isTablet = false;
            KSP.Config.isDesktop = true;
        }
    }
};

// =========================================
// FORM UTILITIES
// =========================================
KSP.Form = {
    /**
     * Serialize form data to object
     */
    serializeObject: function(form) {
        const data = {};
        const formData = new FormData(form);

        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }

        return data;
    },

    /**
     * Populate form with data
     */
    populate: function(form, data) {
        $(form).find('input, select, textarea').each(function() {
            const name = $(this).attr('name');
            if (name && data[name] !== undefined) {
                $(this).val(data[name]);
            }
        });
    },

    /**
     * Clear form
     */
    clear: function(form) {
        $(form).find('input, select, textarea').each(function() {
            $(this).val('');
        });
        $(form).removeClass('was-validated');
    },

    /**
     * Show form errors
     */
    showErrors: function(form, errors) {
        // Clear previous errors
        $(form).find('.invalid-feedback').remove();
        $(form).find('.is-invalid').removeClass('is-invalid');

        for (const [field, messages] of Object.entries(errors)) {
            const element = $(form).find(`[name="${field}"]`);
            if (element.length) {
                element.addClass('is-invalid');
                const feedback = $('<div class="invalid-feedback"></div>');
                feedback.text(Array.isArray(messages) ? messages.join(', ') : messages);
                element.after(feedback);
            }
        }
    }
};

// =========================================
// AUTHENTICATION UTILITIES
// =========================================
KSP.Auth = {
    /**
     * Check if user is authenticated
     */
    isAuthenticated: function() {
        return !!KSP.Config.currentUser;
    },

    /**
     * Get current user
     */
    getCurrentUser: function() {
        return KSP.Config.currentUser;
    },

    /**
     * Check if user has permission
     */
    hasPermission: function(permission) {
        if (!KSP.Config.currentUser || !KSP.Config.currentUser.permissions) {
            return false;
        }
        return KSP.Config.currentUser.permissions.includes(permission);
    },

    /**
     * Redirect to login page
     */
    redirectToLogin: function() {
        window.location.href = KSP.Config.baseUrl + '/login';
    },

    /**
     * Logout user
     */
    logout: function() {
        KSP.Ajax.post(KSP.Config.baseUrl + '/logout', {}, function() {
            window.location.href = KSP.Config.baseUrl + '/login';
        });
    }
};

// =========================================
// NAVIGATION UTILITIES
// =========================================
KSP.Nav = {
    /**
     * Load page content
     */
    loadPage: function(url, pushState = true) {
        KSP.UI.showLoading('Memuat halaman...');

        return KSP.Ajax.request({
            url: url,
            data: { partial: 1 },
            dataType: 'html',
            success: function(html) {
                $('#dynamicContent').html(html);

                // Update URL
                if (pushState && history.pushState) {
                    history.pushState({ url: url }, '', url);
                }

                // Reinitialize components
                KSP.UI.initializeComponents();

                // Update active menu
                KSP.Nav.updateActiveMenu(url);
            }
        });
    },

    /**
     * Update active menu item
     */
    updateActiveMenu: function(url) {
        $('.menu-item').removeClass('active');

        $('.menu-item').each(function() {
            const href = $(this).attr('href');
            if (href && url.includes(href.replace(KSP.Config.baseUrl, ''))) {
                $(this).addClass('active');
                return false;
            }
        });
    },

    /**
     * Initialize navigation
     */
    initialize: function() {
        // Handle menu clicks
        $(document).on('click', '.menu-item', function(e) {
            if ($(this).hasClass('dropdown-toggle')) return;

            e.preventDefault();
            const url = $(this).attr('href');
            if (url && url !== '#') {
                KSP.Nav.loadPage(url);
            }
        });

        // Handle browser back/forward
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.url) {
                KSP.Nav.loadPage(e.state.url, false);
            }
        });
    }
};

// =========================================
// UTILITY FUNCTIONS
// =========================================
KSP.Utils = {
    /**
     * Format currency
     */
    formatCurrency: function(amount, currency = 'IDR') {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0
        }).format(amount);
    },

    /**
     * Format date
     */
    formatDate: function(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        return new Intl.DateTimeFormat('id-ID', $.extend(defaultOptions, options)).format(new Date(date));
    },

    /**
     * Format date and time
     */
    formatDateTime: function(date) {
        return new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    },

    /**
     * Debounce function
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function
     */
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

// =========================================
// INITIALIZATION
// =========================================
$(document).ready(function() {
    console.log('🚀 KSP Enhanced UI System Initialized');

    // Update responsive classes
    KSP.UI.updateResponsiveClasses();

    // Initialize navigation
    KSP.Nav.initialize();

    // Initialize components
    KSP.UI.initializeComponents();

    // Handle window resize
    $(window).on('resize', KSP.Utils.debounce(function() {
        KSP.UI.updateResponsiveClasses();
    }, 250));

    // Global AJAX error handler
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status === 401) {
            KSP.UI.showAlert('Sesi Anda telah berakhir. Silakan login kembali.', 'warning');
            setTimeout(() => KSP.Auth.redirectToLogin(), 3000);
        }
    });

    // Global form submission handler
    $(document).on('submit', 'form[data-ajax="true"]', function(e) {
        e.preventDefault();

        const form = this;
        const action = $(form).attr('action') || window.location.href;
        const method = $(form).attr('method') || 'POST';
        const data = KSP.Form.serializeObject(form);

        KSP.Ajax.request({
            url: action,
            method: method,
            data: data,
            success: function(response) {
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else if (response.reload) {
                    window.location.reload();
                } else if (response.message) {
                    KSP.UI.showToast(response.message, response.type || 'success');
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    KSP.Form.showErrors(form, xhr.responseJSON.errors);
                }
            }
        });
    });
});

// =========================================
// CSS STYLES
// =========================================
const kspStyles = `
<style>
/* Enhanced Loading Spinner */
.ksp-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.ksp-loading-content {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

/* Enhanced Toast Container */
.toast-container {
    z-index: 1060;
}

.toast {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Enhanced Alerts */
.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* Enhanced Modals */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    border-radius: 12px 12px 0 0;
}

/* Enhanced Cards */
.card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 1px solid #e9ecef;
}

/* Enhanced Tables */
.table-responsive {
    border-radius: 8px;
    overflow: hidden;
}

.table {
    margin-bottom: 0;
}

.table thead th {
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    color: #495057;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Enhanced Buttons */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Enhanced Form Controls */
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #ced4da;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Enhanced Navigation */
.menu-item {
    transition: all 0.3s ease;
}

.menu-item:hover {
    transform: translateX(4px);
}

/* Mobile Responsive Enhancements */
@media (max-width: 767.98px) {
    .ksp-loading-content {
        margin: 1rem;
        padding: 1.5rem;
    }

    .card {
        margin-bottom: 1rem;
    }

    .modal-dialog {
        margin: 0.5rem;
    }

    .toast-container {
        left: 0.5rem;
        right: 0.5rem;
        top: 1rem;
    }
}

/* Tablet Responsive */
@media (min-width: 768px) and (max-width: 1023.98px) {
    .card {
        margin-bottom: 1.5rem;
    }
}

/* Utility Classes */
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.text-truncate-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.slide-in-right {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
</style>
`;

// Inject styles
document.head.insertAdjacentHTML('beforeend', kspStyles);