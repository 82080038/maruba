# Maruba – Koperasi Management System

Aplikasi manajemen koperasi sederhana berbasis PHP yang mendukung autentikasi, peran/izin (RBAC), pengelolaan anggota, pengajuan & pencairan pinjaman, survei lapangan dengan geotagging, penagihan angsuran, laporan, audit log, serta API JSON untuk integrasi.

## Fitur Utama
- Autentikasi pengguna dan RBAC berbasis JSON permissions di tabel `roles`.
- Dashboard metrik (outstanding, anggota aktif, pinjaman berjalan, NPL%) dan aktivitas terbaru (audit log).
- Modul Anggota dengan dukungan geolokasi (lat/lng) dan endpoint update geo.
- Modul Produk (pinjaman/tabungan).
- Alur Pinjaman: pengajuan → survei → persetujuan → pencairan → penagihan.
- Modul Survei lapangan dengan catatan dan koordinat lokasi.
- Modul Pencairan pinjaman (dengan unggah dokumen direncanakan di `App/public/uploads/`).
- Modul Angsuran (penagihan dan pencatatan pembayaran).
- Laporan ringkas (Outstanding, NPL count, dst.).
- Audit log untuk pelacakan aktivitas penting.
- API JSON: daftar anggota & survei, serta endpoint POST untuk update geo.
- Templat Surat (unduhan PDF/print-friendly) untuk administrasi koperasi.

## Stack Teknologi
- PHP 8.1+ (tanpa framework), Apache 2.4+
- MySQL/MariaDB via PDO
- Bootstrap 5, sedikit JavaScript vanilla (+ helper lokal `helpers-id.js`)
- ModRewrite Apache untuk clean URL (opsional)

## Struktur Proyek (ringkas)
```
App/
  public/
    index.php            # Front controller
    assets/              # CSS/JS statis
    .htaccess            # (opsional) proteksi/aturan
  schema.sql             # Skema database
  seed_permissions.sql   # Seed data & permissions contoh
  src/
    Controllers/         # Controller modular
    Views/               # View (layout & halaman)
    Helpers/             # Helper (Auth, format, dll.)
    Router.php           # Router sederhana
    Database.php         # Koneksi PDO
    bootstrap.php        # Bootstrap & konstanta URL
.htaccess                # Rewrite rules untuk subdirektori /maruba
index.php                # Redirector ke App/public/index.php
docs/, plan/             # Dokumen & rencana
```

## Prasyarat
- Apache 2.4+ dengan `mod_rewrite` aktif
- PHP 8.1+ dengan ekstensi PDO MySQL
- MySQL/MariaDB 5.7+ / 10.4+

## Instalasi & Konfigurasi
1) Clone repo
```
git clone https://github.com/82080038/maruba.git
cd maruba
```

2) Buat database
```
mysql -u root -p -e "CREATE DATABASE maruba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p maruba < App/schema.sql
mysql -u root -p maruba < App/seed_permissions.sql
```

3) Konfigurasi lingkungan
Salin file `.env` (buat baru) di `App/.env`:
```
DB_HOST=localhost
DB_NAME=maruba
DB_USER=root
DB_PASS=your_password

# BASE_URL aplikasi saat berjalan di subdirektori (default: /maruba)
BASE_URL=/maruba
APP_ENV=production
APP_NAME="KSP Lam Gabe Jaya"
```

4) Konfigurasi Apache (subdirektori `/maruba`)
- Pastikan `AllowOverride All` untuk `/var/www/html`.
- Aktifkan `mod_rewrite`:
```
sudo a2enmod rewrite
sudo systemctl restart apache2
```
- Pastikan `.htaccess` di root repo berisi `RewriteBase /maruba` (sudah disertakan).

Jika aplikasi dipasang di root domain (bukan subdir):
- Ubah `BASE_URL` di `App/src/bootstrap.php` dan/atau `App/.env` menjadi string kosong `""`.
- Sesuaikan `RewriteBase /` di `.htaccess`.

## Menjalankan Aplikasi
- Akses login:
  - Dengan rewrite aktif: `http://localhost/maruba/login`
  - Tanpa rewrite: `http://localhost/maruba/index.php/login`

> Catatan: Pastikan telah membuat user pada tabel `users` (password gunakan `password_hash()` PHP). Seed bawaan berfokus ke permissions & contoh data; pembuatan akun admin perlu dilakukan manual sesuai kebutuhan Anda.

### Contoh Kredensial Demo (dummy)
Gunakan ini hanya untuk pengujian lokal (hapus di produksi):

```
Username: admin
Password: admin123
Peran: admin (memiliki akses penuh)
```

Jika akun belum ada, buat manual di DB (kolom password harus hasil `password_hash('admin123', PASSWORD_DEFAULT)`).

## API
- GET `/api/members` → JSON daftar anggota
- GET `/api/surveys` → JSON daftar survei
- POST `/api/members/geo` → perbarui lat/lng anggota
- POST `/api/surveys/geo` → perbarui lat/lng survei

Contoh POST (x-www-form-urlencoded/JSON):
```
POST /api/members/geo
{ "member_id": 1, "lat": -2.65, "lng": 99.05 }
```

### Panduan Pengujian Cepat (curl)
```
# Cek login page (harus 200)
curl -I http://localhost/maruba/login

# Ambil data anggota (public GET)
curl -s http://localhost/maruba/api/members | jq . | head

# Update geo anggota (POST)
curl -s -X POST \
  -H 'Content-Type: application/json' \
  -d '{"member_id":1,"lat":-2.651,"lng":99.051}' \
  http://localhost/maruba/api/members/geo
```

## Konfigurasi URL Helper
- `BASE_URL` dan `PUBLIC_URL` diatur di `App/src/bootstrap.php`.
- Gunakan helper `route_url('path')` untuk link rute, dan `asset_url('assets/..')` untuk aset.
- Saat rewrite non-aktif, rute akan menggunakan `index.php` agar tetap berfungsi.

## Keamanan & Kebersihan Repo
Sudah diabaikan melalui `.gitignore`:
- `App/.env`, `App/public/uploads/`, `App/public/storage/`
- Kunci/credential lokal (`.git-credentials`, `maruba_*`, `*.key`, `*.pem`)
- Skrip lokal sementara (`setup_github.sh`, `fix_apache.sh`, `fix_permissions.php`, `update_permissions.php`, `update_docs_perm.sql`)
- `vendor/`, `composer.lock` (belum digunakan; tambahkan bila kelak memakai Composer)

Rekomendasi:
- Nonaktifkan `display_errors` di produksi (`APP_ENV=production`).
- Batasi permission direktori upload ke minimal yang dibutuhkan webserver.
- Jangan commit credential/token apa pun ke repo publik.

## Catatan Pengembangan
- Layout admin: sidebar kiri + header navbar; responsive.
- Peta/Leaflet akan diintegrasikan berikutnya untuk input koordinat di modul anggota & survei.
- Gunakan `App/seed_permissions.sql` untuk contoh struktur izin per-peran.

## Tangkapan Layar
Tambahkan file gambar ke repo untuk pratinjau (contoh path di bawah). Jika sudah tersedia, README akan menampilkannya otomatis.

![Dashboard](docs/screenshots/dashboard.png)

## Troubleshooting
- 404 atau URL berulang `index.php/index.php`: periksa `RewriteBase` dan `BASE_URL`.
- Halaman kosong: aktifkan sementara `display_errors` dan cek log Apache.
- Aset 404: pastikan `asset_url()` menghasilkan path sesuai `BASE_URL`.

## Deployment Singkat
1) Pastikan `BASE_URL` sesuai target (root/subdir)
2) `AllowOverride All` + `mod_rewrite` aktif
3) Import schema + seed, buat akun admin
4) Set permission direktori upload (bila digunakan)
5) Reload Apache

## Contributing
Terbuka untuk kontribusi lewat Pull Request.

- Buat issue terlebih dahulu untuk perubahan besar.
- Gunakan branch feature: `feature/nama-fitur` atau fix: `fix/bug-deskriptif`.
- Gaya commit: conventional commits (contoh: `feat(loans): add disbursement form`).
- Tambahkan deskripsi PR yang jelas; lampirkan screenshot/log jika relevan.

## Lisensi
MIT License © 2026 Petrick (82080038)

Izin diberikan secara gratis kepada siapa pun yang memperoleh salinan perangkat lunak ini dan file dokumentasinya ("Perangkat Lunak"), untuk berurusan dalam Perangkat Lunak tanpa batasan, termasuk tanpa batasan hak untuk menggunakan, menyalin, mengubah, menggabungkan, memublikasikan, mendistribusikan, mensublisensikan, dan/atau menjual salinan Perangkat Lunak, dan untuk mengizinkan orang yang diberikan Perangkat Lunak untuk melakukan hal tersebut, dengan syarat ketentuan hak cipta di atas dan pemberitahuan izin ini disertakan dalam semua salinan atau bagian substansial dari Perangkat Lunak.

PERANGKAT LUNAK INI DISEDIAKAN "APA ADANYA", TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN TERSIRAT, TERMASUK NAMUN TIDAK TERBATAS PADA JAMINAN DIPERDAGANGKAN, KECOCOKAN UNTUK TUJUAN TERTENTU DAN NONPELANGGARAN. DALAM KEADAAN APA PUN PARA PENULIS ATAU PEMEGANG HAK CIPTA TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN ATAU KEWAJIBAN LAIN, BAIK DALAM TINDAKAN KONTRAK, KESALAHAN ATAU LAINNYA, YANG TIMBUL DARI, KELUAR DARI ATAU SEHUBUNGAN DENGAN PERANGKAT LUNAK ATAU PENGGUNAAN ATAU PERJANJIAN LAIN-LAIN DALAM PERANGKAT LUNAK.


