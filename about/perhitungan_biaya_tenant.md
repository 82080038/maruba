# Perhitungan Biaya Tenant untuk Aplikasi KSP Multi-Koperasi

## Konsep Biaya Tenant

Sistem multi-koperasi dirancang untuk memberikan skalabilitas dan efisiensi biaya bagi koperasi yang menggunakan aplikasi KSP LAM GABE JAYA. Setiap koperasi (tenant) akan dikenakan biaya berdasarkan penggunaan, fitur yang digunakan, dan volume transaksi.

## Model Biaya

### 1. Model Berlangganan (Subscription-Based)
Model berlangganan dengan paket tiered berdasarkan kebutuhan koperasi.

#### Paket Starter
- **Target**: Koperasi kecil (< 100 anggota)
- **Biaya**: Rp 500.000/bulan
- **Fitur**:
  - Manajemen anggota dasar
  - Simpanan & pinjaman dasar
  - Laporan standar
  - Support email
- **Limit**:
  - 100 anggota
  - 50 transaksi/bulan
  - 1 admin user
  - 1 GB storage

#### Paket Professional
- **Target**: Koperasi menengah (100-500 anggota)
- **Biaya**: Rp 1.500.000/bulan
- **Fitur**:
  - Semua fitur Starter
  - Multi-user (5 admin users)
  - Advanced reporting
  - Mobile app untuk anggota
  - WhatsApp notifications
  - Priority support
- **Limit**:
  - 500 anggota
  - 500 transaksi/bulan
  - 5 admin users
  - 5 GB storage

#### Paket Enterprise
- **Target**: Koperasi besar (> 500 anggota)
- **Biaya**: Rp 3.000.000/bulan
- **Fitur**:
  - Semua fitur Professional
  - Unlimited users
  - Custom branding
  - Advanced analytics
  - API access
  - Dedicated support
  - Custom features (sesuai kesepakatan)
- **Limit**:
  - Unlimited anggota
  - Unlimited transaksi
  - Unlimited users
  - 20 GB storage

### 2. Model Berdasarkan Penggunaan (Usage-Based)
Biaya dihitung berdasarkan volume penggunaan aktual.

#### Komponen Biaya:
- **Biaya Dasar**: Rp 300.000/bulan
- **Biaya per Anggota**: Rp 2.000/anggota/bulan
- **Biaya per Transaksi**: Rp 100/transaksi (setelah 100 transaksi pertama)
- **Biaya Storage**: Rp 100/GB/bulan (setelah 1 GB pertama)
- **Biaya API Call**: Rp 0.01/call (setelah 1.000 call pertama)

#### Contoh Perhitungan:
```
Koperasi dengan:
- 250 anggota
- 300 transaksi/bulan
- 2 GB storage
- 500 API calls

Biaya Dasar: Rp 300.000
Biaya Anggota: 250 × Rp 2.000 = Rp 500.000
Biaya Transaksi: (300-100) × Rp 100 = Rp 20.000
Biaya Storage: (2-1) × Rp 100 = Rp 100
Biaya API: (500-1000) × Rp 0.01 = Rp 0 (masih dalam limit)
Total: Rp 820.100/bulan
```

### 3. Model Hybrid (Subscription + Usage)
Kombinasi biaya berlangganan dasar dengan biaya tambahan berdasarkan penggunaan.

#### Struktur Biaya:
- **Biaya Berlangganan**: Rp 1.000.000/bulan (termasuk 200 anggota, 200 transaksi)
- **Biaya Tambahan**:
  - Anggota tambahan: Rp 1.500/anggota/bulan
  - Transaksi tambahan: Rp 50/transaksi
  - Storage tambahan: Rp 50/GB/bulan
  - Fitur premium: Rp 200.000/fitur/bulan

#### Contoh Perhitungan:
```
Koperasi dengan:
- 350 anggota (150 tambahan)
- 400 transaksi (200 tambahan)
- 3 GB storage (2 GB tambahan)
- 2 fitur premium

Biaya Berlangganan: Rp 1.000.000
Biaya Anggota Tambahan: 150 × Rp 1.500 = Rp 225.000
Biaya Transaksi Tambahan: 200 × Rp 50 = Rp 10.000
Biaya Storage Tambahan: 2 × Rp 50 = Rp 100
Biaya Fitur Premium: 2 × Rp 200.000 = Rp 400.000
Total: Rp 1.635.100/bulan
```

## Cara Menghitung Biaya

### Step 1: Identifikasi Kebutuhan
1. **Jumlah Anggota**: Total anggota aktif
2. **Volume Transaksi**: Rata-rata transaksi per bulan
3. **Jumlah User**: Jumlah admin/staf yang akan menggunakan sistem
4. **Fitur yang Dibutuhkan**: Daftar fitur yang akan digunakan
5. **Storage**: Kebutuhan penyimpanan data
6. **API Usage**: Jika menggunakan integrasi API

### Step 2: Pilih Model Biaya
Pilih model yang paling sesuai dengan pola penggunaan:
- **Subscription**: Jika penggunaan stabil dan dapat diprediksi
- **Usage-Based**: Jika penggunaan fluktuatif
- **Hybrid**: Jika ingin kombinasi keduanya

### Step 3: Hitung Estimasi Biaya
Gunakan kalkulator biaya atau spreadsheet untuk menghitung estimasi biaya bulanan.

### Step 4: Bandingkan Opsi
Bandingkan total biaya antar model untuk memilih yang paling ekonomis.

## Kalkulator Biaya Otomatis

### Formula Perhitungan
```javascript
function calculateTenantCost(model, params) {
    let cost = 0;
    
    switch(model) {
        case 'starter':
            cost = 500000;
            if (params.members > 100) cost += (params.members - 100) * 2000;
            if (params.transactions > 50) cost += (params.transactions - 50) * 100;
            break;
            
        case 'professional':
            cost = 1500000;
            if (params.members > 500) cost += (params.members - 500) * 1500;
            if (params.transactions > 500) cost += (params.transactions - 500) * 80;
            break;
            
        case 'enterprise':
            cost = 3000000;
            // Unlimited untuk enterprise
            break;
            
        case 'usage':
            cost = 300000; // Biaya dasar
            cost += params.members * 2000;
            if (params.transactions > 100) cost += (params.transactions - 100) * 100;
            if (params.storage > 1) cost += (params.storage - 1) * 100;
            break;
            
        case 'hybrid':
            cost = 1000000; // Biaya berlangganan
            if (params.members > 200) cost += (params.members - 200) * 1500;
            if (params.transactions > 200) cost += (params.transactions - 200) * 50;
            if (params.storage > 1) cost += (params.storage - 1) * 50;
            cost += params.premiumFeatures * 200000;
            break;
    }
    
    return cost;
}
```

### Contoh Implementasi
```php
<?php
class TenantCostCalculator {
    public static function calculate($model, $params) {
        $baseCost = 0;
        
        switch($model) {
            case 'starter':
                $baseCost = 500000;
                if ($params['members'] > 100) {
                    $baseCost += ($params['members'] - 100) * 2000;
                }
                if ($params['transactions'] > 50) {
                    $baseCost += ($params['transactions'] - 50) * 100;
                }
                break;
                
            case 'professional':
                $baseCost = 1500000;
                if ($params['members'] > 500) {
                    $baseCost += ($params['members'] - 500) * 1500;
                }
                if ($params['transactions'] > 500) {
                    $baseCost += ($params['transactions'] - 500) * 80;
                }
                break;
                
            case 'enterprise':
                $baseCost = 3000000;
                // Unlimited
                break;
                
            case 'usage':
                $baseCost = 300000; // Biaya dasar
                $baseCost += $params['members'] * 2000;
                if ($params['transactions'] > 100) {
                    $baseCost += ($params['transactions'] - 100) * 100;
                }
                if ($params['storage'] > 1) {
                    $baseCost += ($params['storage'] - 1) * 100;
                }
                break;
                
            case 'hybrid':
                $baseCost = 1000000; // Biaya berlangganan
                if ($params['members'] > 200) {
                    $baseCost += ($params['members'] - 200) * 1500;
                }
                if ($params['transactions'] > 200) {
                    $baseCost += ($params['transactions'] - 200) * 50;
                }
                if ($params['storage'] > 1) {
                    $baseCost += ($params['storage'] - 1) * 50;
                }
                $baseCost += $params['premium_features'] * 200000;
                break;
        }
        
        return $baseCost;
    }
}
?>
```

## Cara Pembayaran

### Metode Pembayaran

#### 1. Transfer Bank
- **Bank BCA**: 123-456-7890 (PT. KSP LAM GABE JAYA)
- **Bank Mandiri**: 123-000-456-789 (PT. KSP LAM GABE JAYA)
- **Bank BNI**: 456-789-0123 (PT. KSP LAM GABE JAYA)

#### 2. Virtual Account
- **BCA Virtual Account**: 88608-1234567890
- **Mandiri Virtual Account**: 88608-1234567890
- **BNI Virtual Account**: 88608-1234567890

#### 3. E-Wallet
- **GoPay**: KSP-LAMGABEJAYA
- **OVO**: 0812-3456-7890
- **DANA**: 0812-3456-7890

#### 4. Kartu Kredit
- **Visa/Mastercard**: Bisa digunakan untuk pembayaran bulanan
- **Auto-debit**: Bisa diatur untuk pembayaran otomatis

#### 5. QRIS
- **QRIS Static**: Scan QR code di dashboard
- **QRIS Dynamic**: Generate QR code per transaksi

### Siklus Pembayaran

#### Pembayaran Bulanan
- **Tanggal Jatuh Tempo**: 1 setiap bulan
- **Grace Period**: 7 hari
- **Late Fee**: 2% per hari dari total tagihan
- **Suspend**: 14 hari setelah grace period

#### Pembayaran Tahunan (Diskon)
- **Pembayaran 1 Tahun**: Diskon 10%
- **Pembayaran 2 Tahun**: Diskon 15%
- **Pembayaran 3 Tahun**: Diskon 20%

### Proses Pembayaran

#### 1. Invoice Generation
- Invoice dibuat otomatis setiap tanggal 1
- Dikirim via email dan WhatsApp
- Tersedia di dashboard tenant

#### 2. Pembayaran
- Tenant memilih metode pembayaran
- Input nominal sesuai invoice
- Upload bukti pembayaran (jika manual)

#### 3. Verifikasi
- Sistem verifikasi pembayaran otomatis (untuk VA, e-wallet, kartu kredit)
- Verifikasi manual (untuk transfer bank)
- Status pembayaran update di dashboard

#### 4. Konfirmasi
- Email konfirmasi pembayaran
- Notifikasi WhatsApp
- Update status aktif tenant

## Dashboard Billing

### Fitur Dashboard Billing
- **Ringkasan Tagihan**: Total tagihan bulanan
- **Riwayat Pembayaran**: Histori pembayaran lengkap
- **Penggunaan**: Grafik penggunaan (anggota, transaksi, storage)
- **Prediksi Biaya**: Estimasi biaya bulan depan
- **Download Invoice**: Download PDF invoice
- **Metode Pembayaran**: Atur metode pembayaran default

### Monitoring Penggunaan
- **Real-time Usage**: Monitor penggunaan real-time
- **Alert**: Notifikasi jika mendekati limit
- **Usage Report**: Laporan penggunaan bulanan
- **Cost Analysis**: Analisis biaya per fitur

## Diskon dan Promo

### Diskon Early Adopter
- **3 Bulan Pertama**: Diskon 50%
- **6 Bulan Pertama**: Diskon 30%
- **12 Bulan Pertama**: Diskon 15%

### Diskon Volume
- **> 500 Anggota**: Diskon 10%
- **> 1000 Anggota**: Diskon 15%
- **> 2000 Anggota**: Diskon 20%

### Diskon Referral
- **Refer 1 Tenant**: Diskon 5% (1 bulan)
- **Refer 3 Tenant**: Diskon 10% (3 bulan)
- **Refer 5 Tenant**: Diskon 15% (6 bulan)

### Promo Khusus
- **Non-Profit**: Diskon 25%
- **Koperasi Wanita**: Diskon 10%
- **Koperasi Pedesaan**: Diskon 15%

## Upgrade dan Downgrade

### Prosedur Upgrade
1. **Request**: Tenant request upgrade via dashboard
2. **Review**: Admin review request
3. **Approval**: Persetujuan upgrade
4. **Implementation**: Implementasi upgrade
5. **Billing Update**: Update billing prorata

### Prosedur Downgrade
1. **Notice**: 30 hari notice period
2. **Data Export**: Export data tenant
3. **Implementation**: Implementasi downgrade
4. **Billing Update**: Update billing prorata

## SLA (Service Level Agreement)

### Uptime Guarantee
- **Starter**: 99.5% uptime
- **Professional**: 99.9% uptime
- **Enterprise**: 99.99% uptime

### Support Response Time
- **Starter**: 24 jam (email)
- **Professional**: 12 jam (email + WhatsApp)
- **Enterprise**: 4 jam (email + WhatsApp + phone)

### Data Backup
- **Starter**: Daily backup, 7 hari retention
- **Professional**: Hourly backup, 30 hari retention
- **Enterprise**: Real-time backup, 90 hari retention

## Contoh Kasus

### Kasus 1: Koperasi Kecil
```
Parameter:
- 75 anggota
- 30 transaksi/bulan
- 1 admin user
- 500 MB storage

Model yang Cocok: Starter
Biaya: Rp 500.000/bulan
```

### Kasus 2: Koperasi Menengah
```
Parameter:
- 300 anggota
- 400 transaksi/bulan
- 5 admin users
- 2 GB storage

Model yang Cocok: Professional
Biaya: Rp 1.500.000/bulan
```

### Kasus 3: Koperasi Besar
```
Parameter:
- 800 anggota
- 1000 transaksi/bulan
- 15 admin users
- 5 GB storage

Model yang Cocok: Enterprise
Biaya: Rp 3.000.000/bulan
```

### Kasus 4: Koperasi dengan Volume Tinggi
```
Parameter:
- 2000 anggota
- 2000 transaksi/bulan
- 10 admin users
- 10 GB storage

Model yang Cocok: Usage-Based
Biaya: 
- Biaya Dasar: Rp 300.000
- Anggota: 2000 × Rp 2.000 = Rp 4.000.000
- Transaksi: (2000-100) × Rp 100 = Rp 190.000
- Storage: (10-1) × Rp 100 = Rp 900
Total: Rp 4.490.900/bulan
```

## Biaya untuk High-Volume Usage

### Skenario High-Volume Rendering

Ketika tenant dan anggota aktif melakukan rendering data dalam volume sangat tinggi, perlu ada mekanisme biaya yang adil dan scalable. Berikut adalah skenario dan solusinya:

### 1. Model Biaya Tiered untuk High-Volume

#### Volume-Based Tiers
```
Tier 1 (Normal): 0-1.000 API calls/bulan
- Biaya: Rp 0.01/call setelah 1.000 calls pertama

Tier 2 (High): 1.001-10.000 API calls/bulan
- Biaya: Rp 0.008/call setelah 1.000 calls pertama

Tier 3 (Very High): 10.001-50.000 API calls/bulan
- Biaya: Rp 0.005/call setelah 1.000 calls pertama

Tier 4 (Ultra High): > 50.000 API calls/bulan
- Biaya: Rp 0.003/call setelah 1.000 calls pertama
```

#### Data Rendering Tiers
```
Tier 1 (Normal): 0-10 GB data transfer/bulan
- Biaya: Rp 100/GB setelah 1 GB pertama

Tier 2 (High): 10-50 GB data transfer/bulan
- Biaya: Rp 80/GB setelah 1 GB pertama

Tier 3 (Very High): 50-100 GB data transfer/bulan
- Biaya: Rp 60/GB setelah 1 GB pertama

Tier 4 (Ultra High): > 100 GB data transfer/bulan
- Biaya: Rp 40/GB setelah 1 GB pertama
```

### 2. Model Biaya Flat Rate untuk High-Volume

#### Unlimited Rendering Package
```
Package Name: "Unlimited Rendering"
Biaya: Rp 5.000.000/bulan
Fitur:
- Unlimited API calls
- Unlimited data transfer
- Unlimited rendering requests
- Priority processing
- Dedicated resources
- 99.99% uptime guarantee
```

#### High-Volume Package
```
Package Name: "High-Volume"
Biaya: Rp 2.500.000/bulan
Fitur:
- 50.000 API calls/bulan
- 50 GB data transfer/bulan
- 10.000 rendering requests/bulan
- Standard processing
- 99.9% uptime guarantee
```

### 3. Model Pay-As-You-Go (PAYG) untuk Rendering

#### Komponen Biaya PAYG
```
Base Fee: Rp 1.000.000/bulan

Additional Costs:
- API Calls: Rp 0.005/call (setelah 10.000 calls)
- Data Transfer: Rp 50/GB (setelah 10 GB)
- Rendering Requests: Rp 100/request (setelah 5.000 requests)
- CPU Time: Rp 0.001/second (setelah 1000 seconds)
- Memory Usage: Rp 0.0005/MB/second
```

### 4. Model Resource-Based Pricing

#### CPU Usage Pricing
```
Normal Usage: 0-100 CPU hours/bulan
- Included dalam biaya dasar

High Usage: 101-500 CPU hours/bulan
- Rp 10.000/CPU hour

Very High Usage: 501-2000 CPU hours/bulan
- Rp 8.000/CPU hour

Ultra High Usage: > 2000 CPU hours/bulan
- Rp 5.000/CPU hour
```

#### Memory Usage Pricing
```
Normal Usage: 0-50 GB-hours/bulan
- Included dalam biaya dasar

High Usage: 51-200 GB-hours/bulan
- Rp 500/GB-hour

Very High Usage: 201-500 GB-hours/bulan
- Rp 400/GB-hour

Ultra High Usage: > 500 GB-hours/bulan
- Rp 300/GB-hour
```

### 5. Model Concurrent User Pricing

#### Concurrent User Tiers
```
Tier 1: 1-50 concurrent users
- Included dalam biaya dasar

Tier 2: 51-200 concurrent users
- Rp 20.000/user/bulan

Tier 3: 201-500 concurrent users
- Rp 15.000/user/bulan

Tier 4: 501-1000 concurrent users
- Rp 10.000/user/bulan

Tier 5: > 1000 concurrent users
- Rp 5.000/user/bulan
```

### 6. Model Cache-Based Pricing

#### Cache Hit Ratio Pricing
```
Excellent Cache (>90% hit ratio):
- Diskon 20% dari biaya rendering

Good Cache (75-90% hit ratio):
- Diskon 10% dari biaya rendering

Fair Cache (50-75% hit ratio):
- Biaya normal

Poor Cache (<50% hit ratio):
- Biaya tambahan 10% untuk optimization
```

## Cara Menghitung Biaya High-Volume

### Step 1: Monitor Usage Metrics
- **API Calls**: Total API calls per bulan
- **Data Transfer**: Total data transfer (GB) per bulan
- **Rendering Requests**: Total rendering requests per bulan
- **CPU Usage**: Total CPU usage (hours) per bulan
- **Memory Usage**: Total memory usage (GB-hours) per bulan
- **Concurrent Users**: Peak concurrent users
- **Cache Hit Ratio**: Persentase cache hit

### Step 2: Identifikasi Pattern
- **Steady High Volume**: Volume tinggi stabil
- **Peak High Volume**: Volume tinggi hanya pada peak hours
- **Burst High Volume**: Volume tinggi sesekali
- **Growth Pattern**: Volume meningkat secara bertahap

### Step 3: Pilih Model yang Sesuai
- **Steady High Volume**: Unlimited Rendering Package
- **Peak High Volume**: High-Volume Package + Burst pricing
- **Burst High Volume**: PAYG dengan burst capacity
- **Growth Pattern**: Tiered pricing dengan auto-upgrade

### Step 4: Hitung Total Biaya
Gunakan formula berdasarkan model yang dipilih.

## Contoh Perhitungan High-Volume

### Kasus 1: Koperasi dengan Steady High Volume
```
Parameter:
- 75.000 API calls/bulan
- 80 GB data transfer/bulan
- 15.000 rendering requests/bulan
- 300 CPU hours/bulan
- 150 GB-hours memory usage/bulan
- 800 concurrent users

Model yang Cocok: Unlimited Rendering Package
Biaya: Rp 5.000.000/bulan

Perhitungan dengan model lain:
- Usage-Based: Rp 1.000.000 + (75.000-10.000)×Rp 0.005 + (80-10)×Rp 50 + (15.000-5.000)×Rp 100 = Rp 2.825.000
- Resource-Based: Rp 1.000.000 + (300-100)×Rp 10.000 + (150-50)×Rp 500 = Rp 3.050.000
- Concurrent User: Rp 1.000.000 + (800-50)×Rp 10.000 = Rp 8.500.000

Pilihan terbaik: Unlimited Rendering Package (Rp 5.000.000)
```

### Kasus 2: Koperasi dengan Peak High Volume
```
Parameter:
- 25.000 API calls/bulan (rata-rata)
- 100.000 API calls/bulan (peak)
- 30 GB data transfer/bulan (rata-rata)
- 120 GB data transfer/bulan (peak)
- 5.000 rendering requests/bulan (rata-rata)
- 25.000 rendering requests/bulan (peak)
- 200 concurrent users (rata-rata)
- 1.500 concurrent users (peak)

Model yang Cocok: High-Volume Package + Burst Pricing
Biaya: Rp 2.500.000 + Burst charges

Perhitungan Burst:
- API calls burst: (100.000-25.000)×Rp 0.005 = Rp 375.000
- Data transfer burst: (120-30)×Rp 50 = Rp 4.500
- Rendering burst: (25.000-5.000)×Rp 100 = Rp 2.000.000
- Concurrent user burst: (1.500-200)×Rp 5.000 = Rp 6.500.000

Total: Rp 2.500.000 + Rp 8.879.500 = Rp 11.379.500
```

### Kasus 3: Koperasi dengan Burst High Volume
```
Parameter:
- 5.000 API calls/bulan (rata-rata)
- 50.000 API calls/bulan (burst, 2 hari/bulan)
- 10 GB data transfer/bulan (rata-rata)
- 100 GB data transfer/bulan (burst, 2 hari/bulan)
- 1.000 rendering requests/bulan (rata-rata)
- 10.000 rendering requests/bulan (burst, 2 hari/bulan)
- 100 concurrent users (rata-rata)
- 800 concurrent users (burst, 2 hari/bulan)

Model yang Cocok: PAYG dengan Burst Capacity
Biaya: Rp 1.000.000 + Burst charges

Perhitungan Burst (hanya 2 hari):
- API calls burst: 45.000×Rp 0.005 = Rp 225.000
- Data transfer burst: 90×Rp 50 = Rp 4.500
- Rendering burst: 9.000×Rp 100 = Rp 900.000
- Concurrent user burst: 700×Rp 10.000 = Rp 7.000.000

Total: Rp 1.000.000 + Rp 8.129.500 = Rp 9.129.500
```

## Optimization Strategies untuk High-Volume

### 1. Caching Strategy
- **Client-Side Caching**: Cache data di client untuk mengurangi API calls
- **Server-Side Caching**: Cache frequently accessed data
- **CDN Integration**: Use CDN untuk static assets
- **Database Caching**: Cache query results

### 2. Data Compression
- **Response Compression**: Gzip compression untuk API responses
- **Image Optimization**: Compress images sebelum delivery
- **Data Minification**: Minimize data payload
- **Delta Updates**: Send only changed data

### 3. Load Balancing
- **Horizontal Scaling**: Add more servers for high load
- **Database Sharding**: Distribute database load
- **Queue System**: Use queue for heavy processing
- **Background Processing**: Process heavy tasks in background

### 4. Resource Optimization
- **Connection Pooling**: Reuse database connections
- **Memory Management**: Optimize memory usage
- **CPU Optimization**: Optimize CPU-intensive operations
- **Network Optimization**: Minimize network round trips

## Monitoring dan Alerting

### Metrics yang Dimonitor
- **API Response Time**: Response time per endpoint
- **Error Rate**: Percentage of failed requests
- **Throughput**: Requests per second
- **Resource Usage**: CPU, memory, disk usage
- **Cache Hit Ratio**: Cache effectiveness
- **Concurrent Users**: Number of active users

### Alert Thresholds
- **High Response Time**: > 2 seconds
- **High Error Rate**: > 5%
- **Low Cache Hit Ratio**: < 70%
- **High Resource Usage**: > 80%
- **High Concurrent Users**: > 90% capacity

## Auto-Scaling Configuration

### Scaling Triggers
- **CPU Usage**: Scale up when CPU > 80% for 5 minutes
- **Memory Usage**: Scale up when memory > 85% for 5 minutes
- **Response Time**: Scale up when response time > 2 seconds for 5 minutes
- **Queue Length**: Scale up when queue length > 1000

### Scaling Limits
- **Minimum Instances**: 2 instances
- **Maximum Instances**: 20 instances
- **Cooldown Period**: 10 minutes between scaling events
- **Scale Down Threshold**: CPU < 30% for 10 minutes

## Cost Optimization Tips

### 1. Efficient Data Fetching
- Use pagination for large datasets
- Implement lazy loading
- Use field selection to fetch only needed data
- Cache frequently accessed data

### 2. Optimize Rendering
- Use server-side rendering for static content
- Use client-side rendering for dynamic content
- Implement incremental updates
- Use web workers for heavy processing

### 3. Network Optimization
- Use HTTP/2 for multiplexing
- Implement request batching
- Use compression for large payloads
- Optimize image sizes and formats

### 4. Database Optimization
- Use proper indexing
- Implement query optimization
- Use read replicas for read-heavy operations
- Implement connection pooling

## Transparansi Penggunaan dan Monitoring

### Konsep Transparansi

Transparansi penggunaan aplikasi adalah kemampuan tenant (koperasi) dan user (anggota/staf) untuk melihat, memahami, dan mengontrol penggunaan sumber daya aplikasi secara real-time. Ini mencakup monitoring penggunaan API, data transfer, storage, CPU, memory, dan biaya yang terkait.

### Komponen Transparansi

#### 1. Dashboard Penggunaan Real-Time
- **Usage Overview**: Ringkasan penggunaan sumber daya
- **Cost Breakdown**: Detail biaya per komponen
- **Performance Metrics**: Metrik performa aplikasi
- **Resource Allocation**: Alokasi sumber daya per user/tenant
- **Historical Data**: Data historis penggunaan

#### 2. Monitoring API Usage
- **API Calls**: Total API calls per endpoint
- **Response Time**: Response time rata-rata per endpoint
- **Error Rate**: Persentase error per endpoint
- **Data Transfer**: Volume data transfer per endpoint
- **User Activity**: Aktivitas user per endpoint

#### 3. Resource Usage Tracking
- **CPU Usage**: Penggunaan CPU real-time
- **Memory Usage**: Penggunaan memory real-time
- **Storage Usage**: Penggunaan storage per tenant
- **Bandwidth Usage**: Penggunaan bandwidth real-time
- **Database Connections**: Jumlah koneksi database aktif

#### 4. Cost Transparency
- **Billing Breakdown**: Detail biaya per komponen
- **Cost Prediction**: Prediksi biaya bulan depan
- **Cost Optimization**: Saran optimasi biaya
- **Invoice Details**: Detail invoice dengan breakdown
- **Payment History**: Histori pembayaran

### Cara Mengetahui Penggunaan

#### 1. Dashboard Tenant Administrator

##### Main Dashboard
```
URL: /tenant/dashboard/admin/usage
Access: Tenant Administrator, Manager, Pengurus

Components:
- Usage Summary Card
- Real-time Metrics
- Cost Breakdown Chart
- Usage Trend Graph
- Alert Notifications
```

##### Usage Metrics Display
```php
// Example Dashboard Component
<div class="usage-dashboard">
    <div class="metric-card">
        <h3>API Calls</h3>
        <div class="metric-value" id="api-calls">15,234</div>
        <div class="metric-change">+12% from last month</div>
        <div class="metric-limit">Limit: 50,000</div>
    </div>
    
    <div class="metric-card">
        <h3>Data Transfer</h3>
        <div class="metric-value" id="data-transfer">45.2 GB</div>
        <div class="metric-change">+8% from last month</div>
        <div class="metric-limit">Limit: 50 GB</div>
    </div>
    
    <div class="metric-card">
        <h3>Active Users</h3>
        <div class="metric-value" id="active-users">234</div>
        <div class="metric-change">+5% from last month</div>
        <div class="metric-limit">Concurrent: 500</div>
    </div>
    
    <div class="metric-card">
        <h3>Monthly Cost</h3>
        <div class="metric-value" id="monthly-cost">Rp 2,345,000</div>
        <div class="metric-change">+3% from last month</div>
        <div class="metric-limit">Budget: Rp 3,000,000</div>
    </div>
</div>
```

#### 2. API Usage Monitoring

##### Endpoint Usage Details
```
URL: /tenant/admin/api-usage
Access: Tenant Administrator, IT Staff

Features:
- List of all API endpoints
- Usage statistics per endpoint
- Error rate per endpoint
- Response time distribution
- User activity per endpoint
```

##### API Usage Table
```html
<table class="api-usage-table">
    <thead>
        <tr>
            <th>Endpoint</th>
            <th>Calls</th>
            <th>Avg Response</th>
            <th>Error Rate</th>
            <th>Data Transfer</th>
            <th>Cost</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>/api/v1/loans</td>
            <td>5,234</td>
            <td>245ms</td>
            <td>2.1%</td>
            <td>12.3 MB</td>
            <td>Rp 52,340</td>
        </tr>
        <tr>
            <td>/api/v1/members</td>
            <td>3,456</td>
            <td>189ms</td>
            <td>1.2%</td>
            <td>8.7 MB</td>
            <td>Rp 34,560</td>
        </tr>
        <tr>
            <td>/api/v1/transactions</td>
            <td>2,789</td>
            <td>156ms</td>
            <td>0.8%</td>
            <td>6.2 MB</td>
            <td>Rp 27,890</td>
        </tr>
    </tbody>
</table>
```

#### 3. User Activity Tracking

##### User Activity Dashboard
```
URL: /tenant/admin/user-activity
Access: Tenant Administrator, Manager

Features:
- List of all active users
- Activity per user
- Resource usage per user
- Login history
- Session duration
```

##### User Activity Report
```php
// User Activity Data Structure
[
    'user_id' => 'USR001',
    'name' => 'Budi Santoso',
    'role' => 'AO',
    'last_login' => '2026-02-22 09:15:00',
    'session_duration' => '4h 23m',
    'api_calls' => 156,
    'data_transfer' => '2.3 MB',
    'cost' => 'Rp 1,560',
    'active_endpoints' => [
        '/api/v1/loans',
        '/api/v1/members',
        '/api/v1/surveys'
    ]
]
```

#### 4. Cost Breakdown Transparency

##### Cost Dashboard
```
URL: /tenant/admin/cost-breakdown
Access: Tenant Administrator, Manager, Pengurus

Features:
- Detailed cost breakdown
- Cost per component
- Cost per user
- Cost prediction
- Cost optimization suggestions
```

##### Cost Breakdown Chart
```javascript
// Cost Breakdown Visualization
const costData = {
    'api_calls': 450000,
    'data_transfer': 320000,
    'storage': 150000,
    'cpu_usage': 280000,
    'memory_usage': 180000,
    'concurrent_users': 420000,
    'base_fee': 1000000
};

// Render as pie chart or bar chart
```

#### 5. Real-time Monitoring

##### WebSocket Real-time Updates
```javascript
// Real-time Usage Updates
const ws = new WebSocket('wss://api.ksp-lamgabejaya.id/usage');

ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    updateUsageDashboard(data);
};

// Data Structure
{
    'timestamp': '2026-02-22T10:30:00Z',
    'api_calls': 15234,
    'data_transfer': 45.2,
    'active_users': 234,
    'cpu_usage': 67.5,
    'memory_usage': 78.2,
    'cost_estimate': 2345000
}
```

### Implementasi Teknis

#### 1. Database Schema untuk Tracking

##### Usage Logs Table
```sql
CREATE TABLE usage_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    response_time INT NOT NULL,
    status_code INT NOT NULL,
    data_transfer BIGINT NOT NULL,
    cpu_usage DECIMAL(5,2),
    memory_usage DECIMAL(5,2),
    cost DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_created (tenant_id, created_at),
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_endpoint_created (endpoint, created_at)
);
```

##### User Sessions Table
```sql
CREATE TABLE user_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration INT,
    api_calls INT DEFAULT 0,
    data_transfer BIGINT DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0,
    INDEX idx_tenant_user (tenant_id, user_id),
    INDEX idx_session (session_id)
);
```

#### 2. Middleware untuk Tracking

##### Usage Tracking Middleware
```php
<?php
class UsageTrackingMiddleware
{
    public function handle($request, $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $usageData = [
            'tenant_id' => $this->getTenantId(),
            'user_id' => $this->getUserId(),
            'endpoint' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'response_time' => ($endTime - $startTime) * 1000,
            'status_code' => $response->getStatusCode(),
            'data_transfer' => strlen($response->getContent()),
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => ($endMemory - $startMemory) / 1024 / 1024,
            'cost' => $this->calculateCost($request, $response),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->logUsage($usageData);
        
        return $response;
    }
    
    private function calculateCost($request, $response)
    {
        // Cost calculation logic
        $baseCost = 0.01; // Base cost per request
        $dataCost = strlen($response->getContent()) * 0.0001; // Cost per byte
        $cpuCost = $this->getCpuUsage() * 0.001; // Cost per CPU percentage
        
        return $baseCost + $dataCost + $cpuCost;
    }
}
?>
```

#### 3. API Endpoints untuk Transparency

##### Get Usage Summary
```php
// GET /api/v1/tenant/usage/summary
{
    "period": "current_month",
    "api_calls": {
        "total": 15234,
        "limit": 50000,
        "percentage": 30.5,
        "cost": 152340
    },
    "data_transfer": {
        "total": 45.2,
        "unit": "GB",
        "limit": 50,
        "percentage": 90.4,
        "cost": 452000
    },
    "active_users": {
        "total": 234,
        "concurrent_peak": 456,
        "limit": 500,
        "percentage": 91.2,
        "cost": 468000
    },
    "total_cost": {
        "current": 2345000,
        "budget": 3000000,
        "percentage": 78.2,
        "prediction": 2450000
    }
}
```

##### Get Detailed Usage
```php
// GET /api/v1/tenant/usage/detailed?period=month&start=2026-02-01&end=2026-02-28
{
    "period": {
        "start": "2026-02-01",
        "end": "2026-02-28"
    },
    "daily_usage": [
        {
            "date": "2026-02-01",
            "api_calls": 523,
            "data_transfer": 1.2,
            "active_users": 45,
            "cost": 52300
        },
        // ... more days
    ],
    "endpoint_breakdown": [
        {
            "endpoint": "/api/v1/loans",
            "calls": 5234,
            "avg_response_time": 245,
            "error_rate": 2.1,
            "cost": 52340
        },
        // ... more endpoints
    ],
    "user_breakdown": [
        {
            "user_id": "USR001",
            "name": "Budi Santoso",
            "role": "AO",
            "calls": 156,
            "cost": 1560
        },
        // ... more users
    ]
}
```

### Dashboard User untuk Anggota

#### 1. Personal Usage Dashboard
```
URL: /member/dashboard/usage
Access: All Members

Features:
- Personal API usage
- Personal data transfer
- Personal cost contribution
- Activity history
- Usage tips
```

#### 2. Usage Report untuk Anggota
```html
<div class="member-usage-dashboard">
    <div class="usage-card">
        <h3>API Usage This Month</h3>
        <div class="usage-value">234 calls</div>
        <div class="usage-detail">Most used: Dashboard (89 calls)</div>
    </div>
    
    <div class="usage-card">
        <h3>Data Transfer</h3>
        <div class="usage-value">2.3 MB</div>
        <div class="usage-detail">Most data: Reports (1.2 MB)</div>
    </div>
    
    <div class="usage-card">
        <h3>Login Activity</h3>
        <div class="usage-value">45 sessions</div>
        <div class="usage-detail">Avg duration: 23 minutes</div>
    </div>
    
    <div class="usage-card">
        <h3>Cost Contribution</h3>
        <div class="usage-value">Rp 2,340</div>
        <div class="usage-detail">2.3% of total tenant cost</div>
    </div>
</div>
```

### Alert dan Notification

#### 1. Usage Alerts
```php
// Alert Configuration
$usageAlerts = [
    'api_calls_limit' => [
        'threshold' => 80, // 80% of limit
        'message' => 'API calls approaching limit',
        'action' => 'notify_admin'
    ],
    'cost_budget' => [
        'threshold' => 90, // 90% of budget
        'message' => 'Monthly cost approaching budget',
        'action' => 'notify_manager'
    ],
    'performance_degradation' => [
        'threshold' => 2000, // 2 seconds response time
        'message' => 'Performance degradation detected',
        'action' => 'notify_it_staff'
    ]
];
```

#### 2. Notification Channels
- **Email**: Daily/weekly usage reports
- **WhatsApp**: Critical alerts
- **Push Notifications**: Real-time alerts
- **SMS**: Emergency alerts
- **In-App**: Dashboard notifications

### Privacy dan Security

#### 1. Data Privacy
- **User Consent**: Explicit consent untuk tracking
- **Data Anonymization**: Anonymize sensitive data
- **Data Retention**: Hapus data lama sesuai kebijakan
- **Access Control**: Akses terbatas per role

#### 2. Security Measures
- **Encryption**: Encrypt usage data
- **Audit Trail**: Log semua akses ke usage data
- **Rate Limiting**: Limit API calls untuk monitoring
- **Authentication**: Secure authentication untuk dashboard

### Best Practices

#### 1. Untuk Tenant Administrator
- **Regular Monitoring**: Monitor usage harian
- **Set Alerts**: Konfigurasi alerts untuk threshold
- **Cost Optimization**: Optimasi penggunaan untuk hemat biaya
- **User Training**: Train users untuk efisiensi

#### 2. Untuk Developer
- **Efficient APIs**: Design APIs yang efisien
- **Caching**: Implement caching untuk mengurangi calls
- **Pagination**: Gunakan pagination untuk data besar
- **Compression**: Compress responses

#### 3. Untuk User
- **Efficient Usage**: Gunakan aplikasi secara efisien
- **Logout**: Logout saat tidak digunakan
- **Cache**: Manfaatkan cache browser
- **Report Issues**: Laporkan masalah performa

### FAQ Transparansi Penggunaan

**Q: Bagaimana cara melihat penggunaan API?**
A: Login ke dashboard tenant, buka menu "Usage Monitoring", pilih "API Usage".

**Q: Apakah data penggunaan privasi?**
A: Ya, data penggunaan dienkripsi dan hanya dapat diakses oleh authorized users.

**Q: Bagaimana cara mengurangi biaya penggunaan?**
A: Gunakan caching, optimasi API calls, dan ikuti tips optimasi di dashboard.

**Q: Berapa lama data penggunaan disimpan?**
A: Data penggunaan disimpan selama 13 bulan sesuai kebijakan privasi.

**Q: Apakah bisa export data penggunaan?**
A: Ya, bisa export data penggunaan ke CSV atau PDF dari dashboard.

**Q: Bagaimana jika ada error dalam tracking?**
A: Laporkan ke support dan sistem akan melakukan investigasi.

**Q: Apakah ada biaya untuk monitoring?**
A: Tidak, monitoring dan transparansi adalah fitur standar.

---

## FAQ High-Volume Billing

**Q: Bagaimana jika tenant melebihi limit API calls?**
A: Sistem akan otomatis menambah kapasitas dengan burst pricing atau menunggu hingga periode berikutnya.

**Q: Apakah ada biaya untuk concurrent users?**
A: Ya, concurrent users dikenakan biaya berdasarkan tier yang digunakan.

**Q: Bagaimana cara mengurangi biaya high-volume?**
A: Implement caching strategies, optimize data fetching, dan gunakan efficient rendering techniques.

**Q: Apakah ada diskon untuk long-term commitment?**
A: Ya, ada diskon 10% untuk kontrak tahunan dan 20% untuk kontrak 3 tahun.

**Q: Bagaimana monitoring usage real-time?**
A: Dashboard billing menampilkan real-time usage metrics dan alert jika mendekati limit.

**Q: Apakah bisa custom package untuk high-volume?**
A: Ya, bisa custom package dengan minimum commitment 6 bulan.

---

*Versi Dokumen: 1.2*
*Terakhir Diperbarui: 22 Februari 2026*
