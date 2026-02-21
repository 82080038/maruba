<?php
namespace App\Helpers;

class UiHelper
{
    public static function t(string $key): string
    {
        $map = [
            'dashboard' => 'Dasbor',
            'loans' => 'Pinjaman',
            'repayments' => 'Angsuran',
            'disbursement' => 'Pencairan',
            'members' => 'Anggota',
            'products' => 'Produk',
            'surveys' => 'Survei',
            'reports' => 'Laporan',
            'audit' => 'Audit Log',
            'users' => 'Pengguna',
            'documents' => 'Surat-Surat',
            'logout' => 'Keluar',
            'profile' => 'Profil',
            'settings' => 'Pengaturan',
            'main' => 'Utama',
            'transaction' => 'Transaksi',
            'master_data' => 'Data Master',
            'report' => 'Laporan',
            'system' => 'Sistem',
            'loading' => 'Memuat…',
            'outstanding' => 'Sisa Pinjaman',
            'anggota aktif' => 'Anggota Aktif',
            'pinjaman berjalan' => 'Pinjaman Berjalan',
            'npl' => 'NPL (kredit bermasalah)',
            'npl count' => 'Jumlah NPL',
            'approved' => 'Disetujui',
            'survey_status' => 'Survei',
            'disbursed' => 'Dicairkan',
            'draft' => 'Draf',
            'paid' => 'Lunas',
            'late' => 'Terlambat',
            'pending' => 'Menunggu',
            'rejected' => 'Ditolak',
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'loan' => 'Pinjaman',
            'savings' => 'Simpanan'
        ];
        $k = strtolower(trim($key));
        return $map[$k] ?? ucfirst($key);
    }

    public static function formatNumber($value, int $decimals = 0): string
    {
        $n = is_numeric($value) ? (float)$value : 0;
        return number_format($n, $decimals, ',', '.');
    }

    public static function formatRupiah($value, int $decimals = 0): string
    {
        return 'Rp ' . self::formatNumber($value, $decimals);
    }

    public static function formatDateId($dateString, bool $withDay = false): string
    {
        if (empty($dateString)) return '-';
        $ts = strtotime($dateString);
        if ($ts === false) return $dateString;
        $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $months = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $d = (int)date('j', $ts);
        $m = (int)date('n', $ts);
        $y = date('Y', $ts);
        $base = $d.' '.$months[$m].' '.$y;
        return $withDay ? ($days[(int)date('w', $ts)].', '.$base) : $base;
    }

    public static function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strpos($digits, '62') === 0) {
            $digits = '0' . substr($digits, 2);
        }
        return $digits;
    }

    public static function formatNpwp(string $npwp): string
    {
        $digits = preg_replace('/\D+/', '', $npwp);
        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d)(\d{3})(\d{3})$/', '$1.$2.$3.$4-$5.$6', $digits) ?: $npwp;
    }

    public static function statusInfo(string $status): array
    {
        $s = strtolower(trim($status));
        $map = [
            'approved' => ['text' => 'Disetujui', 'class' => 'success'],
            'survey' => ['text' => 'Survei', 'class' => 'warning'],
            'disbursed' => ['text' => 'Dicairkan', 'class' => 'primary'],
            'draft' => ['text' => 'Draf', 'class' => 'secondary'],
            'paid' => ['text' => 'Lunas', 'class' => 'success'],
            'late' => ['text' => 'Terlambat', 'class' => 'danger'],
            'pending' => ['text' => 'Menunggu', 'class' => 'warning'],
            'rejected' => ['text' => 'Ditolak', 'class' => 'danger'],
        ];
        return $map[$s] ?? ['text' => ucfirst($status), 'class' => 'secondary'];
    }
}
