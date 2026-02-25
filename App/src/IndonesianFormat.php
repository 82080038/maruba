<?php
/**
 * Indonesian Formatting Helpers
 *
 * Comprehensive helpers for Indonesian formatting including:
 * - Currency (Rupiah)
 * - Dates (DD/MM/YYYY, DD MMMM YYYY)
 * - Numbers (with thousand separators)
 * - Time (24-hour format)
 * - Text translations for UI elements
 * - Date inputs localization
 */

// =========================================
// INDONESIAN FORMATTING HELPERS
// =========================================

class IndonesianFormat {

    // =========================================
    // CURRENCY FORMATTING
    // =========================================

    /**
     * Format number to Indonesian Rupiah
     * @param float|int $amount
     * @param bool $withSymbol Include "Rp" symbol
     * @param int $decimals Number of decimal places
     * @return string
     */
    public static function currency($amount, $withSymbol = true, $decimals = 0) {
        $amount = (float) $amount;

        // Format with Indonesian thousand separator (.) and decimal separator (,)
        $formatted = number_format($amount, $decimals, ',', '.');

        if ($withSymbol) {
            return 'Rp ' . $formatted;
        }

        return $formatted;
    }

    /**
     * Format currency for input fields (without symbol)
     * @param float|int $amount
     * @return string
     */
    public static function currencyInput($amount) {
        return self::currency($amount, false, 2);
    }

    /**
     * Parse currency string back to float
     * @param string $currencyString
     * @return float
     */
    public static function parseCurrency($currencyString) {
        // Remove "Rp", spaces, and convert Indonesian format to standard
        $clean = str_replace(['Rp', ' ', '.'], ['', '', ''], $currencyString);
        $clean = str_replace(',', '.', $clean);
        return (float) $clean;
    }

    // =========================================
    // NUMBER FORMATTING
    // =========================================

    /**
     * Format number with Indonesian thousand separator
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    public static function number($number, $decimals = 0) {
        return number_format($number, $decimals, ',', '.');
    }

    /**
     * Format percentage
     * @param float $percentage
     * @param int $decimals
     * @return string
     */
    public static function percentage($percentage, $decimals = 1) {
        return self::number($percentage, $decimals) . '%';
    }

    // =========================================
    // DATE FORMATTING
    // =========================================

    /**
     * Indonesian month names
     */
    private static $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    /**
     * Indonesian short month names
     */
    private static $shortMonths = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ags',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des'
    ];

    /**
     * Indonesian day names
     */
    private static $days = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu'
    ];

    /**
     * Indonesian short day names
     */
    private static $shortDays = [
        0 => 'Min',
        1 => 'Sen',
        2 => 'Sel',
        3 => 'Rab',
        4 => 'Kam',
        5 => 'Jum',
        6 => 'Sab'
    ];

    /**
     * Format date to Indonesian format (DD/MM/YYYY)
     * @param string|DateTime $date
     * @return string
     */
    public static function date($date) {
        if (empty($date) || $date === '0000-00-00') {
            return '-';
        }

        $timestamp = is_string($date) ? strtotime($date) : $date->getTimestamp();
        return date('d/m/Y', $timestamp);
    }

    /**
     * Format date to Indonesian format with month name (DD MMMM YYYY)
     * @param string|DateTime $date
     * @param bool $shortMonth Use short month name
     * @return string
     */
    public static function dateLong($date, $shortMonth = false) {
        if (empty($date) || $date === '0000-00-00') {
            return '-';
        }

        $timestamp = is_string($date) ? strtotime($date) : $date->getTimestamp();
        $day = date('j', $timestamp);
        $month = (int) date('n', $timestamp);
        $year = date('Y', $timestamp);

        $monthName = $shortMonth ? self::$shortMonths[$month] : self::$months[$month];

        return $day . ' ' . $monthName . ' ' . $year;
    }

    /**
     * Format date with day name (Hari, DD MMMM YYYY)
     * @param string|DateTime $date
     * @param bool $shortDay Use short day name
     * @return string
     */
    public static function dateWithDay($date, $shortDay = false) {
        if (empty($date) || $date === '0000-00-00') {
            return '-';
        }

        $timestamp = is_string($date) ? strtotime($date) : $date->getTimestamp();
        $dayOfWeek = (int) date('w', $timestamp);

        $dayName = $shortDay ? self::$shortDays[$dayOfWeek] : self::$days[$dayOfWeek];
        $dateFormatted = self::dateLong($date, $shortDay);

        return $dayName . ', ' . $dateFormatted;
    }

    /**
     * Format date range
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    public static function dateRange($startDate, $endDate) {
        if (empty($startDate) || empty($endDate)) {
            return '-';
        }

        $start = self::date($startDate);
        $end = self::date($endDate);

        // If same year, omit year from start date
        $startYear = date('Y', strtotime($startDate));
        $endYear = date('Y', strtotime($endDate));

        if ($startYear === $endYear) {
            $start = date('d/m', strtotime($startDate));
        }

        return $start . ' - ' . $end;
    }

    /**
     * Get relative time (e.g., "2 hari yang lalu")
     * @param string|DateTime $date
     * @return string
     */
    public static function timeAgo($date) {
        if (empty($date)) {
            return '-';
        }

        $timestamp = is_string($date) ? strtotime($date) : $date->getTimestamp();
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 0) {
            return 'di masa depan';
        }

        $intervals = [
            ['tahun', 31536000],
            ['bulan', 2592000],
            ['minggu', 604800],
            ['hari', 86400],
            ['jam', 3600],
            ['menit', 60],
            ['detik', 1]
        ];

        foreach ($intervals as $interval) {
            $count = floor($diff / $interval[1]);
            if ($count >= 1) {
                $unit = $interval[0];
                if ($count == 1) {
                    return $count . ' ' . $unit . ' yang lalu';
                } else {
                    return $count . ' ' . $unit . ' yang lalu';
                }
            }
        }

        return 'baru saja';
    }

    // =========================================
    // TIME FORMATTING
    // =========================================

    /**
     * Format time to 24-hour Indonesian format (HH:MM)
     * @param string|DateTime $time
     * @return string
     */
    public static function time($time) {
        if (empty($time)) {
            return '-';
        }

        $timestamp = is_string($time) ? strtotime($time) : $time->getTimestamp();
        return date('H:i', $timestamp);
    }

    /**
     * Format time with seconds (HH:MM:SS)
     * @param string|DateTime $time
     * @return string
     */
    public static function timeWithSeconds($time) {
        if (empty($time)) {
            return '-';
        }

        $timestamp = is_string($time) ? strtotime($time) : $time->getTimestamp();
        return date('H:i:s', $timestamp);
    }

    /**
     * Format date and time (DD/MM/YYYY HH:MM)
     * @param string|DateTime $datetime
     * @return string
     */
    public static function datetime($datetime) {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = is_string($datetime) ? strtotime($datetime) : $datetime->getTimestamp();
        return date('d/m/Y H:i', $timestamp);
    }

    /**
     * Format date and time with day (Hari, DD MMMM YYYY HH:MM)
     * @param string|DateTime $datetime
     * @param bool $shortNames Use short names
     * @return string
     */
    public static function datetimeWithDay($datetime, $shortNames = true) {
        if (empty($datetime)) {
            return '-';
        }

        $datePart = self::dateWithDay($datetime, $shortNames);
        $timePart = self::time($datetime);

        return $datePart . ' ' . $timePart;
    }

    // =========================================
    // TEXT AND UI TRANSLATIONS
    // =========================================

    /**
     * Common Indonesian translations for UI elements
     */
    private static $translations = [
        // Actions
        'add' => 'Tambah',
        'edit' => 'Edit',
        'delete' => 'Hapus',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'submit' => 'Kirim',
        'update' => 'Update',
        'create' => 'Buat',
        'view' => 'Lihat',
        'detail' => 'Detail',
        'back' => 'Kembali',
        'next' => 'Selanjutnya',
        'previous' => 'Sebelumnya',
        'search' => 'Cari',
        'filter' => 'Filter',
        'export' => 'Ekspor',
        'import' => 'Impor',
        'download' => 'Unduh',
        'upload' => 'Unggah',
        'print' => 'Cetak',

        // Status
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'completed' => 'Selesai',
        'processing' => 'Diproses',
        'draft' => 'Draft',

        // Common terms
        'name' => 'Nama',
        'email' => 'Email',
        'phone' => 'Telepon',
        'address' => 'Alamat',
        'date' => 'Tanggal',
        'time' => 'Waktu',
        'amount' => 'Jumlah',
        'total' => 'Total',
        'status' => 'Status',
        'description' => 'Deskripsi',
        'notes' => 'Catatan',
        'type' => 'Tipe',
        'category' => 'Kategori',

        // Member terms
        'member' => 'Anggota',
        'members' => 'Anggota',
        'membership' => 'Keanggotaan',
        'registration' => 'Pendaftaran',

        // Loan terms
        'loan' => 'Pinjaman',
        'loans' => 'Pinjaman',
        'interest' => 'Bunga',
        'principal' => 'Pokok',
        'installment' => 'Angsuran',
        'repayment' => 'Pelunasan',

        // Navigation
        'dashboard' => 'Dashboard',
        'home' => 'Beranda',
        'profile' => 'Profil',
        'settings' => 'Pengaturan',
        'logout' => 'Keluar',
        'login' => 'Masuk',

        // Messages
        'success' => 'Berhasil',
        'error' => 'Error',
        'warning' => 'Peringatan',
        'info' => 'Informasi',
        'loading' => 'Memuat...',
        'please_wait' => 'Mohon tunggu...',
        'no_data' => 'Tidak ada data',
        'confirm_delete' => 'Apakah Anda yakin ingin menghapus?',
        'data_saved' => 'Data berhasil disimpan',
        'data_deleted' => 'Data berhasil dihapus',

        // Table headers
        'no' => 'No',
        'action' => 'Aksi',
        'actions' => 'Aksi',

        // Date/Time
        'today' => 'Hari ini',
        'yesterday' => 'Kemarin',
        'tomorrow' => 'Besok',
        'this_week' => 'Minggu ini',
        'this_month' => 'Bulan ini',
        'this_year' => 'Tahun ini'
    ];

    /**
     * Translate text to Indonesian
     * @param string $key
     * @param string $default Default text if translation not found
     * @return string
     */
    public static function translate($key, $default = '') {
        $key = strtolower($key);
        return self::$translations[$key] ?? ($default ?: $key);
    }

    /**
     * Get all translations
     * @return array
     */
    public static function getTranslations() {
        return self::$translations;
    }

    /**
     * Add custom translation
     * @param string $key
     * @param string $translation
     */
    public static function addTranslation($key, $translation) {
        self::$translations[strtolower($key)] = $translation;
    }

    // =========================================
    // INPUT FORMATTING
    // =========================================

    /**
     * Format phone number to Indonesian format
     * @param string $phone
     * @return string
     */
    public static function phoneNumber($phone) {
        if (empty($phone)) {
            return '';
        }

        // Remove all non-numeric characters
        $clean = preg_replace('/\D/', '', $phone);

        // Indonesian phone number formats
        if (strlen($clean) === 10 && substr($clean, 0, 1) === '8') {
            // Format: 8xxxxxxxxx -> 08xx-xxxx-xxxx
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8);
        } elseif (strlen($clean) === 11 && substr($clean, 0, 2) === '08') {
            // Format: 08xxxxxxxxx -> 08xx-xxxx-xxxx
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8);
        } elseif (strlen($clean) === 12 && substr($clean, 0, 3) === '628') {
            // Format: 628xxxxxxxxx -> 08xx-xxxx-xxxx
            $clean = '08' . substr($clean, 2);
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8);
        } elseif (strlen($clean) === 13 && substr($clean, 0, 3) === '+62') {
            // Format: +628xxxxxxxxx -> 08xx-xxxx-xxxx
            $clean = '08' . substr($clean, 3);
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8);
        }

        // Return as-is if format not recognized
        return $phone;
    }

    /**
     * Format NIK (Indonesian ID number)
     * @param string $nik
     * @return string
     */
    public static function nik($nik) {
        if (empty($nik) || strlen($nik) !== 16) {
            return $nik;
        }

        // Format: xxxxx-xxxx-xxxx-xxxx
        return substr($nik, 0, 4) . '-' . substr($nik, 4, 4) . '-' . substr($nik, 8, 4) . '-' . substr($nik, 12, 4);
    }

    /**
     * Format postal code
     * @param string $postalCode
     * @return string
     */
    public static function postalCode($postalCode) {
        if (empty($postalCode) || strlen($postalCode) !== 5) {
            return $postalCode;
        }

        return $postalCode; // Indonesian postal codes are 5 digits, no formatting needed
    }

    // =========================================
    // UTILITY FUNCTIONS
    // =========================================

    /**
     * Get Indonesian month name
     * @param int $month (1-12)
     * @param bool $short Return short name
     * @return string
     */
    public static function getMonthName($month, $short = false) {
        if ($short) {
            return self::$shortMonths[$month] ?? '';
        }
        return self::$months[$month] ?? '';
    }

    /**
     * Get Indonesian day name
     * @param int $day (0-6, where 0 = Sunday)
     * @param bool $short Return short name
     * @return string
     */
    public static function getDayName($day, $short = false) {
        if ($short) {
            return self::$shortDays[$day] ?? '';
        }
        return self::$days[$day] ?? '';
    }

    /**
     * Check if date is valid
     * @param string $date
     * @param string $format Expected format
     * @return bool
     */
    public static function isValidDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Get current date in Indonesian format
     * @return string
     */
    public static function today() {
        return self::date(date('Y-m-d'));
    }

    /**
     * Get current date and time in Indonesian format
     * @return string
     */
    public static function now() {
        return self::datetime(date('Y-m-d H:i:s'));
    }
}

// =========================================
// HELPER FUNCTIONS FOR EASY USE
// =========================================

/**
 * Format currency to Indonesian Rupiah
 * @param float|int $amount
 * @param bool $withSymbol
 * @param int $decimals
 * @return string
 */
function format_uang($amount, $withSymbol = true, $decimals = 0) {
    return IndonesianFormat::currency($amount, $withSymbol, $decimals);
}

/**
 * Format date to Indonesian format
 * @param string|DateTime $date
 * @return string
 */
function format_tanggal($date) {
    return IndonesianFormat::date($date);
}

/**
 * Format date to long Indonesian format
 * @param string|DateTime $date
 * @param bool $shortMonth
 * @return string
 */
function format_tanggal_panjang($date, $shortMonth = false) {
    return IndonesianFormat::dateLong($date, $shortMonth);
}

/**
 * Format date and time
 * @param string|DateTime $datetime
 * @return string
 */
function format_tanggal_waktu($datetime) {
    return IndonesianFormat::datetime($datetime);
}

/**
 * Format number with Indonesian separators
 * @param float|int $number
 * @param int $decimals
 * @return string
 */
function format_angka($number, $decimals = 0) {
    return IndonesianFormat::number($number, $decimals);
}

/**
 * Translate text to Indonesian
 * @param string $key
 * @param string $default
 * @return string
 */
function translate($key, $default = '') {
    return IndonesianFormat::translate($key, $default);
}

/**
 * Get relative time
 * @param string|DateTime $date
 * @return string
 */
function waktu_lalu($date) {
    return IndonesianFormat::timeAgo($date);
}

/**
 * Format phone number
 * @param string $phone
 * @return string
 */
function format_telepon($phone) {
    return IndonesianFormat::phoneNumber($phone);
}

/**
 * Format NIK
 * @param string $nik
 * @return string
 */
function format_nik($nik) {
    return IndonesianFormat::nik($nik);
}
?>
