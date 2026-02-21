# Rencana Pengembangan Aplikasi KSP LAM GABE JAYA
*Berdasarkan AD/ART, dokumen internal, regulasi umum, dan praktik industri KSP*

---

## 1. Role & Tata Kelola

### 1.1 Definisi Role
- **Pengurus**: Ketua, Wakil Ketua, Sekretaris, Bendahara, Anggota (5–9 orang). Tugas: rapat anggota, kebijakan, RAPB, SHU, angkat/berhentikan pengelola.
- **Pengawas**: Pilih dari anggota biasa, integritas, tidak rangkap pengurus/pengelola. Tugas: audit internal, laporkan ke RAT.
- **Pengelola**: Manajer, Pembukuan, Kasir Masuk/Keluar, Petugas Unit/Lapangan. Diangkat/diberhentikan pengurus, melaksanakan operasional harian.
- **Anggota**: Biasa (hak pilih/dipilih) & Luar Biasa (tidak pilih/dipilih). Kewajiban: simpanan pokok/wajib, patuh AD/ART.
- **Admin IT**: Kelola sistem, user, backup, monitoring.

### 1.2 RBAC (Role-Based Access Control)
- Middleware `AuthHelper::can` per controller/action.
- Mapping menu/aksi per role.
- Audit log untuk perubahan status, pembayaran, persetujuan.

### 1.3 Rapat & Tata Kelola
- Modul PRA RAT (per wilayah) & RAT (gabungan).
- Fitur: agenda, notulen, daftar hadir, keputusan, pembagian SHU, upload dokumen.
- Notifikasi agenda & reminder.

---

## 2. Keanggotaan & Simpanan

### 2.1 Data Anggota
- Status: Aktif, Berhenti, Ditolak.
- Tanggal masuk/berhenti.
- Checklist pendidikan dasar koperasi.
- Riwayat simpanan & pinjaman.

### 2.2 Jenis Simpanan
- **Simpanan Pokok**: Sekali saat masuk, tidak bisa diambil selama aktif.
- **Simpanan Wajib**: Iuran rutin, tidak bisa diambil.
- **Simpanan Sukarela**: Setor/tarik fleksibel.
- **SISUKA (Simpanan Sukarela Berjangka)**: Setor sekali, jangka waktu, bunga, tidak bisa diambil sebelum jatuh tempo.

### 2.3 Fitur Simpanan
- Form setor/tarik per anggota.
- Saldo riwayat per jenis.
- Bunga simpanan: metode saldo rata-rata harian atau saldo terkecil; dibayar bulanan ke saldo.
- Laporan per anggota & per jenis.

---

## 3. Pinjaman & Agunan

### 3.1 Alur Pinjaman
1. **Pengajuan**: Form + dokumen (KTP, KK, slip gaji/usaha, agunan).
2. **Verifikasi**: Petugas cek kelengkapan & kelayakan.
3. **Persetujuan**: Pengurus setujui/tolak (berjenjang jika perlu).
4. **SKB (Surat Kesepakatan Bersama)**: Generate PDF dari template (`surat.md`), isi otomatis.
5. **Pencairan**: Transfer/tunai via kasir.
6. **Jadwal Angsuran**: Auto-generate per bulan (pokok + bunga).
7. **Pembayaran**: Kasir input bayar, update sisa, hitung denda.
8. **Denda**: 1% per hari terlambat (sesuai kebijakan).
9. **Kolektibilitas**: Update otomatis (Lancar, DPK, Kurang Lancar, Diragukan, Macet).
10. **Restrukturisasi**: Rescheduling/Reconditioning/Restructuring dengan persetujuan Pengurus.

### 3.2 Agunan
- Jenis: Bergerak (BPKB, dll) & Tidak Bergerak (Sertifikat, dll).
- Nilai taksasi, lampiran foto/dokumen.
- Status: Aktif, Lepas, Sita.

### 3.3 SKB Otomatis
- Template dari `plan/surat.md`.
- Isi otomatis: nama, jumlah, tenor, bunga, jadwal, agunan.
- Export PDF (gunakan library DOMPDF/MPDF).

---

## 4. Produk & Tarif

### 4.1 Produk Pinjaman
- Nama, plafon min/max, tenor, bunga (efektif/flat), biaya admin/provisi, denda, persyaratan agunan.

### 4.2 Produk Simpanan
- Nama, minimal setor, saldo minimal, bunga per tahun, periode pembayaran bunga (bulanan), jatuh tempo SISUKA.

### 4.3 UI Master Produk
- CRUD produk.
- Tombol “Ajukan Pinjaman”/“Buka Simpanan” di dashboard anggota.

---

## 5. SHU & Jasa

### 5.1 Perhitungan SHU Tahunan
- SHU = Pendapatan (bunga pinjaman, jasa, lain) − Biaya (operasional, bunga simpanan, pajak).

### 5.2 Alokasi SHU (sesuai AD/ART/Rapat)
- **JMA (Jasa Modal Anggota)** = (Simpanan Anggota / Total Simpanan) × %JasaModal × SHU
- **JUA (Jasa Usaha Anggota)** = (Penjualan / Total Penjualan) × %JasaUsaha × SHU
- Cadangan, Sosial, Pendidikan.

### 5.3 Fitur SHU
- Input alokasi persentase per tahun.
- Hitung per anggota.
- Cetak laporan SHU.
- Notifikasi ke anggota.

---

## 6. Pelaporan & Risiko

### 6.1 Laporan
- Outstanding per produk.
- Aging pinjaman (0–30, 31–60, 61–90, >90).
- Kolektibilitas bucket.
- Simpanan per jenis.
- Cashflow kasir (masuk/keluar).
- Laba/rugi sederhana.
- Neraca sederhana.
- SHU.

### 6.2 Dashboard KPI
- Total pinjaman, total simpanan, NPL %, bunga terbayar, SHU tahun berjalan.
- Grafik mini (opsional: Chart.js).

### 6.3 Notifikasi
- Jatuh tempo angsuran.
- Simpanan jatuh tempo.
- Persetujuan pinjaman pending.
- Agenda rapat.

---

## 7. UX/Responsif

### 7.1 Mobile
- CTA (Tambah Pinjaman/Anggota) stack full-width.
- Kartu KPI 2 kolom.
- Tabel scroll horizontal.
- Peta collapsible.

### 7.2 Filter & Search
- Di tabel anggota, pinjaman, simpanan, audit.

### 7.3 Export
- CSV/Excel untuk laporan.

### 7.4 Partial Load
- Pastikan semua view pakai `#dynamicContent`.

---

## 8. Keamanan & Operasional

### 8.1 Validasi
- Server-side semua form.
- CSRF sudah ada.

### 8.2 Rate Limit
- Login, pengajuan pinjaman.

### 8.3 Backup
- DB dan lampiran dokumen.

### 8.4 Logging
- Perubahan status pinjaman/simpanan, pembayaran, persetujuan.

---

## 9. Peta (Opsional)

- Ganti placeholder dengan Leaflet/OpenStreetMap.
- Tampilkan lokasi anggota/unit jika diperlukan.

---

## 11. Praktik Industri & Referensi (Internet)

### 11.1 Fitur Umum Aplikasi KSP (Berdasarkan Survey)
- **Master Data**: COA, produk simpanan, produk pinjaman, saldo awal, persentase SHU.
- **Multi Cabang**: Akses per cabang, dashboard pemantauan pusat.
- **Simpanan Anggota**: Pokok, wajib, sukarela, SISUKA (berjangka dengan bunga).
- **Pinjaman Anggota**: Pengajuan, tenor, bunga, provisi, top-up, agunan.
- **Kredit Lancar & Macet**: Rekap bulanan, pembayaran tepat waktu, macet > jatuh tempo.
- **RAT & Laporan Keuangan**: Buku besar, rekam simpanan/pinjaman, penyusutan aset, SHU, laba rugi, neraca.
- **Pembagian SHU**: Perhitungan per anggota & pengurus, periode alokasi.
- **Portal Anggota**: Login anggota, lihat saldo, pengajuan, SHU.
- **Mobile Apps**: Akses anggota via mobile, lihat SHU, notifikasi.

### 11.2 Kolektibilitas & NPL (OJK & Perbankan)
- **Kol-1 (Lancar)**: 0 hari keterlambatan → Performing Loan.
- **Kol-2 (DPK/Dalam Perhatian Khusus)**: 1–90 hari → Performing Loan.
- **Kol-3 (Kurang Lancar)**: 91–120 hari → Non-Performing Loan (NPL).
- **Kol-4 (Diragukan)**: 121–180 hari → NPL.
- **Kol-5 (Macet)**: >180 hari → NPL.
- **Aging Bucket**: 0–30, 31–60, 61–90, >90 hari untuk monitoring.
- **Restrukturisasi**: Rescheduling (ubah jadwal), Reconditioning (ubah syarat), Restructuring (ubah pokok).

### 11.3 SHU & Jasa (Best Practice)
- **SHU = Pendapatan − Biaya** (termasuk bunga simpanan & pajak).
- **Alokasi AD/ART**: JMA (modal), JUA (usaha), cadangan, sosial, pendidikan.
- **JMA**: (Simpanan Anggota / Total Simpanan) × %JasaModal × SHU.
- **JUA**: (Penjualan / Total Penjualan) × %JasaUsaha × SHU.
- **Transparansi**: Rapat sebelum pembagian, tunai, notifikasi ke anggota.

### 11.4 Alur Pinjaman (Praktik Lapangan & Mobile-First)
1. **Pengajuan (Mobile/Kantor)**:
   - Calon peminjam bisa ajukan via aplikasi mobile (anggota) atau di kantor.
   - Upload dokumen: KTP, KK, slip gaji/usaha, foto rumah/usaha, koordinat lokasi.
   - System berikan nomor pengajuan; status tracking real-time.
2. **Verifikasi Awal (Customer Service)**:
   - Cek kelengkapan dokumen digital/fisik.
   - Input data ke sistem; validasi format & kelengkapan.
3. **Survei Lapangan (Account Officer/Petugas Lapangan)**:
   - **On The Spot**: AO kunjungi alamat, verifikasi fisik rumah/usaha, foto tambahan, wawancara tetangga/kerabat.
   - **Catat**: Koordinat GPS, kondisi agunan, foto dokumen asli, tanda tangan digital di formulir survey.
   - **Batasan**: AO hanya survei; tidak boleh menyerahkan dana langsung ke peminjam (kecuali ditentukan lain oleh sistem).
4. **Analisis Kelayakan (Account Officer)**:
   - Analisis 5C (Character, Capacity, Capital, Collateral, Condition).
   - Hitung Debt Service Ratio (DSR); usulkan plafon, tenor, bunga, agunan.
   - Upload laporan survei & rekomendasi ke sistem.
5. **Persetujuan Berjenjang (Digital Workflow)**:
   - AO → Manager Operasional/Manager Utama → (jika plafon besar) Pengurus.
   - Notifikasi push ke approver; approval digital dengan audit trail.
   - Hasil: Surat Keputusan Persetujuan/Penolakan (PDF generate).
6. **Kontrak & SKB (Digital/Physical)**:
   - Generate Surat Perjanjian Pinjaman & Jaminan (template dari `surat.md`).
   - Tanda tangan di kantor; suami/istri hadir; simpan scan ke sistem.
   - Agunan: seluruh simpanan, barang bergerak/tidak bergerak, gaji (surat kuasa potong gaji).
7. **Pencairan (Controlled)**:
   - Setelah tanda tangan, jadwal pencairan oleh Petugas Kredit/Kasir.
   - **Tidak ada penyerahan langsung oleh AO ke peminjam**.
   - Dana cair via transfer ke rekening peminjam atau tunai di kasir (bukti tanda tangan).
   - Wajib setor ke Simpanan Pagar peminjam (kecuali pinjaman kapitalisasi).
8. **Jadwal Angsuran & Notifikasi**:
   - Auto-generate per bulan (pokok + bunga, flat/efektif).
   - Push notifikasi jatuh tempo ke peminjam (mobile) & petugas.
9. **Pembayaran & Penagihan**:
   - **Penagihan Lapangan**: Petugas jemput cicilan, wajib setor ke kasir hari yang sama (bukti digital/scan).
   - **Bayar di Kantor**: Kasir/CS terima tunai/non-tunai, bukukan di sistem.
   - **Bayar Mobile**: Anggota bisa bayar via aplikasi (transfer virtual account), bukti otomatis masuk sistem.
   - Cross-check harian: saldo kredit vs pembukuan.
10. **Jatuh Tempo, Denda & Kolektibilitas**:
    - Denda otomatis sesuai kebijakan (misal 1%/hari).
    - Update status kolektibilitas otomatis (Lancar/DPK/Kurang Lancar/Diragukan/Macet).
    - Notifikasi ke peminjam & petugas untuk follow-up.
11. **Monitoring & Restrukturisasi**:
    - Dashboard real-time: outstanding, aging, NPL, kolektibilitas bucket.
    - Account Officer awasi kredit bermasalah; laporkan ke Manager.
    - Restrukturisasi digital workflow: usulan → persetujuan → jadwal baru.
12. **Pelunasan & Agunan**:
    - Penyerahan agunan dilakukan oleh pemilik & peminjam didampingi petugas.
    - Dokumen agunan asli disimpan di brankas; tidak bisa diambil sebelum sisa pinjaman < 10% dari simpanan yang dijaminkan.
    - Status pelunasan otomatis di sistem; notifikasi ke semua pihak.

### 11.5 Antisipasi Risiko & Keamanan Operasional
- **Karyawan Melarikan Diri**:
  - Batasan wewenang: AO tidak boleh serahkan dana; hanya Kasir yang boleh cairkan.
  - Audit trail: semua transaksi, persetujuan, perubahan status dicatat dengan user & timestamp.
  - Laporan harian: Kasir wajib laporkan cashflow; Manager review.
  - Role-based access: tidak ada akses tanpa login; semua aksi terverifikasi.
- **Peminjam Melarikan Diri**:
  - Data lengkap: alamat detail, koordinat GPS, foto rumah/usaha, dokumen agunan.
  - Agunan kuat: simpanan, barang bergerak/tidak bergerak, surat kuasa potong gaji.
  - Monitoring kolektibilitas otomatis; early warning untuk NPL > 90 hari.
  - Penagihan lapangan tercatat; bukti foto/lokasi saat jemput cicilan.
- **Laporan Akuntansi Tidak Benar**:
  - Sistem buku ganda: semua transaksi masuk ke sistem otomatis; tidak boleh manual di luar sistem.
  - Cross-check harian: Kasir vs pembukuan vs bank vs cash.
  - Role approval: perubahan data penting butuh persetujuan Manager/Pengurus.
  - Audit eksternal: akses auditor untuk review laporan.
- **Keamanan Data & Dokumen**:
  - Enkripsi data sensitif (penghasilan, agunan, dokumen pribadi).
  - Backup otomatis harian & offsite.
  - Akses log: siapa akses data peminjam, kapan, dari device mana.
  - Dokumen fisik: brankas dengan system kunci bersama (Manager + Kasir).

### 11.5 Simpanan & Bunga (Praktik)
- **Bunga Simpanan**: Metode saldo rata-rata harian atau saldo terkecil; dibayar bulanan ke saldo.
- **SISUKA**: Setor sekali, jangka waktu, bunga lebih tinggi, tidak bisa diambil sebelum jatuh tempo.
- **Penarikan**: Fleksibel untuk sukarela; pokok/wajib tidak bisa diambil selama aktif.
- **Laporan**: Rekap per jenis, per anggota, mutasi, bunga terbayar.

### 11.6 Mobile-First & Multi-Role Access
- **Portal Anggota (Mobile)**:
  - Ajukan pinjaman, unggah dokumen, foto rumah/usaha, GPS.
  - Lihat saldo, riwayat simpanan & pinjaman, jadwal angsuran.
  - Bayar angsuran via virtual account; bukti otomatis masuk sistem.
  - Notifikasi push: jatuh tempo, persetujuan, SHU.
- **Petugas Lapangan (AO)**:
  - Akses mobile: lihat daftar survei, input laporan, upload foto/GPS.
  - Tidak bisa akses pencairan atau pembukuan kas.
  - Wajib upload bukti penagihan (foto/lokasi) saat jemput cicilan.
- **Kasir/CS (Kantor & Mobile)**:
  - Akses pembayaran, pencairan (dengan approval), laporan harian.
  - Cross-check cashflow harian; upload bukti setor.
- **Manager/Pengurus (Dashboard Mobile)**:
  - Approve pengajuan, restrukturisasi, lihat KPI real-time.
  - Laporan outstanding, aging, NPL, kolektibilitas.
  - Notifikasi eskalasi: NPL > 90 hari, cashflow negatif.

### 11.8 Gaji & Biaya Operasional (Best Practice)
- **Struktur Gaji & Honor**:
  - **Pengurus**: Tidak menerima gaji tetap, hanya uang jasa sesuai RAPB yang disahkan RAT.
  - **Pengawas**: Tidak menerima gaji, hanya uang jasa (jika ada) sesuai keputusan RAT.
  - **Pengelola (Manajer, Kasir, AO, CS, Petugas Lapangan)**: Menerima gaji/honor bulanan sesuai SK Pengurus.
  - **Admin IT**: Gaji tetap atau honor sesuai kebijakan.
- **Komisi & Insentif**:
  - **AO (Account Officer)**: Insentif berdasarkan kualitas portofolio (kolektibilitas Lancar > 90%) dan jumlah pencairan yang disalurkan.
  - **Kasir/CS**: Insentif berdasarkan volume transaksi dan kepatuhan cashflow harian.
  - **Manajer**: Bonus berdasarkan pencapaian target KPI (NPL < 5%, pertumbuhan pinjaman, profitabilitas).
- **Biaya Operasional**:
  - **Biaya Tetap**: Gaji/honor, sewa kantor, listrik, internet, software, asuransi karyawan.
  - **Biaya Variabel**: Biaya survei lapangan (transport, komunikasi), biaya admin/provisi pinjaman (dibebankan ke peminjam), biaya materai, notaris, asuransi kredit/agunan, biaya penagihan.
  - **Biaya Promosi**: Edukasi anggota, sosialisasi produk, event komunitas.
- **Penggajian & Pengeluaran**:
  - **Slip Gaji**: Digital per bulan dengan rincian gaji/honor, tunjangan, potongan (BPJS, PPh 21), insentif.
  - **Approval**: Manajer ajukan RAPB gaji; Pengurus setujui; RAT sahkan.
  - **Pembayaran**: Transfer ke rekening karyawan; bukti digital disimpan.
  - **Laporan**: Laporan gaji bulanan, rekap biaya operasional, perbandingan vs anggaran.
- **Audit & Kontrol**:
  - **Cashflow Harian**: Kasir laporkan pemasukan (angsuran, simpanan) dan pengeluaran (gaji, biaya operasional).
  - **Cross-Check**: Manager review cashflow vs buku besar vs bank.
  - **Audit Internal**: Pengawas periksa penggajian & biaya setiap triwulan.
  - **Audit Eksternal**: Tahunan oleh akuntan publik (jika diperlukan).
- **Integrasi Sistem**:
  - **Modul Penggajian**: Input data karyawan, hitung gaji otomatis, generate slip gaji PDF.
  - **Modul Biaya**: Input biaya operasional, kategori, lampiran bukti (scan/foto).
  - **Dashboard**: Real-time cashflow, beban gaji, rasio biaya terhadap pendapatan.
  - **Notifikasi**: Pengingat pengajuan gaji, approval gaji, cashflow negatif.

### 11.10 Pinjaman Gagal Bayar (NPL & Restrukturisasi)
- **Klasifikasi Kolektibilitas (OJK)**:
  - **Kol-1 (Lancar)**: 0 hari keterlambatan.
  - **Kol-2 (DPK/Dalam Perhatian Khusus)**: 1–90 hari.
  - **Kol-3 (Kurang Lancar)**: 91–120 hari → NPL.
  - **Kol-4 (Diragukan)**: 121–180 hari → NPL.
  - **Kol-5 (Macet)**: >180 hari → NPL.
- **Early Warning System**:
  - Otomatis update status kolektibilitas setiap hari.
  - Notifikasi ke AO & Manager saat >30 hari, >60 hari, >90 hari.
  - Dashboard NPL: outstanding, aging bucket, % kolektibilitas.
- **Tindakan Penagihan**:
  - **0–30 hari**: Reminder via SMS/push; AO follow-up telepon.
  - **31–90 hari**: Surat peringatan; AO kunjungi rumah/usaha; negosiasi jadwal baru.
  - **91–120 hari**: Surat somasi; evaluasi agunan; siapkan restrukturisasi.
  - **>120 hari**: Proses hukum (eksekusi agunan) jika ada; atau write-off.
- **Restrukturisasi Kredit**:
  - **Rescheduling**: Ubah jadwal angsuran (perpanjang tenor) tanpa ubah pokok/bunga.
  - **Reconditioning**: Ubah syarat (tambah agunan, ubah bunga, grace period).
  - **Restructuring**: Ubah pokok pinjaman (pengurangan) dengan persetujuan Pengurus.
  - **Workflow**: AO ajukan → Manager analisis → Pengurus setujui → generate SK Baru → update jadwal.
- **Eksekusi Agunan**:
  - **Agunan Simpanan**: Potong simpanan untuk menutupi tunggakan (sesuai perjanjian).
  - **Agunan Bergerak/Tidak Bergerak**: Lelang sesuai prosedur; hasil digunakan untuk menutupi pinjaman.
  - **Dokumen**: SK Penyerahan Agunan, Berita Acara Lelang, bukti penyerahan ke peminjam.
- **Write-Off (Penghapusan)**:
  - Kriteria: >180 hari, agunan tidak mencukupi, upaya penagihan maksimal.
  - Prosedur: AO usulkan → Manager review → Pengurus setujui → jurnal write-off.
  - Laporkan ke RAT; catat kerugian; evaluasi kebijakan penagihan.
- **Pelaporan & Audit**:
  - **Laporan NPL Bulanan**: Outstanding per kolektibilitas, aging, recovery rate.
  - **Laporan Restrukturisasi**: Jumlah kasus, pokok direstruktur, status jadwal baru.
  - **Audit Trail**: Semua tindakan penagihan, restrukturisasi, eksekusi dicatat user & timestamp.
- **Integrasi Sistem**:
  - **Auto Update**: Status kolektibilitas, denda, notifikasi.
  - **Workflow Digital**: Pengajuan restrukturisasi, approval, SK Baru (PDF).
  - **Dashboard**: Real-time NPL, early warning, recovery rate.
  - **Mobile**: AO input laporan penagihan di lapangan (foto, lokasi, catatan).

### 11.12 Peminjam Melarikan Diri (Mitigasi & Tindakan)
- **Data Lengkap & Verifikasi Awal**:
  - **Identitas**: KTP, KK, foto rumah/usaha, koordinat GPS, nomor telepon, email, akun media sosial (jika ada).
  - **Kerabat/Darurat**: Nama, hubungan, nomor telepon, alamat (minimal 2 orang).
  - **Pekerjaan**: Surat keterangan kerja, slip gaji, nama atasan, nomor HP atasan (verifikasi).
  - **Agunan**: Dokumen lengkap (BPKB, sertifikat, foto, nilai taksasi), kuasa penjualan jika diperlukan.
- **Monitoring Aktif**:
  - **Early Warning**: Jika tidak ada kontak (telepon tidak aktif, tidak respons) >7 hari, status “Risiko Hilang”.
  - **Kunjungan Lapangan**: AO kunjungi alamat terakhir; wawancara tetangga/RT/RW; catat kondisi rumah/usaha.
  - **Koordinat GPS**: Bandingkan GPS saat survei vs saat pengajuan; jika jauh, status “Risiko Pindah”.
  - **Notifikasi**: Kirim SMS/push ke peminjam & kerabat; catat status terkirim/dibaca.
- **Tindakan Preventif**:
  - **Agunan Kuat**: Pastikan nilai agunan ≥ 120% dari outstanding; simpanan wajib ≥ 20% plafon.
  - **Asuransi Kredit**: Jika ada, klaim ke asuransi untuk kerugian akibat peminjam hilang.
  - **Surat Kuasa Penjualan**: Saat tanda tangan perjanjian, peminjam tanda tangani surat kuasa menjual agunan jika macet/hilang.
  - **Laporan Polisi**: Jika ada indikasi penipuan atau hilang >30 hari, laporkan ke polisi dengan bukti lengkap.
- **Proses Hilang**:
  - **30 Hari Pertama**: Reminder harian, kunjungan lapangan, koordinasi dengan kerabat.
  - **31–60 Hari**: Surat somasi pertama ke alamat terakhir; somasi elektronik ke email & nomor HP.
  - **61–90 Hari**: Surat somasi kedua; evaluasi agunan; siapkan proses eksekusi.
  - **>90 Hari**: Eksekusi agunan (jual lelang atau potong simpanan); laporkan ke Pengurus & RAT.
- **Eksekusi Agunan (Hilang)**:
  - **Simpanan**: Potong simpanan untuk menutupi tunggakan; sisa jika ada dikembalikan ke ahli waris setelah proses hukum.
  - **Barang Bergerak**: Lelang sesuai prosedur; hasil digunakan untuk menutupi pinjaman; kelebihan (jika ada) disimpan.
  - **Barang Tidak Bergerak**: Lelang via notaris/pejabat lelang; hasil digunakan untuk menutupi pinjaman.
  - **Dokumen**: Berita Acara Eksekusi, bukti lelang, surat pemberitahuan ke ahli waris.
- **Aspek Hukum & Pelaporan**:
  - **Laporan Polisi**: Bawa KTP, KK, perjanjian pinjaman, bukti penagihan, laporan survei.
  - **Kuasa Hukum**: Jika perlu, berikan kuasa ke pengacara untuk proses hukum.
  - **RAT**: Laporkan kasus peminjam hilang, kerugian, tindakan yang diambil; buat kebijakan preventif.
- **Integrasi Sistem**:
  - **Status Peminjam**: Tambah status “Hilang”, “Risiko Hilang”, “Ditemukan”.
  - **Alert Otomatis**: Notifikasi ke AO & Manager saat >7 hari tidak ada kontak.
  - **Historik Kunjungan**: AO input laporan kunjungan (foto, lokasi GPS, catatan).
  - **Dokumen Digital**: Upload scan laporan polisi, berita acara, bukti lelang.
- **Preventif Jangka Panjang**:
  - **Screening Lebih Ketat**: Cross-check ke database SLIK (BI Checking) untuk riwayat kredit.
  - **Verifikasi Tempat Usaha**: Pastikan usaha benar-benar ada dan beroperasi.
  - **Jaringan Informan**: Bangun relasi dengan RT/RW, tokoh masyarakat, tetangga bisnis.
  - **Asuransi**: Wajibkan asuransi jiwa untuk pinjaman > plafon tertentu.

### 11.14 Pengurus Tidak Bekerja atau Tidak Melaksanakan Tugas (Pengawasan & Sanksi)
- **Tugas & Wewenang Pengurus (AD/ART)**:
  - Menyelenggarakan rapat anggota (PRA RAT/RAT).
  - Menyusun & mengesahkan RAPB (Rencana Anggaran Pendapatan dan Belanja).
  - Menyetujui alokasi SHU dan kebijakan operasional.
  - Mengangkat/memberhentikan pengelola (manajer, kasir, AO, CS).
  - Melakukan pengawasan atas kinerja pengelola dan kepatutan AD/ART.
  - Mewakili koperasi di dalam dan luar pengadilan.
  - Bertanggung jawab atas maju mundurnya koperasi.
- **Mekanisme Pengawasan Internal**:
  - **Pengawas**: Dipilih dari anggota biasa, integritas, tidak rangkap pengurus/pengelola.
  - **Jadwal Pengawasan**: Minimal 3 bulan sekali; bisa insidental jika ada indikasi masalah.
  - **Ruang Lingkup**: Organisasi, keuangan, pembukuan, pelaksanaan kebijakan, kepatutan AD/ART.
  - **Hak Akses**: Akses ke seluruh dokumen, laporan, sistem, wawancara karyawan & anggota.
- **Indikasi Pengurus Tidak Aktif**:
  - **Tidak Ada Rapat**: Tidak ada PRA RAT/RAT selama >6 bulan tanpa alasan sah.
  - **Tidak Ada Laporan**: Tidak menyetujui laporan keuangan tahunan ke RAT.
  - **Keputusan Sepih**: Keputusan penting (pinjaman besar, pengangkatan) tanpa persetujuan kolektif.
  - **Tidak Ada Monitoring**: Tidak ada evaluasi kinerja pengelola atau review KPI.
  - **Akses Terbatas**: Menutup akses informasi dari Pengawas atau anggota.
- **Tindakan Pengawas**:
  - **1–30 Hari**: Surat teguran tertulis ke Pengurus; minta penjelasan; buat berita acara.
  - **31–60 Hari**: Undang rapat luar biasa dengan agenda evaluasi kinerja Pengurus; undang Pengawas Independen (jika perlu).
  - **61–90 Hari**: Laporkan ke Rapat Anggota Luar Biasa; usulkan pergantian Pengurus interim.
  - **>90 Hari**: Ajukan mosi tidak percaya ke Rapat Anggota; proses pemilihan Pengurus baru.
- **Sanksi & Konsekuensi**:
  - **Sanksi Organisasi**: Tidak ada keputusan strategis, arah usaha tidak jelas, SHU tidak terbagikan.
  - **Sanksi Keuangan**: Laporan tidak disahkan, cashflow tidak termonitor, risiko penyelewengan meningkat.
  - **Sanksi Karyawan**: Kinerja menurun, tidak ada evaluasi, insentif tidak jalan, moral menurun.
  - **Sanksi Anggota: Layanan menurun, kepercayaan menurun, potensi keluar anggota.
- **Prosedur Pergantian Pengurus**:
  - **Usulan**: Pengawas atau minimal 10% anggota biasa bisa usulkan calon Pengurus.
  - **Verifikasi**: Calon Pengurus harus lulus syarat AD/ART (integritas, tidak ada kasus pidana berat, tidak usaha menyaingi).
  - **Pemilihan**: Rapat Anggota Luar Biasa pilih Pengurus baru; periode jabatan sesuai AD/ART.
  - **Serah Terima**: Pengurus baru serah terima jabatan; buat berita acara serah terima.
- **Integrasi Sistem**:
  - **Status Pengurus**: Tambah status “Aktif”, “Tidak Aktif”, “Sanksi”, “Diganti”.
  - **Alert Otomatis**: Notifikasi ke Pengawas jika tidak ada rapat >6 bulan.
  - **Audit Trail**: Semua keputusan, laporan, aksi Pengurus dicatat user & timestamp.
  - **Dashboard Pengawasan**: KPI kepatutan Pengurus (rapat, laporan, monitoring, evaluasi).
- **Role Pengawas Independen (Opsional)**:
  - **Tujuan**: Audit eksternal untuk objektivitas; jika internal tidak efektif.
  - **Tugas**: Periksa laporan keuangan, kepatutan AD/ART, kinerja Pengurus & Pengelola.
  - **Laporan**: Hasil audit disampaikan ke Rapat Anggota; rekomendasi perbaikan.

### 11.16 Pengambilan Foto & Dokumentasi via Aplikasi (Mobile & Web)
- **Fitur Kamera & Upload**:
  - **Mobile**: Akses kamera depan/belakang; ambil foto KTP, KK, slip gaji, rumah, usaha, agunan.
  - **Web**: Upload file dari komputer; webcam capture (opsional).
  - **Format**: JPEG/PNG untuk foto; PDF untuk dokumen multi-halaman.
  - **Kompresi Otomatis**: Resize & kompres untuk hemat storage; tetap jelas untuk verifikasi.
- **GPS & Metadata**:
  - **Mobile**: Auto-capture koordinat GPS saat foto; simpan EXIF data (tanggal, waktu, lokasi).
  - **Web**: Input manual alamat; bisa pilih di peta (Leaflet) untuk koordinat.
  - **Verifikasi**: Bandingkan GPS foto vs GPS pengajuan untuk deteksi kecurangan.
- **Proses Verifikasi Dokumen**:
  - **OCR (Optical Character Recognition)**: Ekstrak teks dari KTP/KK untuk auto-fill form.
  - **Validasi Format**: Cek kejelasan foto, keutuhan dokumen, tidak blur, tidak terpotong.
  - **Deteksi Duplikat**: Cek hash file untuk mencegah upload ulang dokumen sama.
- **Dokumen Terstruktur**:
  - **KTP**: Nama, nomor KTP, alamat, tanggal lahir, agama, status perkawinan, pekerjaan.
  - **KK**: Kepala keluarga, anggota, nomor KK.
  - **Slip Gaji**: Nama perusahaan, gaji bulanan, potongan, periode.
  - **Agunan**: Foto BPKB, sertifikat, STNK, foto barang, nilai taksasi.
  - **Surat Keterangan**: Surat keterangan kerja, usaha, domisili.
- **Keamanan & Privasi**:
  - **Enkripsi**: Dokumen dienkripsi di storage; akses hanya berdasarkan role.
  - **Watermark**: Tambah watermark logo koperasi & tanggal ke foto untuk mencegah penyalahgunaan.
  - **Audit Trail**: Log setiap upload, view, download dokumen (user, timestamp, IP).
  - **Retention**: Otomatis hapus dokumen lama setelah periode tertentu (sesuai kebijakan).
- **Integrasi Sistem**:
  - **Storage**: Lokal (server) atau cloud (AWS S3, Google Cloud) dengan auto-backup.
  - **Thumbnail**: Generate thumbnail untuk preview cepat di list.
  - **API**: Endpoint untuk upload, view, download, delete dokumen.
  - **Notifikasi**: Notifikasi ke petugas saat ada dokumen baru per diverifikasi.
- **UI/UX**:
  - **Mobile**: Kamera full-screen, preview sebelum simpan, crop & rotate tools.
  - **Web**: Drag & drop upload, progress bar, preview multi-halaman PDF.
  - **Status**: Status dokumen (Upload → Verifikasi → Disetujui → Ditolak).
  - **History**: Riwayat perubahan dokumen (upload ulang, verifikasi ulang).
- **Penggunaan per Role**:
  - **Anggota**: Upload dokumen pengajuan; lihat status dokumen; upload ulang jika ditolak.
  - **AO**: Ambil foto saat survei; upload laporan; lihat dokumen anggota.
  - **CS**: Verifikasi dokumen; approve/reject; minta upload ulang.
  - **Manager**: Review dokumen penting; akses semua dokumen.
  - **Pengurus**: Akses dokumen untuk keputusan strategis.
- **Teknologi**:
  - **Frontend**: HTML5 File API, Camera API, Canvas untuk crop/compress; Leaflet untuk peta.
  - **Backend**: PHP GD/ImageMagick untuk proses gambar; Tesseract OCR (opsional); AWS SDK/Google Cloud SDK.
  - **Database**: Tabel dokumen (id, user_id, type, path, metadata, status, created_at, updated_at).
  - **Security**: CSRF token, file type validation, max size limit, virus scan (opsional).

### 11.18 Bunga Pinjaman & Sistem Perhitungan
- **Jenis Bunga**:
  - **Bunga Flat**: Bunga dihitung dari plafon awal selama tenor; angsuran pokok + bunga tetap setiap bulan.
  - **Bunga Efektif (Anuitas)**: Bunga dihitung dari sisa pokok pinjaman; angsuran tetap, komposisi pokok & bunga berubah.
  - **Bunga Menurun**: Bunga dihitung dari sisa pokok; angsuran pokok tetap, bunga menurun.
  - **Bunga Mengambang**: Bunga dasar + spread; bisa berubah sesuai kebijakan atau acuan (BI Rate).
- **Metode Perhitungan**:
  - **Flat**: `Angsuran = (Pokok + (Pokok × Bunga × Tenor)) / Tenor`
  - **Efektif**: Gunakan formula PMT (Present Value of Annuity) atau tabel angsuran.
  - **Menurun**: `Angsuran Pokok = Pokok / Tenor`; `Bunga Bulan = Sisa Pokok × Bunga/Tahun × 1/12`.
  - **Mengambang**: Update bunga setiap periode; hitung ulang angsuran jika ada perubahan.
- **Komponen Biaya Pinjaman**:
  - **Bunga Pinjaman**: % per tahun (flat/efektif/menurun/mengambang).
  - **Biaya Administrasi**: Sekali saat pencairan (contoh: 1% dari plafon, minimal Rp50.000).
  - **Biaya Provisi**: Sekali saat pencairan (contoh: 0.5% dari plafon).
  - **Biaya Asuransi**: Bulanan atau tahunan (jika ada).
  - **Biaya Notaris/Materai**: Saat tanda tangan perjanjian.
  - **Denda Keterlambatan**: % per hari (contoh: 1%/hari dari tunggakan).
- **Struktur Produk Pinjaman**:
  - **Nama Produk**: Pinjaman Produktif, Konsumtif, Darurat, Modal Kerja.
  - **Plafon**: Min & max (contoh: Rp1.000.000 – Rp50.000.000).
  - **Tenor**: 1–36 bulan (sesuai produk).
  - **Bunga**: 12%–24% per tahun (flat/efektif/menurun/mengambang).
  - **Biaya**: Admin, provisi, asuransi, denda.
  - **Persyaratan**: Agunan, maksimal DSR, usia, lama menjadi anggota.
- **Perhitungan Denda**:
  - **Denda Harian**: `Tunggakan × Denda%/Hari × Hari Terlambat`.
  - **Bunga Berjalan**: Tetap berjalan selama pinjaman, termasuk saat terlambat.
  - **Kapitalisasi Denda**: Bisa masuk ke tunggakan atau dibayar terpisah.
- **Sistem Akuntansi Bunga**:
  - **Bunga Terutang**: Akru bunga setiap bulan (matching principle).
  - **Bunga Diterima**: Saat pembayaran angsuran masuk.
  - **Bunga Tertunggak**: Jika angsuran macet, bunga tetap diakru.
  - **Pajak Bunga**: Potongan PPh 23 atas bunga pinjaman (jika ada).
- **Fitur Sistem**:
  - **Kalkulator Pinjaman**: Simulasi angsuran real-time (input plafon, tenor, bunga).
  - **Tabel Angsuran**: Generate otomatis per bulan (pokok, bunga, total, sisa).
  - **Flexi Bunga**: Ubah bunga di tengah tenor (restrukturisasi); generate ulang jadwal.
  - **Promo Bunga**: Bunga khusus untuk periode tertentu (diskon, cashback).
  - **Laporan Bunga**: Rekap bunga terutang, diterima, tertunggak per periode.
- **UI/UX**:
  - **Form Pengajuan**: Pilih produk, input plafon, tenor; lihat simulasi angsuran.
  - **Detail Pinjaman**: Tabel angsuran, rincian biaya, jadwal jatuh tempo.
  - **Dashboard**: Total bunga bulanan, rasio bunga terhadap outstanding, trend bunga.
  - **Mobile**: Simulasi cepat, preview angsuran, notifikasi jatuh tempo.
- **Validasi & Kontrol**:
  - **DSR (Debt Service Ratio)**: Maksimal 30%–50% dari penghasilan (sesuai kebijakan).
  - **Bunga Maksimal**: Sesuai regulasi OJK atau kebijakan internal.
  - **Approval Bunga**: Perubahan bunga butuh persetujuan Manager/Pengurus.
  - **Audit**: Perubahan bunga, diskon, promo dicatat user & timestamp.
- **Integrasi**:
  - **Keuangan**: Jurnal otomatis bunga terutang, diterima, pajak.
  - **Laporan**: Laporan laba rugi (pendapatan bunga), laporan bunga per produk.
  - **Notifikasi**: Info perubahan bunga, promo, jatuh tempo bunga.
  - **API**: Endpoint untuk kalkulator, tabel angsuran, riwayat bunga.

### 11.20 Modal Usaha, Keuntungan & Biaya (Akuntansi Koperasi)
- **Struktur Modal Koperasi**:
  - **Modal Sendiri**: Simpanan pokok anggota, modal penyertaan, cadangan wajib.
  - **Modal Pinjaman**: Pinjaman dari bank, lembaga keuangan lain, pinjaman anggota (jika ada).
  - **Modal Donasi/Hibah**: Bantuan pemerintah atau pihak ketiga (jika ada).
  - **Retensi Laba**: Laba ditahan untuk modal kerja.
- **Perhitungan Modal**:
  - **Modal Awal**: Saldo awal periode.
  - **Penambahan Modal**: Setoran modal baru, laba ditahan.
  - **Pengurangan Modal**: Penarikan modal (sesuai AD/ART), kerugian.
  - **Modal Akhir**: Modal Awal + Penambahan – Pengurangan.
- **Pendapatan Koperasi**:
  - **Bunga Pinjaman**: Pendapatan utama dari bunga pinjaman anggota.
  - **Jasa Administrasi**: Biaya admin, provisi, denda.
  - **Jasa Usaha Lain**: Penjualan barang, jasa konsultasi, sewa aset.
  - **SHU dari Investasi**: Bunga dari deposito, saham, investasi lain.
- **Beban Operasional**:
  - **Bunga Simpanan**: Beban bunga simpanan anggota (pokok, wajib, sukarela).
  - **Gaji & Honor**: Gaji pengelola, uang jasa pengurus, insentif.
  - **Biaya Operasional**: Sewa kantor, listrik, air, internet, telepon.
  - **Biaya Administrasi**: Materai, notaris, bank, software, asuransi.
  - **Penyusutan Aset**: Penyusutan kendaraan, peralatan kantor, bangunan.
  - **Biaya Penagihan**: Transport, komunikasi, biaya eksekusi agunan.
  - **Biaya Promosi**: Edukasi anggota, sosialisasi, event komunitas.
- **Perhitungan Laba/Rugi**:
  - **Laba Bruto**: Pendapatan Total – Beban Bunga Simpanan.
  - **Laba Operasional**: Laba Bruto – Beban Operasional.
  - **Laba Sebelum Pajak**: Laba Operasional + Pendapatan Lain – Beban Lain.
  - **Laba Bersih**: Laba Sebelum Pajak – Pajak Penghasilan Badan.
- **Sisa Hasil Usaha (SHU)**:
  - **SHU Sebelum Pajak**: Laba Bersih + Beban Pajak (jika ada koreksi).
  - **Alokasi SHU** (sesuai AD/ART/Rapat):
    - Jasa Modal Anggota (JMA): % dari SHU.
    - Jasa Usaha Anggota (JUA): % dari SHU.
    - Cadangan: % dari SHU.
    - Sosial/Pendidikan: % dari SHU.
    - Dana Tak Terduga: % dari SHU.
  - **Perhitungan JMA**: (Simpanan Anggota / Total Simpanan) × %JMA × SHU.
  - **Perhitungan JUA**: (Penjualan/Total Penjualan) × %JUA × SHU.
- **Arus Kas (Cash Flow)**:
  - **Arus Kas Masuk**: Pencairan pinjaman, pembayaran angsuran, setor simpanan, pendapatan lain.
  - **Arus Kas Keluar**: Pencairan pinjaman, pembayaran bunga simpanan, gaji, biaya operasional.
  - **Net Cash Flow**: Arus Masuk – Arus Keluar.
  - **Saldo Kas**: Saldo Awal + Net Cash Flow.
- **Neraca Sederhana**:
  - **Aset**: Kas, bank, piutang pinjaman, piutang bunga, penyisihan piutang ragu-ragu, inventori, tetap.
  - **Kewajiban**: Simpanan anggota, pinjaman bank, utang bunga, utang operasional.
  - **Modal**: Modal sendiri, cadangan, laba ditahan, SHU belum dibagi.
- **Fitur Sistem**:
  - **Jurnal Otomatis**: Setiap transaksi otomatis buat jurnal (debet/kredit).
  - **Buku Besar**: Rekap per akun, saldo per periode.
  - **Laporan Laba Rugi**: Bulanan, triwulan, tahunan.
  - **Laporan Perubahan Modal**: Periode ke periode.
  - **Laporan Arus Kas**: Bulanan, tahunan.
  - **Neraca**: Bulanan, tahunan.
  - **Dashboard KPI**: Laba bersih, ROA, ROE, NPL, LDR (Loan to Deposit Ratio).
- **UI/UX**:
  - **Input Transaksi**: Form pembayaran, pencairan, biaya, pendapatan.
  - **Dashboard Keuangan**: Grafik pendapatan/beban, cash flow, KPI.
  - **Laporan Interaktif**: Filter periode, export PDF/Excel, drill down.
  - **Mobile**: Ringkasan keuangan, notifikasi cashflow negatif.
- **Validasi & Kontrol**:
  - **Balance Check**: Total debet = total kredit setiap jurnal.
  - **Closing Period**: Tutup buku bulanan/triwulan/tahunan; lock transaksi lama.
  - **Budget vs Actual**: Bandingkan RAPB vs realisasi.
  - **Audit Trail**: Semua perubahan jurnal dicatat user & timestamp.
### 11.21 Data Rendering & API (Hybrid Approach - Desktop vs Mobile)
- **Konsep Hybrid Rendering**:
  - **Smart Rendering**: Sistem cerdas memilih rendering method berdasarkan device, network, dan data size.
  - **Progressive Enhancement**: Desktop full rendering, mobile progressive loading.
  - **Adaptive API**: API response format menyesuaikan dengan client capabilities.
  - **Seamless Experience**: Konsistensi data dan UX antar platform.
- **Device Detection & Strategy**:
  - **Desktop (Large Screen)**: Full server-side rendering, complete data, rich UI components.
  - **Tablet (Medium Screen)**: Hybrid rendering, moderate data, responsive components.
  - **Mobile (Small Screen)**: API-first rendering, minimal data, mobile-optimized components.
  - **Low Bandwidth**: Text-only mode, compressed data, essential features only.
- **Rendering Methods per Device**:
  ```
  Desktop (>1024px):
  - Server-side rendering (SSR)
  - Complete HTML response
  - Full dataset (no pagination)
  - Rich UI components (tables, charts)
  - Real-time updates via WebSocket
  
  Tablet (768px-1024px):
  - Hybrid SSR + API
  - Moderate data size
  - Responsive components
  - Touch-optimized interface
  - Progressive loading
  
  Mobile (<768px):
  - API-first rendering
  - JSON response
  - Paginated data
  - Mobile components
  - Offline support (production only)
  ```
- **API Response Strategy**:
  - **Desktop Request**: Return full HTML + embedded JSON data.
  - **Mobile Request**: Return JSON data only, client-side rendering.
  - **Tablet Request**: Return HTML with lazy-loaded sections.
  - **Fallback**: Graceful degradation for unsupported devices.
- **Data Size Optimization**:
  - **Desktop**: Full dataset (1000+ records), no compression priority.
  - **Mobile**: Paginated dataset (20-50 records), high compression.
  - **Tablet**: Moderate dataset (100-200 records), medium compression.
  - **Network Detection**: Auto-adjust based on connection speed.
- **Component Strategy**:
  - **Desktop**: Complex tables, advanced charts, multi-panel dashboard.
  - **Mobile**: Simple cards, basic charts, single-panel dashboard.
  - **Tablet**: Hybrid components, adaptive layout.
  - **Progressive**: Components upgrade based on device capabilities.
- **Caching Strategy**:
  - **Desktop**: Server-side caching of full pages.
  - **Mobile**: Client-side caching of API responses (production only).
  - **Tablet**: Hybrid caching strategy.
  - **Smart Cache**: Cache based on device type and data volatility.
- **Performance Optimization**:
  - **Desktop**: Optimize for CPU, memory usage, rendering speed.
  - **Mobile**: Optimize for bandwidth, battery, processing power.
  - **Tablet**: Balance between performance and features.
  - **Lazy Loading**: Load components and data as needed.
- **Network Considerations**:
  - **Fast Connection (WiFi)**: Full data, real-time updates.
  - **Slow Connection (3G/4G)**: Compressed data, offline mode (production only).
  - **No Connection**: Offline-first, sync when available (production only).
  - **Adaptive Quality**: Adjust data quality based on network.
- **Implementation Architecture**:
  ```
  Request Flow:
  1. Device Detection (User-Agent + Screen Size)
  2. Network Detection (Connection Speed)
  3. Rendering Strategy Selection
  4. API Response Format Decision
  5. Data Size Calculation
  6. Component Selection
  7. Cache Strategy Application
  8. Response Generation
  ```
- **Backend Implementation**:
  - **Device Detection Middleware**: Automatic device and capability detection.
  - **Response Builder**: Dynamic response building based on client.
  - **Data Optimizer**: Optimize data size and format per device.
  - **Component Factory**: Generate appropriate UI components.
  - **Cache Manager**: Smart caching per device and data type.
- **Frontend Implementation**:
  - **Adaptive Renderer**: Client-side rendering adapts to device.
  - **Progressive Enhancement**: Start basic, enhance progressively.
  - **Offline Support**: Service worker untuk cache data mobile (aktif di production).
  - **Sync Manager**: Background sync untuk mobile devices (production only).
  - **Performance Monitor**: Monitor dan optimasi performance.
- **API Endpoints Strategy**:
  ```
  /api/dashboard (Desktop):
  - Response: HTML + JSON
  - Data: Complete dataset
  - Format: Server-rendered
  
  /api/dashboard (Mobile):
  - Response: JSON only
  - Data: Paginated
  - Format: Client-rendered
  
  /api/dashboard (Tablet):
  - Response: HTML + lazy sections
  - Data: Moderate dataset
  - Format: Hybrid
  ```
- **Data Synchronization**:
  - **Real-time Sync**: Desktop gets real-time updates.
  - **Periodic Sync**: Mobile gets periodic updates (production only).
  - **On-Demand Sync**: Manual refresh available.
  - **Conflict Resolution**: Handle conflicts intelligently.
- **Security Considerations**:
  - **Device Authentication**: Different auth per device type.
  - **Data Encryption**: Encrypt sensitive data on mobile.
  - **Access Control**: Role-based access per device.
  - **Session Management**: Secure session management.
- **Testing Strategy**:
  - **Device Testing**: Test on various devices and screen sizes.
  - **Network Testing**: Test on different network conditions.
  - **Performance Testing**: Performance testing per device.
  - **Usability Testing**: Usability testing per platform.
- **Monitoring & Analytics**:
  - **Device Analytics**: Track usage per device type.
  - **Performance Metrics**: Monitor performance per platform.
  - **Network Analytics**: Track network conditions.
  - **User Behavior**: Analyze user behavior per device.
- **Benefits of Hybrid Approach**:
  - **Optimal Performance**: Best performance per device.
  - **Bandwidth Efficiency**: Efficient bandwidth usage.
  - **User Experience**: Consistent yet optimized UX.
  - **Scalability**: Scalable architecture.
  - **Flexibility**: Flexible and adaptable system.
- **Implementation Examples**:
  - **Dashboard**: Desktop shows full dashboard, mobile shows summary cards.
  - **Reports**: Desktop shows detailed tables, mobile shows key metrics.
  - **Forms**: Desktop shows complex forms, mobile shows simplified forms.
  - **Charts**: Desktop shows interactive charts, mobile shows static images.
- **Production-Only Features**:
  - **Service Worker**: Offline cache untuk mobile hanya aktif di production.
  - **Background Sync**: Sync background hanya aktif di production.
  - **Offline Mode**: Mode offline hanya aktif di production.
  - **Push Notifications**: Push notifications hanya aktif di production.
- **Development vs Production**:
  - **Development**: Service worker dinonaktifkan untuk memudahkan debugging.
  - **Production**: Service worker diaktifkan untuk performa optimal.
  - **Environment Detection**: Otomatis deteksi environment untuk fitur offline.
  - **Feature Flags**: Control fitur offline berdasarkan environment.
  - **Desktop**: Prioritaskan fitur lengkap; bandwidth tidak masalah.
  - **Mobile**: Prioritaskan kecepatan; minimalisir payload; gunakan CDN untuk assets.
  - **Offline**: Service worker untuk cache data mobile (opsional).
- **Security**:
  - **Rate Limiting**: API lebih ketat (misal 100 req/menit per user).
  - **Input Validation**: Server-side untuk API; client-side untuk UX.
  - **Data Masking**: Sensitive data (gaji, agunan) hanya tampil di desktop atau role tertentu.
- **Implementation Strategy**:
  - **Phase 1**: Desktop full rendering (sudah ada).
  - **Phase 2**: Tambah API endpoints untuk data master & transaksi.
  - **Phase 3**: Mobile client (JavaScript/Vue) konsumsi API.
  - **Phase 4**: Hybrid rendering (partial load + API) untuk optimalisasi.
- **Code Example**:
  ```php
  // Di Controller
  public function index() {
      if ($this->acceptJson()) {
          return $this->jsonResponse($this->getLoans(10, $page));
      } else {
          return view('loans.index', ['loans' => $this->getLoans(50)]);
      }
  }
  ```
- **Testing**:
  - **Desktop**: Test rendering HTML, pagination, filter.
  - **Mobile**: Test API response, pagination, error handling.
  - **Cross-Device**: Test data konsistensi antara desktop & mobile.

### 11.24 Mitigasi Dokumen Tidak Lengkap (Anti-Fraud & Risk Management)
- **Kebutuhan Dokumen**:
  - **KTP**: Identitas resmi, verifikasi kebenaran data pribadi.
  - **KK**: Data keluarga, status perkawinan, tanggung jawab.
  - **Slip Gaji/Usaha**: Bukti penghasilan, stabilitas keuangan, kemampuan bayar.
  - **Foto Rumah/Usaha**: Verifikasi fisik lokasi, kondisi, keberadaan.
  - **Agunan**: Jaminan pinjaman, nilai taksasi, keabsahan kepemilikan.
- **Risiko Dokumen Tidak Lengkap**:
  - **Identitas Palsu**: Penipuan identitas, pinjaman fiktif, multiple applications.
  - **Kredit Macet**: Peminjam tidak mampu bayar, kolektibilitas buruk, potensi hilang.
  - **Penyalahgunaan Dana**: Pinjaman digunakan untuk tujuan ilegal (judi, investasi bodong).
  - **Kerugian Reputasi**: Koperasi dianggap tidak profesional, kehilangan anggota.
- **Strategi Mitigasi**:
  - **Multi-Verifikasi**: Cross-check data dengan sumber eksternal (Dukcapil, SLIK, telepon kerabat).
  - **Digital Verification**: OCR otomatis, validasi format, deteksi duplikat.
  - **Survei Lapangan Wajib**: AO kunjungi alamat, foto lokasi, wawancara tetangga.
  - **Scoring System**: Nilai kelayakan berdasarkan kelengkapan dokumen (0–100).
  - **Plafon Bertahap**: Untuk aplikasi tanpa dokumen lengkap, batasi plafon rendah.
  - **Agunan Berlebih**: Jika dokumen kurang, wajibkan agunan lebih tinggi.
- **Prosedur Jika Dokumen Kurang**:
  - **1–3 Hari**: Notifikasi ke peminjam untuk lengkapi dokumen; status "Pending".
  - **4–7 Hari**: AO follow-up via telepon; kunjungi alamat jika perlu.
  - **8–14 Hari**: Surat peringatan resmi; batas waktu 7 hari untuk lengkapi.
  - **>14 Hari**: Tolak otomatis; catat alasan di sistem; blacklist jika perlu.
- **Fitur Early Warning**:
  - **Alert Dashboard**: Notifikasi ke Manager/AO ada pengajuan dengan dokumen kurang.
  - **Risk Scoring**: Otomatis hitung risiko berdasarkan kelengkapan.
  - **Blacklist**: Database peminjam bermasalah (penipuan, macet, hilang).
  - **SLIK Check**: Cross-check ke database BI Checking (jika ada API).
- **Validasi Dokumen**:
  - **KTP**: Format 16 digit, nama sesuai, tidak expired, foto jelas, tidak blur.
  - **KK**: Nomor KK valid, anggota sesuai KTP, tidak ada coretan.
  - **Slip Gaji**: Masa berlaku ≤ 3 bulan, perusahaan valid, gaji masuk akal.
  - **Foto**: Jelas, terang, tidak blur, ada timestamp GPS (jika mobile).
  - **Agunan**: Dokumen lengkap (BPKB, sertifikat), foto jelas, nilai taksasi masuk akal.
- **Alternatif Dokumen**:
  - **Surat Keterangan Usaha**: Dari RT/RW atau lurah setempat jika tidak ada slip gaji.
  - **Surat Keterangan Domisili**: Dari Desa/Kelurahan jika alamat tidak jelas.
  - **Referensi Bank**: Rekening koran 3 bulan terakhir sebagai bukti penghasilan.
  - **Interview Video**: AO lakukan wawancara singkat via video call.
- **Keamanan Data**:
  - **Enkripsi**: Dokumen dienkripsi di storage; akses per role.
  - **Watermark**: Tambah watermark "KSP LAM GABE JAYA" pada foto.
  - **Hash Verification**: Hash file untuk cek duplikat dan integritas.
  - **Retention**: Hapus dokumen lama setelah periode tertentu (sesuai kebijakan).
- **UI/UX untuk Pengajuan**:
  - **Progress Bar**: Indikator kelengkapan dokumen (0–100%).
  - **Checklist**: Checklist dokumen yang harus diupload.
  - **Preview**: Preview dokumen sebelum submit.
  - **Error Messages**: Pesan error spesifik untuk setiap dokumen.
  - **Auto-Save**: Simpan draft pengajuan jika dokumen belum lengkap.
- **Integrasi Sistem**:
  - **OCR**: Ekstrak teks dari KTP/KK untuk auto-fill form.
  - **Face Recognition**: Verifikasi foto KTP dengan foto pengajuan (opsional).
  - **Location Check**: Bandingkan GPS foto dengan alamat di form.
  - **API Verification**: Kirim data ke eksternal verification service (jika ada).
- **Audit Trail**:
  - **Log Upload**: Siapa upload, kapan, dokumen apa, timestamp.
  - **Log Perubahan**: Perubahan status dokumen dicatat user & timestamp.
  - **Log Akses**: Siapa lihat dokumen, kapan, timestamp.
  - **Log Penolakan**: Alasan penolakan dicatat untuk evaluasi.
- **Kebijakan Pengurus**:
  - **Tidak Ada Kompromi**: Pengajuan tanpa dokumen lengkap tidak bisa disetujui.
  - **Batas Risiko**: AO tidak boleh ajukan tanpa dokumen valid.
  - **Evaluasi Berkala**: Manager review kasus dokumen kurang rutin bulanan.
  - **Sanksi**: AO yang mengabaikan verifikasi dapat dikenai sanksi.

### 11.26 Pinjaman Tanpa Agunan (Unsecured Loan Policy)
- **Kebijakan Dasar**:
  - **Pinjaman Tanpa Agunan**: Diperbolehkan dengan syarat ketat, plafon rendah, dan bunga lebih tinggi.
  - **Tujuan**: Melayani anggota baru/usaha kecil yang belum memiliki agunan cukup.
  - **Risiko**: Lebih tinggi; mitigasi dengan scoring ketat dan monitoring intensif.
- **Kriteria Peminjam Tanpa Agunan**:
  - **Lama Menjadi Anggota**: Minimum 6 bulan aktif (simpanan rutin).
  - **Riwayat Simpanan**: Simpanan wajib & sukarela konsisten ≥ 6 bulan.
  - **Riwayat Pinjaman**: Tidak ada tunggakan sebelumnya (jika pernah pinjam).
  - **Penghasilan Stabil**: Slip gaji/usaha ≥ 3 bulan terakhir.
  - **Usia**: 21–55 tahun (masuk produktif).
  - **DSR Maksimal**: 30% (lebih ketat dari pinjaman beragunan).
- **Plafon & Tenor**:
  - **Plafon Maksimal**: Rp5.000.000 – Rp10.000.000 (sesuai kebijakan Pengurus).
  - **Tenor Maksimal**: 12 bulan (tidak boleh >1 tahun).
  - **Peningkatan Bertahap**: Pinjaman pertama ≤ Rp5.000.000; bisa naik setelah lunas.
- **Bunga & Biaya**:
  - **Bunga Lebih Tinggi**: +2%–4% dari bunga pinjaman beragunan (misal 18% vs 14%).
  - **Biaya Administrasi**: Lebih tinggi (misal 2% dari plafon).
  - **Biaya Provisi**: Lebih tinggi (misal 1% dari plafon).
  - **Asuransi**: Wajib asuransi jiwa (jika ada kerjasama).
- **Proses Persetujuan**:
  - **Scoring Ketat**: Minimum skor 80/100 untuk approve tanpa agunan.
  - **Persetujuan Berjenjang**: AO → Manager → Pengurus (wajib Pengurus approve).
  - **Dokumen Tambahan**: Surat keterangan usaha, referensi 2 anggota lain.
  - **Interview Wajib**: AO wawancara langsung di tempat usaha/rumah.
- **Monitoring Intensif**:
  - **Weekly Check**: AO kunjungi/telepon setiap minggu pertama.
  - **Payment Reminder**: Notifikasi 3 hari sebelum jatuh tempo.
  - **Early Warning**: Notifikasi otomatis jika terlambat >1 hari.
  - **Collection**: Penagihan lapangan dimulai hari ke-2 keterlambatan.
- **Kebijakan Gagal Bayar**:
  - **Blacklist**: Peminjam macet tanpa agunan langsung blacklist.
  - **Penagihan Intensif**: Kunjungan harian, telepon ke kerabat, surat somasi.
  - **Legal Action**: Jika >60 hari macet, proses hukum (perjanjian bermaterai).
  - **SHU Potongan**: Potong SHU anggota (jika ada) untuk menutupi kerugian.
- **UI/UX Khusus**:
  - **Form Pengajuan**: Checkbox "Tanpa Agunan" → muncul syarat tambahan.
  - **Kalkulator**: Otomatis hitung bunga lebih tinggi jika tanpa agunan.
  - **Warning**: Pesan peringatan risiko tinggi sebelum submit.
  - **Status Tracking**: Status "Tanpa Agunan" terlihat di dashboard.
- **Integrasi Sistem**:
  - **Scoring Engine**: Otomatis hitung skor berdasarkan kriteria.
  - **Risk Dashboard**: Khusus monitoring pinjaman tanpa agunan.
  - **Alert System**: Notifikasi khusus untuk pinjaman tanpa agunan.
  - **Report**: Laporan khusus pinjaman tanpa agunan (performa, NPL).
- **Kebijakan Pengurus**:
  - **Limit Portfolio**: Maksimal 10% dari total portfolio pinjaman tanpa agunan.
  - **Review Bulanan**: Evaluasi performa pinjaman tanpa agunan setiap bulan.
  - **Adjustment**: Bisa naik/turunkan plafon atau bunga berdasarkan performa.
  - **Approval**: Setiap perubahan kebijakan butuh persetujuan Pengurus.
- **Alternatif Mitigasi**:
  - **Jaminan Sosial**: Surat pernyataan dari 2 anggota sebagai penjamin.
  - **Auto-Debit**: Wajib auto-debit rekening gaji (jika kerja formal).
  - **Linkage Program**: Linkage dengan bank untuk penagihan otomatis.
  - **Group Lending**: Pinjaman kelompok dengan tanggung jawab bersama (opsional).
- **Contoh Kasus**:
  - **Kasus 1**: Anggota 2 tahun, simpanan rutin, penghasilan Rp3.000.000, DSR 25% → bisa pinjam Rp5.000.000 tanpa agunan, bunga 18%.
  - **Kasus 2**: Anggota baru 3 bulan, tidak ada riwayat → tidak bisa pinjam tanpa agunan.
  - **Kasus 3**: Anggota 1 tahun, pernah telat 7 hari → tidak bisa pinjam tanpa agunan.

### 11.48 Versioning Strategy (Application, API & Database)
- **Konsep Versioning**:
  - **Semantic Versioning**: Menggunakan semantic versioning (MAJOR.MINOR.PATCH).
  - **Backward Compatibility**: Memastikan backward compatibility untuk API dan database.
  - **Rollback Strategy**: Strategy untuk rollback jika versi baru bermasalah.
  - **Deployment Pipeline**: Automated deployment dengan version control.
- **Application Versioning**:
  - **Version Format**: v1.0.0 (Major.Minor.Patch).
  - **Major Version**: Perubahan besar yang tidak compatible (v1.0.0 → v2.0.0).
  - **Minor Version**: Fitur baru dengan backward compatibility (v1.0.0 → v1.1.0).
  - **Patch Version**: Bug fixes dan perbaikan kecil (v1.0.0 → v1.0.1).
  - **Pre-release**: Alpha, Beta, RC versions (v1.0.0-alpha.1, v1.0.0-beta.1).
- **API Versioning Strategy**:
  - **URL Versioning**: `/api/v1/loans`, `/api/v2/loans`.
  - **Header Versioning**: `Accept: application/vnd.api+json;version=1`.
  - **Query Parameter Versioning**: `?version=1`.
  - **Version Deprecation**: Sunset policy untuk versi lama.
- **API Versioning Implementation**:
  ```
  Version 1 (v1):
  - Basic CRUD operations
  - Simple authentication
  - Basic data structures
  
  Version 2 (v2):
  - Advanced filtering & sorting
  - Enhanced security (JWT)
  - Optimized data structures
  - Backward compatible with v1
  
  Version 3 (v3):
  - GraphQL support
  - Real-time updates
  - Advanced analytics
  - Breaking changes from v2
  ```
- **Database Versioning**:
  - **Migration Scripts**: SQL migration scripts untuk setiap versi.
  - **Schema Versioning**: Table `schema_versions` untuk track database version.
  - **Rollback Scripts**: Rollback scripts untuk setiap migration.
  - **Data Migration**: Data migration scripts untuk structural changes.
- **Database Migration Strategy**:
  ```
  Migration Files:
  - migrations/001_initial_schema.sql
  - migrations/002_add_multi_tenant.sql
  - migrations/003_add_notifications.sql
  - migrations/004_optimize_loans.sql
  
  Version Tracking:
  CREATE TABLE schema_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(20) NOT NULL,
    description TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rollback_script TEXT
  );
  ```
- **Multi-Tenant Versioning**:
  - **Tenant-Specific Versions**: Setiap tenant bisa memiliki versi berbeda.
  - **Global Version**: Versi global untuk core system.
  - **Feature Flags**: Fitur baru bisa diaktifkan per tenant.
  - **Gradual Rollout**: Rollout bertahap per tenant.
- **Version Control Integration**:
  - **Git Tags**: Git tags untuk setiap release (v1.0.0, v1.1.0).
  - **Branch Strategy**: Main untuk production, develop untuk development.
  - **Release Notes**: Release notes untuk setiap versi.
  - **Changelog**: Changelog otomatis dari commit messages.
- **Deployment Pipeline**:
  ```
  Pipeline Stages:
  1. Development → Testing → Staging → Production
  2. Automated testing per stage
  3. Database migration per stage
  4. Health checks per stage
  5. Rollback capability per stage
  ```
- **Version Detection**:
  - **Application Version**: `APP_VERSION` constant.
  - **API Version**: `API_VERSION` header.
  - **Database Version**: Query ke `schema_versions` table.
  - **Compatibility Check**: Check compatibility antar versi.
- **Backward Compatibility**:
  - **API Compatibility**: Maintain v1 endpoints while introducing v2.
  - **Database Compatibility**: Use views for backward compatibility.
  - **Data Format**: Maintain old data format alongside new format.
  - **Feature Flags**: Disable new features for old versions.
- **Rollback Strategy**:
  - **Application Rollback**: Deploy previous version using Git.
  - **Database Rollback**: Execute rollback migration scripts.
  - **API Rollback**: Switch to previous API version.
  - **Data Recovery**: Restore data from backup if needed.
- **Version Monitoring**:
  - **Health Check Endpoint**: `/api/health` dengan version info.
  - **Version Dashboard**: Dashboard untuk monitoring versi per tenant.
  - **Alert System**: Alert untuk version mismatches.
  - **Performance Monitoring**: Monitor performance per version.
- **Testing Strategy**:
  - **Version Testing**: Test backward compatibility.
  - **Migration Testing**: Test database migrations.
  - **API Testing**: Test API versioning.
  - **Integration Testing**: Test integration between versions.
- **Release Management**:
  - **Release Schedule**: Regular release schedule (monthly/quarterly).
  - **Release Notes**: Detailed release notes for each version.
  - **Communication**: Communication plan for version updates.
  - **Training**: Training for new versions.
- **Multi-Koperasi Versioning**:
  - **Cooperative Versions**: Setiap koperasi bisa memiliki versi berbeda.
  - **Version Isolation**: Isolasi versi per koperasi.
  - **Upgrade Path**: Path untuk upgrade per koperasi.
  - **Support Matrix**: Support matrix per versi.
- **Version Lifecycle**:
  ```
  Lifecycle Stages:
  1. Development → Alpha → Beta → RC → Release
  2. Maintenance → Security Updates → Deprecation → End of Life
  3. Support Period: 12 months for major versions
  4. Security Updates: 6 months after EOL
  ```
- **Implementation Examples**:
  - **Version 1.0.0**: Initial release with basic features.
  - **Version 1.1.0**: Add notifications feature (backward compatible).
  - **Version 1.2.0**: Add multi-tenant support (backward compatible).
  - **Version 2.0.0**: Major rewrite with breaking changes.
  - **Version 2.1.0**: Add GraphQL support (backward compatible).
- **Changelog Management**:
  ```
  Changelog Format:
  ## [Unreleased]
  ### Added
  - New notification system
  ### Changed
  - Improved API performance
  ### Deprecated
  - Old authentication method
  ### Removed
  - Legacy endpoints
  ### Fixed
  - Bug in loan calculation
  ```
- **Version Configuration**:
  ```php
  // config/version.php
  return [
      'app_version' => '1.2.0',
      'api_version' => 'v1',
      'db_version' => '1.2.0',
      'supported_api_versions' => ['v1'],
      'deprecated_api_versions' => [],
      'feature_flags' => [
          'notifications' => true,
          'multi_tenant' => true,
          'offline_support' => false,
      ],
  ];
  ```
- **Security Considerations**:
  - **Version Authentication**: Authenticate API calls per version.
  - **Rate Limiting**: Different rate limits per version.
  - **Access Control**: Control access based on version.
  - **Audit Trail**: Audit trail for version changes.
- **Benefits of Versioning**:
  - **Stability**: Stable releases with proper testing.
  - **Flexibility**: Flexibility to introduce breaking changes.
  - **Maintainability**: Maintainable codebase with clear versioning.
  - **Scalability**: Scalable architecture with version support.
  - **Reliability**: Reliable deployment with rollback capability.

### 11.49 Teknologi & Keamanan
- **Partial Load**: SPA-style tanpa reload penuh (sudah ada).
- **RBAC**: Middleware per controller/action, audit log.
- **Export**: CSV/Excel untuk laporan.
- **PDF**: SKB, laporan, slip gaji, SK restrukturisasi, berita acara eksekusi, berita acara serah terima, tabel angsuran, laporan keuangan (DOMPDF/MPDF).
- **Push Notification**: Notifikasi mobile (jatuh tempo, persetujuan, SHU, gaji, NPL warning, peminjam hilang, pengawasan, dokumen baru, perubahan bunga, cashflow negatif, dokumen kurang, pinjaman tanpa agunan, perjanjian digital, kasir tidak ada, petugas pencairan, tracking karyawan).
- **Multi-Channel Notifications**: WhatsApp Business API, SMS gateway, push notifications, email, unified notification engine.
- **Document Generation Engine**: Template engine untuk surat resmi, dynamic content, PDF/Word generation, digital signature, batch processing.
- **Status Management Engine**: Workflow status peminjam hilang, automated triggers, approval workflows, lifecycle management.
- **Risk Management Engine**: Provisioning, loan loss reserve, collateral coverage, early warning indicators, capital adequacy monitoring.
- **Product-Based Interest Engine**: Bunga berbeda per produk, risk-based pricing, dynamic adjustment, product validation.
- **Multi-Tenant Engine**: Tenant detection, database switching, session management, cache isolation, configuration management.
- **Versioning Engine**: Semantic versioning, migration management, rollback capability, compatibility checking.
- **Rate Limit**: Login & pengajuan pinjaman; API lebih ketat (100 req/menit per user).
- **Backup**: Otomatis DB & lampiran; offsite.
- **Enkripsi**: Data sensitif (penghasilan, agunan, dokumen pribadi, gaji, file dokumen).
- **Audit Trail**: Semua aksi dicatat user, timestamp, device.
- **GPS Tracking**: AO input lokasi survei; bandingkan dengan data pengajuan; petugas pencairan real-time tracking; karyawan lapangan tracking.
- **SLIK Integration**: Cross-check riwayat kredit (opsional, jika ada API).
- **Dashboard Pengawasan**: KPI kepatutan Pengurus, alert otomatis, status Pengurus.
- **Document Management**: Upload foto/kamera, OCR, enkripsi, watermark, storage lokal/cloud.
- **Bunga Engine**: Kalkulator pinjaman, generate tabel angsuran, akru bunga, pajak bunga.
- **Akuntansi Engine**: Jurnal otomatis, buku besar, laba/rugi, neraca, cash flow, SHU.
- **API Layer**: RESTful API dengan JSON response, caching, compression, versioning.
- **Anti-Fraud Engine**: Scoring dokumen, blacklist, verifikasi eksternal, early warning.
- **Unsecured Loan Engine**: Scoring khusus tanpa agunan, monitoring intensif, bunga dinamis.
- **Digital Contract Engine**: Generate PDF di mobile, digital signature, offline sync, legal compliance.
- **Delegation Engine**: Mobile approval, queue management, emergency cash mode, backup kasir.
- **Field Disbursement Engine**: Petugas pencairan, safety box, GPS tracking, route optimization, security protocol.
- **Employee Tracking Engine**: GPS tracking, check-in/out, route planning, performance analytics, geofencing.
- **Notification Engine**: Multi-channel notifications (WhatsApp, SMS, Push, Email), template management, analytics.
- **Document Engine**: Template-based document generation, legal compliance, batch processing, digital signatures.
- **Lost Borrower Engine**: Status lifecycle management, automated triggers, investigation workflows, recovery processes.
- **Accounting Risk Engine**: Provisioning, bad loans, asset recovery, compliance (PSAKK 71, IFRS 9), capital adequacy.
- **Product Interest Engine**: Product-based interest rates, risk pricing, dynamic adjustment, validation.
- **Multi-Tenant Engine**: Tenant detection, database switching, session management, cache isolation, configuration management.
- **Versioning Engine**: Semantic versioning, migration management, rollback capability, compatibility checking.

---

## 12. Urutan Implementasi Disarankan (Mobile-First & Risiko)

1. **Role & RBAC**: Definisi role (AO, Kasir, Manager, Pengurus, Anggota), middleware `AuthHelper::can`, pembatasan menu/aksi per role.
2. **Mobile Portal Anggota**: Login anggota, pengajuan pinjaman (upload dokumen/foto/GPS), lihat saldo, riwayat, bayar via virtual account, notifikasi push.
3. **Simpanan**: Modul simpanan (pokok/wajib/sukarela/SISUKA), transaksi setor/tarik (mobile & kantor), bunga bulanan otomatis.
4. **Pinjaman – Alur Lapangan**:
   - Pengajuan (mobile/kantor) → Verifikasi CS → Survei AO (mobile input, foto, GPS) → Analisis AO → Persetujuan digital → SKB → Pencairan (Kasir/Petugas) → Angsuran → Denda → Kolektibilitas.
5. **Produk & Tarif**: Master produk pinjaman & simpanan, COA, saldo awal, konfigurasi bunga (flat/efektif/menurun/mengambang), bunga per produk.
6. **Bunga Engine**: Kalkulator pinjaman, generate tabel angsuran, akru bunga, pajak bunga, validasi DSR.
7. **Akuntansi Engine**: Jurnal otomatis, buku besar, laba/rugi, neraca, cash flow, perhitungan modal, SHU.
8. **SHU**: Perhitungan tahunan, alokasi persentase, laporan per anggota, notifikasi mobile.
9. **Pelaporan & Dashboard**: Laporan aging, kolektibilitas, cashflow, KPI real-time (mobile & desktop), export CSV/Excel.
10. **Rapat**: Modul PRA RAT/RAT, agenda, notulen, keputusan, upload dokumen.
11. **Gaji & Biaya**: Modul penggajian (slip gaji digital), insentif AO/Kasir/Manajer, biaya operasional, cashflow harian, approval RAPB.
12. **NPL & Restrukturisasi**: Early warning, penagihan bertahap, workflow restrukturisasi digital, eksekusi agunan, write-off.
13. **Peminjam Hilang**: Data lengkap, monitoring aktif, status "Hilang", early warning, kunjungan lapangan, proses hukum, eksekusi agunan.
14. **Pengawasan Pengurus**: Mekanisme pengawasan internal, indikasi tidak aktif, tindakan bertahap, sanksi, pergantian, dashboard pengawasan.
15. **Document Management**: Upload foto/kamera (mobile & web), GPS metadata, OCR, enkripsi, watermark, storage, verifikasi dokumen.
16. **Anti-Fraud Engine**: Scoring dokumen, blacklist, verifikasi eksternal, early warning dokumen kurang.
17. **Unsecured Loan Engine**: Scoring khusus tanpa agunan, monitoring intensif, bunga dinamis, limit portfolio.
18. **Digital Contract Engine**: Generate PDF di mobile, digital signature, offline sync, legal compliance.
19. **Delegation Engine**: Mobile approval, queue management, emergency cash mode, backup kasir.
20. **Field Disbursement Engine**: Petugas pencairan, safety box, GPS tracking, route optimization, security protocol.
21. **Employee Tracking Engine**: GPS tracking, check-in/out, route planning, performance analytics, geofencing.
22. **Notification Engine**: Multi-channel notifications (WhatsApp, SMS, Push, Email), template management, analytics.
23. **Document Generation Engine**: Template engine untuk surat resmi, dynamic content, PDF/Word generation, digital signature, batch processing.
24. **Lost Borrower Engine**: Status lifecycle management, automated triggers, investigation workflows, recovery processes.
25. **Accounting Risk Engine**: Provisioning, bad loans, asset recovery, compliance (PSAKK 71, IFRS 9), capital adequacy.
26. **Product Interest Engine**: Product-based interest rates, risk pricing, dynamic adjustment, validation.
27. **Multi-Tenant Engine**: Tenant detection, database switching, session management, cache isolation, configuration management.
28. **Versioning Engine**: Semantic versioning, migration management, rollback capability, compatibility checking.
29. **API Layer**: RESTful API endpoints, JSON response, caching, compression, versioning, authentication.
30. **Keamanan & Risiko**:
    - Audit trail lengkap per aksi.
    - Batasan wewenang: AO tidak bisa cairkan; Kasir wajib laporkan cashflow harian.
    - Early warning NPL > 90 hari, peminjam hilang >7 hari, Pengurus tidak aktif >6 bulan; notifikasi ke Manager/Pengawas.
    - Backup otomatis & enkripsi data sensitif.
31. **Integrasi**: PDF SKB & slip gaji, push notification, virtual account pembayaran, GPS tracking, SLIK (opsional), dashboard pengawasan, document management, bunga engine, akuntansi engine, API layer, anti-fraud engine, unsecured loan engine, digital contract engine, delegation engine, field disbursement engine, employee tracking engine, notification engine, document generation engine, lost borrower engine, accounting risk engine, product interest engine, multi-tenant engine, versioning engine.
32. **UX/Export**: Filter, export, mobile responsif, CTA stack, scroll tabel.
33. **Peta**: Leaflet/OpenStreetMap integrasi (opsional).
34. **Testing**: Role & izin, alur pinjaman mobile, perhitungan SHU, kolektibilitas, cross-check cashflow, slip gaji, NPL workflow, peminjam hilang, pengawasan Pengurus, upload foto/OCR, kalkulator bunga, jurnal/akuntansi, API endpoints, anti-fraud, pinjaman tanpa agunan, digital signature, delegasi kasir, petugas pencairan, tracking karyawan, multi-channel notifications, document generation, status management, accounting risk, product interest, multi-tenant, versioning.

---

### Catatan Tambahan
- **AD/ART**: Semua fitur harus konsisten dengan AD/ART KSP LAM GABE JAYA.
- **Template Dokumen**: Gunakan `plan/surat.md` untuk SKB otomatis.
- **Lokalisasi**: Bahasa Indonesia untuk semua UI/label.
- **Partial Load**: Pastikan semua view baru pakai `#dynamicContent`.
- **Testing**: Role & izin, alur pinjaman mobile, perhitungan SHU, kolektibilitas, cross-check cashflow, slip gaji, NPL workflow, peminjam hilang, pengawasan Pengurus, upload foto/OCR, kalkulator bunga, jurnal/akuntansi, API endpoints, anti-fraud, pinjaman tanpa agunan, digital signature, delegasi kasir, petugas pencairan, tracking karyawan, multi-channel notifications, document generation, status management, accounting risk, product interest, multi-tenant, versioning.
- **Kolektibilitas**: Implementasi update otomatis berdasarkan hari keterlambatan.
- **Mobile-First**: Prioritaskan UX anggota & petugas lapangan; CTA stack, scroll tabel, push notification.
- **Keamanan**: Enkripsi data gaji & sensitif; audit trail; batasan wewenang untuk mencegah penyelewengan.
- **Gaji**: Pengurus hanya uang jasa (RAPB); Pengelola gaji/honor; insentif berbasis KPI; approval RAPB di RAT.
- **Biaya**: Pisahkan biaya tetap vs variabel; bebankan biaya admin/provisi ke peminjam; cashflow harian wajib dicatat.
- **NPL**: Early warning, penagihan bertahap, restrukturisasi digital, eksekusi agunan, write-off sesuai prosedur.
- **Restrukturisasi**: Rescheduling/Reconditioning/Restructuring dengan workflow digital dan SK Baru.
- **Peminjam Hilang**: Data lengkap, monitoring aktif, status tracking, early warning, kunjungan lapangan, proses hukum, eksekusi agunan.
- **Pengawasan Pengurus**: Mekanisme internal, indikasi tidak aktif, tindakan bertahap, sanksi, pergantian, dashboard pengawasan.
- **Document Management**: Upload foto/kamera, GPS metadata, OCR, enkripsi, watermark, storage, verifikasi dokumen.
- **Bunga Pinjaman**: Flat/efektif/menurun/mengambang; kalkulator real-time; tabel angsuran; akru bunga; pajak bunga; validasi DSR.
- **Modal & Keuntungan**: Struktur modal, perhitungan laba/rugi, SHU, cash flow, neraca, KPI (ROA, ROE, LDR).
- **Data Rendering**: Desktop full HTML rendering; Mobile API-first dengan JSON response; Hybrid approach.
- **API Layer**: RESTful API dengan JSON response, caching, compression, versioning, authentication.
- **Anti-Fraud**: Scoring dokumen, blacklist, verifikasi eksternal, early warning dokumen kurang, mitigasi penipuan.
- **Pinjaman Tanpa Agunan**: Bunga lebih tinggi (+2–4%), plafon rendah (Rp5–10 juta), scoring ketat, monitoring intensif, limit portfolio 10%.
- **Digital Contract**: Generate PDF di mobile, digital signature, offline sync, legal compliance, tanda tangan di lapangan tanpa printer.
- **Delegasi Kasir**: Mobile approval, queue management, emergency cash mode, backup ketika Kasir/CS tidak ada.
- **Petugas Pencairan**: Field disbursement officer, safety box, GPS tracking, route optimization, security protocol, pencairan di lokasi peminjam.
- **Penagihan Lapangan & Tracking**: GPS tracking karyawan, check-in/out, photo evidence, digital receipt, performance analytics, geofencing, penilaian kinerja.
- **Multi-Channel Notifications**: WhatsApp Business API, SMS gateway, push notifications, email, template management, analytics, fallback mechanism.
- **Document Generation**: Template engine untuk surat resmi, dynamic content, PDF/Word generation, digital signature, batch processing, legal compliance.
- **Status Management Peminjam Hilang**: Status lifecycle (Aktif → Risiko Hilang → Hilang → Ditemukan → Blacklist), automated triggers, investigation workflows, recovery processes.
- **Akuntansi & Perlindungan**: Provisioning, loan loss reserve, collateral coverage, asset recovery, compliance (PSAKK 71, IFRS 9), capital adequacy, risk management framework.
- **Bunga per Jenis Produk**: Bunga berbeda untuk Pinjaman Produktif (12-18%), Konsumtif (18-24%), Darurat (24-30%), Modal Kerja (15-20%) dengan tenor, plafon, dan agunan yang sesuai.
- **Multi-Koperasi Support**: Single instance multi-tenant architecture, database isolation per koperasi, tenant detection, domain routing, branding per koperasi.
- **Versioning Strategy**: Semantic versioning (MAJOR.MINOR.PATCH), API versioning, database migration, backward compatibility, rollback capability, multi-tenant versioning.
- **Audit**: Semua tindakan penagihan, restrukturisasi, eksekusi, peminjam hilang, keputusan Pengurus, akses dokumen, perubahan bunga, jurnal akuntansi, API calls, anti-fraud checks, pinjaman tanpa agunan, digital signature, delegasi kasir, petugas pencairan, tracking karyawan, multi-channel notifications, document generation, status management, accounting risk, product interest, multi-tenant, versioning dicatat user & timestamp; laporan bulanan ke RAT.
- **GPS & SLIK**: AO input lokasi survei; cross-check riwayat kredit (opsional); petugas pencairan real-time tracking; karyawan lapangan real-time tracking.
- **Dashboard Pengawasan**: KPI kepatutan Pengurus, alert otomatis, status Pengurus, notifikasi ke Pengawas.
