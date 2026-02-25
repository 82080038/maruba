/**
 * DOM Helper Functions for Maruba Application
 * Provides consistent DOM manipulation utilities using jQuery
 */

// Ensure jQuery is loaded before using MarubaDOM
(function ($) {
    'use strict';

    // Global DOM Helper Object
    window.MarubaDOM = {

        /**
         * Show loading state on a button
         */
        showButtonLoading: function (buttonId, text = 'Memuat...') {
            $('#' + buttonId).prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>' + text);
        },

        /**
         * Reset button to normal state
         */
        resetButton: function (buttonId, originalText = 'Simpan') {
            $('#' + buttonId).prop('disabled', false).text(originalText);
        },

        /**
         * Show alert message
         */
        showAlert: function (message, type = 'success', containerId = 'mainContainer') {
            const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert" id="dynamicAlert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
            $('#' + containerId).prepend(alertHtml);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                $('#dynamicAlert').fadeOut('slow', function () {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Clear all form fields
         */
        clearForm: function (formId) {
            $('#' + formId)[0].reset();
            $('#' + formId + ' .form-control').removeClass('is-invalid');
            $('#' + formId + ' .invalid-feedback').hide();
        },

        /**
         * Validate form fields
         */
        validateForm: function (formId, rules = {}) {
            let isValid = true;
            const form = $('#' + formId);

            // Clear previous errors
            form.find('.form-control').removeClass('is-invalid');
            form.find('.invalid-feedback').hide();

            // Validate each field based on rules
            Object.keys(rules).forEach(fieldId => {
                const field = $('#' + fieldId);
                const value = field.val().trim();
                const rule = rules[fieldId];

                if (rule.required && !value) {
                    field.addClass('is-invalid');
                    $('#' + (rule.errorId || fieldId + 'Error')).show();
                    isValid = false;
                } else if (rule.pattern && !rule.pattern.test(value)) {
                    field.addClass('is-invalid');
                    $('#' + (rule.errorId || fieldId + 'Error')).show();
                    isValid = false;
                } else if (rule.minLength && value.length < rule.minLength) {
                    field.addClass('is-invalid');
                    $('#' + (rule.errorId || fieldId + 'Error')).show();
                    isValid = false;
                }
            });

            return isValid;
        },

        /**
         * Format currency input
         */
        formatCurrencyInput: function (inputId) {
            $('#' + inputId).on('input', function () {
                let value = $(this).val().replace(/\D/g, '');
                if (value) {
                    value = parseInt(value).toLocaleString('id-ID');
                }
                $(this).val(value);
            });
        },

        /**
         * Format phone number input
         */
        formatPhoneInput: function (inputId, maxLength = 13) {
            $('#' + inputId).on('input', function () {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > maxLength) {
                    value = value.substring(0, maxLength);
                }
                $(this).val(value);
            });
        },

        /**
         * Format NIK input (16 digits)
         */
        formatNikInput: function (inputId) {
            $('#' + inputId).on('input', function () {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 16) {
                    value = value.substring(0, 16);
                }
                $(this).val(value);
            });
        },

        /**
         * Get current geolocation
         */
        getCurrentLocation: function (latId, lngId, callback) {
            if (navigator.geolocation) {
                $('#' + latId + ', #' + lngId).prop('readonly', true).val('Mendapatkan lokasi...');

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        $('#' + latId).val(position.coords.latitude);
                        $('#' + lngId).val(position.coords.longitude);
                        $('#' + latId + ', #' + lngId).prop('readonly', false);

                        if (callback) callback(position.coords.latitude, position.coords.longitude);
                    },
                    function (error) {
                        $('#' + latId + ', #' + lngId).val('').prop('readonly', false);
                        MarubaDOM.showAlert('Tidak dapat mendapatkan lokasi. Pastikan GPS aktif.', 'danger');
                    }
                );
            } else {
                MarubaDOM.showAlert('Browser tidak mendukung geolocation.', 'danger');
            }
        },

        /**
         * Initialize data table with common settings
         */
        initDataTable: function (tableId, options = {}) {
            const defaultOptions = {
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]]
            };

            return $('#' + tableId).DataTable($.extend(defaultOptions, options));
        },

        /**
         * Confirm action dialog
         */
        confirmAction: function (message, callback) {
            if (confirm(message)) {
                callback();
            }
        },

        /**
         * Toggle sidebar visibility
         */
        toggleSidebar: function () {
            $('#mainSidebar').toggleClass('show');
        },

        /**
         * Set active navigation
         */
        setActiveNav: function (navId) {
            $('#mainNavigation .nav-link').removeClass('active');
            $('#' + navId).addClass('active');
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function () {
            $('[data-toggle="tooltip"]').tooltip();
        },

        /**
         * Initialize popovers
         */
        initPopovers: function () {
            $('[data-toggle="popover"]').popover();
        },

        /**
         * Debounce function for search inputs
         */
        debounce: function (func, wait) {
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
         * Auto-size textarea
         */
        autoSizeTextarea: function (textareaId) {
            const textarea = document.getElementById(textareaId);
            if (textarea) {
                textarea.addEventListener('input', function () {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
        },

        /**
         * Copy to clipboard
         */
        copyToClipboard: function (text, successMessage = 'Disalin ke clipboard!') {
            navigator.clipboard.writeText(text).then(function () {
                MarubaDOM.showAlert(successMessage, 'success');
            }).catch(function () {
                MarubaDOM.showAlert('Gagal menyalin ke clipboard', 'danger');
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        // Initialize tooltips and popovers
        MarubaDOM.initTooltips();
        MarubaDOM.initPopovers();

        // Auto-hide alerts after 5 seconds
        setTimeout(function () {
            $('#errorAlert, #successAlert').fadeOut('slow');
        }, 5000);
    });

    // Global AJAX error handler
    $(document).ajaxError(function (event, jqXHR, ajaxSettings, thrownError) {
        const errorMsg = 'Terjadi kesalahan. Silakan coba lagi.';
        MarubaDOM.showAlert(errorMsg, 'danger');
    });

})(jQuery);
