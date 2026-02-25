<?php
// Indonesian Formatting Helpers for JavaScript
// Comprehensive helper functions for Indonesian locale formatting and utilities
// Enhanced with additional functions from project helpers
?>

// =========================================
// IDHELPER - COMPREHENSIVE INDONESIAN HELPERS
// =========================================

// Indonesian Helper Namespace with enhanced functionality
window.IDHelper = (function () {
    const locale = 'id-ID';
    const tz = 'Asia/Jakarta';

    const numberFormatter = (decimals = 0, useGrouping = true) =>
        new Intl.NumberFormat(locale, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
            useGrouping
        });

    const currencyFormatter = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });

    // =========================================
    // NUMBER & CURRENCY FORMATTING
    // =========================================

    /**
     * Format number with Indonesian separators
     * @param {number} value
     * @param {number} decimals
     * @returns {string}
     */
    function formatNumber(value, decimals = 0) {
        const n = Number(value ?? 0);
        return numberFormatter(decimals).format(isNaN(n) ? 0 : n);
    }

    /**
     * Format currency to Indonesian Rupiah
     * @param {number} value
     * @param {boolean} withSymbol
     * @returns {string}
     */
    function formatCurrency(value, withSymbol = true) {
        const n = Number(value ?? 0);
        if (withSymbol) return currencyFormatter.format(isNaN(n) ? 0 : n);
        return formatNumber(n, 0);
    }

    /**
     * Parse formatted number string back to number
     * @param {string} str
     * @returns {number}
     */
    function parseNumber(str) {
        if (typeof str !== 'string') return Number(str) || 0;
        // Remove thousand separators, convert decimal separator
        const normalized = str.replace(/\./g, '').replace(/,/g, '.').trim();
        const num = Number(normalized);
        return isNaN(num) ? 0 : num;
    }

    // =========================================
    // DATE & TIME FORMATTING
    // =========================================

    /**
     * Format date with Indonesian locale
     * @param {string|Date} date
     * @param {object} options
     * @returns {string}
     */
    function formatDate(date, options) {
        const d = date instanceof Date ? date : new Date(date);
        if (isNaN(d.getTime())) return '';
        const fmt = new Intl.DateTimeFormat(locale, options || {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            timeZone: tz
        });
        return fmt.format(d);
    }

    /**
     * Format date for input fields (DD/MM/YYYY to YYYY-MM-DD)
     * @param {string} str
     * @returns {string}
     */
    function formatDateInput(str) {
        const m = (str || '').match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/);
        if (!m) return '';
        const [_, d, mo, y] = m;
        const year = y.length === 2 ? '20' + y : y;
        return `${year.padStart(4, '0')}-${mo.padStart(2, '0')}-${d.padStart(2, '0')}`;
    }

    /**
     * Convert date input string to Date object
     * @param {string} str
     * @returns {Date|null}
     */
    function fromDateInput(str) {
        const iso = formatDateInput(str);
        return iso ? new Date(iso) : null;
    }

    /**
     * Format relative time (e.g., "2 jam yang lalu")
     * @param {string|Date} date
     * @returns {string}
     */
    function humanizeTime(date) {
        const d = date instanceof Date ? date : new Date(date);
        if (isNaN(d.getTime())) return '';

        const diff = Date.now() - d.getTime();
        const sec = Math.floor(diff / 1000);

        if (sec < 60) return `${sec} detik lalu`;
        const min = Math.floor(sec / 60);
        if (min < 60) return `${min} menit lalu`;
        const hr = Math.floor(min / 60);
        if (hr < 24) return `${hr} jam lalu`;
        const day = Math.floor(hr / 24);
        if (day < 30) return `${day} hari lalu`;
        const month = Math.floor(day / 30);
        if (month < 12) return `${month} bulan lalu`;
        const year = Math.floor(month / 12);
        return `${year} tahun lalu`;
    }

    // =========================================
    // TERBILANG (NUMBER TO WORDS)
    // =========================================

    /**
     * Convert number to Indonesian words
     * @param {number} n
     * @returns {string}
     */
    function terbilang(n) {
        n = Math.floor(Number(n) || 0);
        const satuan = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];

        if (n < 12) return satuan[n];
        if (n < 20) return terbilang(n - 10) + ' belas';
        if (n < 100) return terbilang(Math.floor(n / 10)) + ' puluh ' + terbilang(n % 10);
        if (n < 200) return 'seratus ' + terbilang(n - 100);
        if (n < 1000) return terbilang(Math.floor(n / 100)) + ' ratus ' + terbilang(n % 100);
        if (n < 2000) return 'seribu ' + terbilang(n - 1000);
        if (n < 1000000) return terbilang(Math.floor(n / 1000)) + ' ribu ' + terbilang(n % 1000);
        if (n < 1000000000) return terbilang(Math.floor(n / 1000000)) + ' juta ' + terbilang(n % 1000000);
        if (n < 1000000000000) return terbilang(Math.floor(n / 1000000000)) + ' milyar ' + terbilang(n % 1000000000);
        if (n < 1000000000000000) return terbilang(Math.floor(n / 1000000000000)) + ' triliun ' + terbilang(n % 1000000000000);
        return '';
    }

    /**
     * Convert currency amount to Indonesian words
     * @param {number} n
     * @returns {string}
     */
    function terbilangRupiah(n) {
        const t = terbilang(n).trim();
        return t ? `${t} rupiah` : '';
    }

    // =========================================
    // VALIDATION FUNCTIONS
    // =========================================

    /**
     * Validate email address
     * @param {string} str
     * @returns {boolean}
     */
    function validateEmail(str) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str || '');
    }

    /**
     * Validate Indonesian phone number
     * @param {string} str
     * @returns {boolean}
     */
    function validatePhoneID(str) {
        return /^(\+62|62|0)8\d{7,11}$/.test((str || '').replace(/\D/g, ''));
    }

    /**
     * Validate NIK (16 digits)
     * @param {string} str
     * @returns {boolean}
     */
    function validateNIK(str) {
        return /^\d{16}$/.test((str || '').trim());
    }

    /**
     * Validate NPWP (15 digits)
     * @param {string} str
     * @returns {boolean}
     */
    function validateNPWP(str) {
        const digits = (str || '').replace(/\D/g, '');
        return digits.length === 15;
    }

    // =========================================
    // UTILITY FUNCTIONS
    // =========================================

    /**
     * Escape HTML special characters
     * @param {string} str
     * @returns {string}
     */
    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Create URL slug from string
     * @param {string} str
     * @returns {string}
     */
    function slugify(str) {
        return (str || '')
            .toLowerCase()
            .normalize('NFD').replace(/[^\w\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    /**
     * Debounce function calls
     * @param {function} fn
     * @param {number} wait
     * @returns {function}
     */
    function debounce(fn, wait = 300) {
        let t;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    /**
     * Throttle function calls
     * @param {function} fn
     * @param {number} wait
     * @returns {function}
     */
    function throttle(fn, wait = 300) {
        let last = 0;
        return function (...args) {
            const now = Date.now();
            if (now - last >= wait) {
                last = now;
                fn.apply(this, args);
            }
        };
    }

    // =========================================
    // LEGACY COMPATIBILITY (from original IndonesianFormat)
    // =========================================

    const IndonesianFormat = {
        number: function(value, decimals = 0) {
            return formatNumber(value, decimals);
        },

        currency: function(value, withSymbol = true, decimals = 0) {
            return formatCurrency(value, withSymbol);
        },

        parseCurrency: function(currencyString) {
            return parseNumber(currencyString);
        },

        date: function(dateString) {
            if (!dateString || dateString === '0000-00-00') return '-';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '-';
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        },

        phoneNumber: function(phone) {
            if (!phone) return '';
            const clean = phone.replace(/\D/g, '');

            if (clean.length === 10 && clean.startsWith('8')) {
                return clean.substring(0, 4) + '-' + clean.substring(4, 8) + '-' + clean.substring(8);
            } else if (clean.length === 11 && clean.startsWith('08')) {
                return clean.substring(0, 4) + '-' + clean.substring(4, 8) + '-' + clean.substring(8);
            } else if (clean.length === 12 && clean.startsWith('628')) {
                const formatted = '08' + clean.substring(2);
                return formatted.substring(0, 4) + '-' + formatted.substring(4, 8) + '-' + formatted.substring(8);
            }

            return phone;
        },

        nik: function(nik) {
            if (!nik || nik.length !== 16) return nik;
            return nik.substring(0, 4) + '-' + nik.substring(4, 8) + '-' + nik.substring(8, 12) + '-' + nik.substring(12);
        }
    };

    // Indonesian Text Translations
    const IndonesianText = {
        translations: {
            'save': 'Simpan',
            'cancel': 'Batal',
            'edit': 'Edit',
            'delete': 'Hapus',
            'add': 'Tambah',
            'view': 'Lihat',
            'search': 'Cari',
            'loading': 'Memuat...',
            'error': 'Error',
            'success': 'Berhasil',
            'warning': 'Peringatan',
            'info': 'Informasi',
            'confirm_delete': 'Apakah Anda yakin ingin menghapus?',
            'data_saved': 'Data berhasil disimpan',
            'data_deleted': 'Data berhasil dihapus',
            'no_data': 'Tidak ada data',
            'required_field': 'Field ini wajib diisi',
            'invalid_format': 'Format tidak valid'
        },

        translate: function(key) {
            return this.translations[key] || key;
        }
    };

    // =========================================
    // PUBLIC API
    // =========================================

    return {
        // Number & Currency
        formatNumber,
        formatCurrency,
        parseNumber,

        // Date & Time
        formatDate,
        formatDateInput,
        fromDateInput,
        humanizeTime,

        // Terbilang
        terbilang,
        terbilangRupiah,

        // Validation
        validateEmail,
        validatePhoneID,
        validateNIK,
        validateNPWP,

        // Utilities
        escapeHtml,
        slugify,
        debounce,
        throttle,

        // Legacy compatibility
        IndonesianFormat,
        IndonesianText
    };
})();

// =========================================
// GLOBAL COMPATIBILITY ALIASES
// =========================================

// Make globally available for backward compatibility
window.IndonesianFormat = IDHelper.IndonesianFormat;
window.IndonesianText = IDHelper.IndonesianText;

// Enhanced global functions
window.formatUang = (amount, withSymbol = true) => IDHelper.formatCurrency(amount, withSymbol);
window.formatTanggal = (date) => IDHelper.formatDate(date);
window.formatAngka = (number, decimals = 0) => IDHelper.formatNumber(number, decimals);
window.terjemahkan = (key) => IDHelper.IndonesianText.translate(key);
window.waktuLalu = (date) => IDHelper.humanizeTime(date);
window.formatTelepon = (phone) => IDHelper.IndonesianFormat.phoneNumber(phone);
window.formatNIK = (nik) => IDHelper.IndonesianFormat.nik(nik);
