// Helper JavaScript utilitas lokal Indonesia
// Namespace sederhana
window.IDHelper = (function () {
  const locale = 'id-ID';
  const tz = 'Asia/Jakarta';

  const numberFormatter = (decimals = 0, useGrouping = true) =>
    new Intl.NumberFormat(locale, { minimumFractionDigits: decimals, maximumFractionDigits: decimals, useGrouping });

  const currencyFormatter = new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });

  function formatNumber(value, decimals = 0) {
    const n = Number(value ?? 0);
    return numberFormatter(decimals).format(isNaN(n) ? 0 : n);
  }

  function formatCurrency(value, withSymbol = true) {
    const n = Number(value ?? 0);
    if (withSymbol) return currencyFormatter.format(isNaN(n) ? 0 : n);
    return formatNumber(n, 0);
  }

  function parseNumber(str) {
    if (typeof str !== 'string') return Number(str) || 0;
    // Hilangkan titik pemisah ribuan, ganti koma ke titik
    const normalized = str.replace(/\./g, '').replace(/,/g, '.').trim();
    const num = Number(normalized);
    return isNaN(num) ? 0 : num;
  }

  function formatDate(date, options) {
    const d = date instanceof Date ? date : new Date(date);
    if (isNaN(d.getTime())) return '';
    const fmt = new Intl.DateTimeFormat(locale, options || { day: '2-digit', month: 'long', year: 'numeric', timeZone: tz });
    return fmt.format(d);
  }

  function formatDateInput(str) {
    // Normalisasi dd/mm/yyyy -> yyyy-mm-dd
    const m = (str || '').match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/);
    if (!m) return '';
    const [_, d, mo, y] = m;
    const year = y.length === 2 ? '20' + y : y;
    return `${year.padStart(4, '0')}-${mo.padStart(2, '0')}-${d.padStart(2, '0')}`;
  }

  function fromDateInput(str) {
    const iso = formatDateInput(str);
    return iso ? new Date(iso) : null;
  }

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

  function terbilangRupiah(n) {
    const t = terbilang(n).trim();
    return t ? `${t} rupiah` : '';
  }

  function validateEmail(str) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str || '');
  }

  function validatePhoneID(str) {
    return /^(\+62|62|0)8\d{7,11}$/.test((str || '').replace(/\D/g, ''));
  }

  function validateNIK(str) {
    return /^\d{16}$/.test((str || '').trim());
  }

  function validateNPWP(str) {
    const digits = (str || '').replace(/\D/g, '');
    return digits.length === 15;
  }

  function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function slugify(str) {
    return (str || '')
      .toLowerCase()
      .normalize('NFD').replace(/[^\w\s-]/g, '')
      .trim()
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-');
  }

  function debounce(fn, wait = 300) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

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

  return {
    formatNumber,
    formatCurrency,
    parseNumber,
    formatDate,
    formatDateInput,
    fromDateInput,
    humanizeTime,
    terbilang,
    terbilangRupiah,
    validateEmail,
    validatePhoneID,
    validateNIK,
    validateNPWP,
    escapeHtml,
    slugify,
    debounce,
    throttle,
  };
})();
