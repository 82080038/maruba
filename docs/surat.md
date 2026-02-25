# Fitur Surat-Surat Koperasi

## Overview

Fitur ini menyediakan template surat-surat koperasi yang dapat diunduh dalam format PDF. Surat-surat ini mencakup berbagai keperluan administrasi koperasi.

## Daftar Surat yang Tersedia

### 1. Surat Lamaran Kerja
- **URL**: `/surat/lamaran-kerja`
- **Deskripsi**: Format surat lamaran kerja untuk calon karyawan KSP Lam Gabe Jaya
- **Kelengkapan**: KTP, CV, Ijazah, Transkrip, SKCK, Sertifikat pendukung

### 2. Surat Permohonan Menjadi Anggota
- **URL**: `/surat/permohonan-anggota`
- **Deskripsi**: Formulir pendaftaran anggota baru koperasi
- **Kelengkapan**: Fotokopi KTP, Pas foto 3x4, Formulir data anggota

### 3. Daftar Sah Anggota
- **URL**: `/surat/daftar-sah`
- **Deskripsi**: Format daftar resmi anggota koperasi untuk administrasi internal
- **Format**: Tabel dengan kolom No, Nama, No. Anggota, No. KTP, Alamat, Tanggal Bergabung, Tanda Tangan

### 4. Surat Permohonan Pinjaman Dana
- **URL**: `/surat/permohonan-pinjaman`
- **Deskripsi**: Formulir pengajuan pinjaman dana
- **Kelengkapan**: Fotokopi KTP, Kartu anggota, Slip gaji/usaha, Data agunan

### 5. Surat Kesepakatan Bersama (SKB)
- **URL**: `/surat/skb`
- **Deskripsi**: Perjanjian pinjaman antara nasabah dan koperasi
- **Ketentuan**: Jangka waktu, Jasa/bunga, Agunan, Sanksi wanprestasi

## Cara Mengakses

1. Login ke sistem dengan akun yang memiliki permission `documents`
2. Klik menu **Surat-Surat** di sidebar
3. Pilih surat yang diinginkan
4. Klik tombol **Unduh PDF**

## Permission

Fitur ini memerlukan permission:
- `documents.view` - Melihat daftar surat
- `documents.download` - Mengunduh PDF surat

Roles yang memiliki permission:
- **Admin**: view, download
- **Manajer**: view, download  
- **Kasir**: view, download

## Teknologi

- **PDF Generation**: TCPDF (jika tersedia) atau fallback ke HTML print
- **Styling**: Times New Roman, format surat resmi
- **Responsive**: Print-friendly layout

## Struktur File

```
App/src/Controllers/SuratController.php - Controller untuk mengelola surat
App/src/Views/surat/index.php - Halaman daftar surat
App/src/Views/surat/print_layout.php - Layout untuk print fallback
plan/surat.md - Source template surat
```

## Customization

Template surat dapat dimodifikasi di:
- `SuratController.php` method `getLamaranKerjaContent()`, `getPermohonanAnggotaContent()`, dll
- Format HTML dapat disesuaikan dengan kebutuhan

## Installation

1. Pastikan TCPDF terinstall (opsional):
   ```bash
   composer require tecnickcom/tcpdf
   ```

2. Update permissions di database:
   ```sql
   UPDATE roles SET permissions = JSON_SET(permissions, '$.documents', JSON_ARRAY('view', 'download')) WHERE name IN ('admin', 'manajer', 'kasir');
   ```

## Troubleshooting

### PDF tidak terdownload
- Pastikan permission `documents.download` sudah ada
- Cek apakah TCPDF terinstall (opsional, fallback ke HTML print)

### Layout tidak rapi
- Template menggunakan CSS print-friendly
- Pastikan printer settings menggunakan portrait orientation

## Future Enhancements

- [ ] Generate PDF dengan data dinamis dari database
- [ ] Template surat yang dapat diedit
- [ ] Digital signature
- [ ] Bulk download
- [ ] History download
