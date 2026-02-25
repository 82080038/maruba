// =========================================
// INDONESIAN JAVASCRIPT FORMATTING UTILITIES
// =========================================

// Indonesian locale data
const IndonesianLocale = {
    // Month names
    months: {
        long: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        short: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des']
    },

    // Day names
    days: {
        long: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        short: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
    },

    // Translations for common UI elements
    translations: {
        // Actions
        'add': 'Tambah',
        'edit': 'Edit',
        'delete': 'Hapus',
        'save': 'Simpan',
        'cancel': 'Batal',
        'submit': 'Kirim',
        'update': 'Update',
        'create': 'Buat',
        'view': 'Lihat',
        'detail': 'Detail',
        'back': 'Kembali',
        'next': 'Selanjutnya',
        'previous': 'Sebelumnya',
        'search': 'Cari',
        'filter': 'Filter',
        'export': 'Ekspor',
        'import': 'Impor',
        'download': 'Unduh',
        'upload': 'Unggah',
        'print': 'Cetak',

        // Status
        'active': 'Aktif',
        'inactive': 'Tidak Aktif',
        'pending': 'Menunggu',
        'approved': 'Disetujui',
        'rejected': 'Ditolak',
        'completed': 'Selesai',
        'processing': 'Diproses',
        'draft': 'Draft',

        // Common terms
        'name': 'Nama',
        'email': 'Email',
        'phone': 'Telepon',
        'address': 'Alamat',
        'date': 'Tanggal',
        'time': 'Waktu',
        'amount': 'Jumlah',
        'total': 'Total',
        'status': 'Status',
        'description': 'Deskripsi',
        'notes': 'Catatan',
        'type': 'Tipe',
        'category': 'Kategori',

        // Messages
        'success': 'Berhasil',
        'error': 'Error',
        'warning': 'Peringatan',
        'info': 'Informasi',
        'loading': 'Memuat...',
        'please_wait': 'Mohon tunggu...',
        'no_data': 'Tidak ada data',
        'confirm_delete': 'Apakah Anda yakin ingin menghapus?',
        'data_saved': 'Data berhasil disimpan',
        'data_deleted': 'Data berhasil dihapus'
    }
};

// =========================================
// INDONESIAN FORMATTING CLASS
// =========================================

class IndonesianFormat {
    /**
     * Format number to Indonesian Rupiah
     * @param {number} amount
     * @param {boolean} withSymbol
     * @param {number} decimals
     * @returns {string}
     */
    static currency(amount, withSymbol = true, decimals = 0) {
        const numAmount = parseFloat(amount) || 0;

        // Format with Indonesian thousand separator (.) and decimal separator (,)
        const formatted = numAmount.toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });

        return withSymbol ? 'Rp ' + formatted : formatted;
    }

    /**
     * Format currency for input fields
     * @param {number} amount
     * @returns {string}
     */
    static currencyInput(amount) {
        return this.currency(amount, false, 2);
    }

    /**
     * Parse currency string back to number
     * @param {string} currencyString
     * @returns {number}
     */
    static parseCurrency(currencyString) {
        if (typeof currencyString !== 'string') return 0;

        // Remove "Rp", spaces, and convert Indonesian format to standard
        let clean = currencyString.replace(/Rp\s?/g, '').replace(/\s/g, '').replace(/\./g, '').replace(/,/g, '.');
        return parseFloat(clean) || 0;
    }

    /**
     * Format number with Indonesian separators
     * @param {number} number
     * @param {number} decimals
     * @returns {string}
     */
    static number(number, decimals = 0) {
        const num = parseFloat(number) || 0;
        return num.toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    /**
     * Format percentage
     * @param {number} percentage
     * @param {number} decimals
     * @returns {string}
     */
    static percentage(percentage, decimals = 1) {
        return this.number(percentage, decimals) + '%';
    }

    /**
     * Format date to Indonesian format (DD/MM/YYYY)
     * @param {string|Date} date
     * @returns {string}
     */
    static date(date) {
        if (!date || date === '0000-00-00') return '-';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '-';

        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();

        return `${day}/${month}/${year}`;
    }

    /**
     * Format date to long Indonesian format (DD MMMM YYYY)
     * @param {string|Date} date
     * @param {boolean} shortMonth
     * @returns {string}
     */
    static dateLong(date, shortMonth = false) {
        if (!date || date === '0000-00-00') return '-';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '-';

        const day = d.getDate();
        const month = shortMonth ? IndonesianLocale.months.short[d.getMonth()] : IndonesianLocale.months.long[d.getMonth()];
        const year = d.getFullYear();

        return `${day} ${month} ${year}`;
    }

    /**
     * Format date with day name
     * @param {string|Date} date
     * @param {boolean} shortDay
     * @returns {string}
     */
    static dateWithDay(date, shortDay = false) {
        if (!date || date === '0000-00-00') return '-';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '-';

        const dayName = shortDay ? IndonesianLocale.days.short[d.getDay()] : IndonesianLocale.days.long[d.getDay()];
        const dateFormatted = this.dateLong(date, shortDay);

        return `${dayName}, ${dateFormatted}`;
    }

    /**
     * Format date range
     * @param {string} startDate
     * @param {string} endDate
     * @returns {string}
     */
    static dateRange(startDate, endDate) {
        if (!startDate || !endDate) return '-';

        const start = this.date(startDate);
        const end = this.date(endDate);

        // If same year, omit year from start date
        const startYear = new Date(startDate).getFullYear();
        const endYear = new Date(endDate).getFullYear();

        if (startYear === endYear) {
            const startParts = start.split('/');
            return `${startParts[0]}/${startParts[1]} - ${end}`;
        }

        return `${start} - ${end}`;
    }

    /**
     * Get relative time in Indonesian
     * @param {string|Date} date
     * @returns {string}
     */
    static timeAgo(date) {
        if (!date) return '-';

        const timestamp = new Date(date).getTime();
        const now = Date.now();
        const diff = now - timestamp;

        if (diff < 0) return 'di masa depan';

        const intervals = [
            { label: 'tahun', seconds: 31536000000 },
            { label: 'bulan', seconds: 2592000000 },
            { label: 'minggu', seconds: 604800000 },
            { label: 'hari', seconds: 86400000 },
            { label: 'jam', seconds: 3600000 },
            { label: 'menit', seconds: 60000 },
            { label: 'detik', seconds: 1000 }
        ];

        for (const interval of intervals) {
            const count = Math.floor(diff / interval.seconds);
            if (count >= 1) {
                const unit = interval.label;
                return `${count} ${unit} yang lalu`;
            }
        }

        return 'baru saja';
    }

    /**
     * Format time to 24-hour Indonesian format
     * @param {string|Date} time
     * @returns {string}
     */
    static time(time) {
        if (!time) return '-';

        const d = new Date(time);
        if (isNaN(d.getTime())) return '-';

        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');

        return `${hours}:${minutes}`;
    }

    /**
     * Format time with seconds
     * @param {string|Date} time
     * @returns {string}
     */
    static timeWithSeconds(time) {
        if (!time) return '-';

        const d = new Date(time);
        if (isNaN(d.getTime())) return '-';

        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');

        return `${hours}:${minutes}:${seconds}`;
    }

    /**
     * Format datetime
     * @param {string|Date} datetime
     * @returns {string}
     */
    static datetime(datetime) {
        if (!datetime) return '-';

        const d = new Date(datetime);
        if (isNaN(d.getTime())) return '-';

        const datePart = this.date(datetime);
        const timePart = this.time(datetime);

        return `${datePart} ${timePart}`;
    }

    /**
     * Format datetime with day
     * @param {string|Date} datetime
     * @param {boolean} shortNames
     * @returns {string}
     */
    static datetimeWithDay(datetime, shortNames = true) {
        if (!datetime) return '-';

        const datePart = this.dateWithDay(datetime, shortNames);
        const timePart = this.time(datetime);

        return `${datePart} ${timePart}`;
    }

    /**
     * Translate text to Indonesian
     * @param {string} key
     * @param {string} defaultText
     * @returns {string}
     */
    static translate(key, defaultText = '') {
        const lowerKey = key.toLowerCase();
        return IndonesianLocale.translations[lowerKey] || defaultText || key;
    }

    /**
     * Format phone number to Indonesian format
     * @param {string} phone
     * @returns {string}
     */
    static phoneNumber(phone) {
        if (!phone) return '';

        // Remove all non-numeric characters
        const clean = phone.replace(/\D/g, '');

        // Indonesian phone number formats
        if (clean.length === 10 && clean.startsWith('8')) {
            // Format: 8xxxxxxxxx -> 08xx-xxxx-xxxx
            return clean.substring(0, 4) + '-' + clean.substring(4, 8) + '-' + clean.substring(8);
        } else if (clean.length === 11 && clean.startsWith('08')) {
            // Format: 08xxxxxxxxx -> 08xx-xxxx-xxxx
            return clean.substring(0, 4) + '-' + clean.substring(4, 8) + '-' + clean.substring(8);
        } else if (clean.length === 12 && clean.startsWith('628')) {
            // Format: 628xxxxxxxxx -> 08xx-xxxx-xxxx
            const formatted = '08' + clean.substring(2);
            return formatted.substring(0, 4) + '-' + formatted.substring(4, 8) + '-' + formatted.substring(8);
        } else if (clean.length === 13 && clean.startsWith('62')) {
            // Format: 628xxxxxxxxx -> 08xx-xxxx-xxxx
            const formatted = '08' + clean.substring(2);
            return formatted.substring(0, 4) + '-' + formatted.substring(4, 8) + '-' + formatted.substring(8);
        }

        return phone; // Return as-is if format not recognized
    }

    /**
     * Format NIK
     * @param {string} nik
     * @returns {string}
     */
    static nik(nik) {
        if (!nik || nik.length !== 16) return nik;

        return nik.substring(0, 4) + '-' + nik.substring(4, 8) + '-' + nik.substring(8, 12) + '-' + nik.substring(12);
    }

    /**
     * Get Indonesian month name
     * @param {number} month (1-12)
     * @param {boolean} short
     * @returns {string}
     */
    static getMonthName(month, short = false) {
        if (month < 1 || month > 12) return '';
        return short ? IndonesianLocale.months.short[month - 1] : IndonesianLocale.months.long[month - 1];
    }

    /**
     * Get Indonesian day name
     * @param {number} day (0-6, where 0 = Sunday)
     * @param {boolean} short
     * @returns {string}
     */
    static getDayName(day, short = false) {
        if (day < 0 || day > 6) return '';
        return short ? IndonesianLocale.days.short[day] : IndonesianLocale.days.long[day];
    }

    /**
     * Get current date in Indonesian format
     * @returns {string}
     */
    static today() {
        return this.date(new Date());
    }

    /**
     * Get current datetime in Indonesian format
     * @returns {string}
     */
    static now() {
        return this.datetime(new Date());
    }
}

// =========================================
// GLOBAL FUNCTIONS FOR EASY ACCESS
// =========================================

/**
 * Format currency to Indonesian Rupiah
 * @param {number} amount
 * @param {boolean} withSymbol
 * @param {number} decimals
 * @returns {string}
 */
function formatUang(amount, withSymbol = true, decimals = 0) {
    return IndonesianFormat.currency(amount, withSymbol, decimals);
}

/**
 * Format date to Indonesian format
 * @param {string|Date} date
 * @returns {string}
 */
function formatTanggal(date) {
    return IndonesianFormat.date(date);
}

/**
 * Format date to long Indonesian format
 * @param {string|Date} date
 * @param {boolean} shortMonth
 * @returns {string}
 */
function formatTanggalPanjang(date, shortMonth = false) {
    return IndonesianFormat.dateLong(date, shortMonth);
}

/**
 * Format datetime
 * @param {string|Date} datetime
 * @returns {string}
 */
function formatTanggalWaktu(datetime) {
    return IndonesianFormat.datetime(datetime);
}

/**
 * Format number with Indonesian separators
 * @param {number} number
 * @param {number} decimals
 * @returns {string}
 */
function formatAngka(number, decimals = 0) {
    return IndonesianFormat.number(number, decimals);
}

/**
 * Translate text to Indonesian
 * @param {string} key
 * @param {string} defaultText
 * @returns {string}
 */
function terjemahkan(key, defaultText = '') {
    return IndonesianFormat.translate(key, defaultText);
}

/**
 * Get relative time
 * @param {string|Date} date
 * @returns {string}
 */
function waktuLalu(date) {
    return IndonesianFormat.timeAgo(date);
}

/**
 * Format phone number
 * @param {string} phone
 * @returns {string}
 */
function formatTelepon(phone) {
    return IndonesianFormat.phoneNumber(phone);
}

/**
 * Format NIK
 * @param {string} nik
 * @returns {string}
 */
function formatNIK(nik) {
    return IndonesianFormat.nik(nik);
}

// =========================================
// JQUERY EXTENSIONS FOR INDONESIAN FORMATTING
// =========================================

/**
 * jQuery extension for Indonesian formatting
 */
$.fn.indonesianFormat = function(options = {}) {
    return this.each(function() {
        const $element = $(this);
        const type = options.type || $element.data('format-type');

        switch (type) {
            case 'currency':
                const amount = parseFloat($element.text().replace(/[^\d.,-]/g, '').replace(',', '.'));
                if (!isNaN(amount)) {
                    $element.text(IndonesianFormat.currency(amount, options.withSymbol, options.decimals));
                }
                break;

            case 'date':
                const dateText = $element.text().trim();
                if (dateText && dateText !== '-' && dateText !== '0000-00-00') {
                    $element.text(IndonesianFormat.date(dateText));
                }
                break;

            case 'datetime':
                const datetimeText = $element.text().trim();
                if (datetimeText && datetimeText !== '-') {
                    $element.text(IndonesianFormat.datetime(datetimeText));
                }
                break;

            case 'number':
                const numberText = $element.text().trim();
                const number = parseFloat(numberText.replace(/[^\d.,-]/g, '').replace(',', '.'));
                if (!isNaN(number)) {
                    $element.text(IndonesianFormat.number(number, options.decimals));
                }
                break;

            case 'phone':
                const phoneText = $element.text().trim();
                if (phoneText) {
                    $element.text(IndonesianFormat.phoneNumber(phoneText));
                }
                break;

            case 'nik':
                const nikText = $element.text().trim();
                if (nikText) {
                    $element.text(IndonesianFormat.nik(nikText));
                }
                break;
        }
    });
};

// =========================================
// AUTO-FORMATTING FOR ELEMENTS WITH DATA ATTRIBUTES
// =========================================

$(document).ready(function() {
    console.log('🇮🇩 Indonesian Formatting System Initialized');

    // Auto-format elements with data-format-type attribute
    $('[data-format-type]').each(function() {
        const $element = $(this);
        const options = {
            type: $element.data('format-type'),
            withSymbol: $element.data('format-symbol') !== false,
            decimals: $element.data('format-decimals') || 0
        };
        $element.indonesianFormat(options);
    });

    // Auto-format currency inputs
    $('input[data-format-type="currency"]').on('input', function() {
        const $input = $(this);
        const value = IndonesianFormat.parseCurrency($input.val());
        const formatted = IndonesianFormat.currencyInput(value);
        $input.val(formatted);
    });

    // Auto-format phone inputs
    $('input[data-format-type="phone"]').on('input', function() {
        const $input = $(this);
        const formatted = IndonesianFormat.phoneNumber($input.val());
        if (formatted !== $input.val()) {
            $input.val(formatted);
        }
    });

    // Auto-format NIK inputs
    $('input[data-format-type="nik"]').on('input', function() {
        const $input = $(this);
        const formatted = IndonesianFormat.nik($input.val().replace(/-/g, ''));
        if (formatted !== $input.val()) {
            $input.val(formatted);
        }
    });

    // Update time displays every minute
    setInterval(function() {
        $('.auto-time-update').each(function() {
            const $element = $(this);
            const timestamp = $element.data('timestamp');
            if (timestamp) {
                $element.text(IndonesianFormat.timeAgo(timestamp));
            }
        });
    }, 60000); // Update every minute
});

// =========================================
// DATEPICKER INDONESIAN LOCALIZATION
// =========================================

/**
 * Initialize Indonesian datepicker
 */
function initializeIndonesianDatepicker(selector, options = {}) {
    const defaultOptions = {
        format: 'dd/mm/yyyy',
        language: 'id',
        autoclose: true,
        todayHighlight: true,
        todayBtn: 'linked'
    };

    // Indonesian language for datepicker
    if (typeof $.fn.datepicker !== 'undefined') {
        $.fn.datepicker.dates['id'] = {
            days: IndonesianLocale.days.long,
            daysShort: IndonesianLocale.days.short,
            daysMin: IndonesianLocale.days.short,
            months: IndonesianLocale.months.long,
            monthsShort: IndonesianLocale.months.short,
            today: 'Hari Ini',
            clear: 'Bersihkan',
            format: 'dd/mm/yyyy',
            titleFormat: 'MM yyyy',
            weekStart: 1
        };
    }

    const config = $.extend({}, defaultOptions, options);
    $(selector).datepicker(config);
}

// Make IndonesianFormat globally available
window.IndonesianFormat = IndonesianFormat;