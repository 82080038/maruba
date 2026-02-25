# 📱 Mobile Navigation Guide - KSP LGJ

## 🎯 Jawaban Langsung: Menu Pengganti Sidebar di HP

### **🍔 Mobile Sidebar Toggle (Pengganti Utama)**
```html
<button id="mobileSidebarToggle" class="btn d-block d-lg-none">
    <!-- SVG Hamburger Icon -->
    <svg width="20" height="20" viewBox="0 0 16 16">
        <path d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"></path>
    </svg>
</button>
```

**Lokasi:** Header kiri atas (sebelum brand "KSP LGJ")
**Fungsi:** Membuka sidebar yang sama dengan desktop, tapi dalam mode slide-in

---

## 📊 Sistem Navigasi Lengkap

### **🖥️ Desktop Mode (≥992px)**
```
┌─────────────────────────────────────────────────────────┐
│ [SIDEBAR]                    [HEADER - USER MENU]      │
│ 📊 Dashboard                 👤 User Name ▼            │
│ 💰 Pinjaman                  └── Logout                 │
│ 👥 Anggota                                             │
│ 📦 Produk                                               │
│ ...                                                    │
└─────────────────────────────────────────────────────────┘
```

### **📱 Mobile Mode (<992px)**
```
┌─────────────────────────────────────────────────────────┐
│ [☰] KSP LGJ                          [👤 User ▼] [☰]   │
│                                                         │
│ ┌─────────────────────────────────────────────────────┐ │
│ │           MAIN CONTENT AREA                         │ │
│ │                                                     │ │
│ │  (Sidebar disembunyakan, muncul saat ☰ diklik)      │ │
│ │                                                     │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

---

## 🧩 Komponen Mobile Navigation

### **1. 🍔 Mobile Sidebar Toggle**
```html
<button id="mobileSidebarToggle">
    <svg>...</svg> <!-- Hamburger icon -->
</button>
```
- **Visible:** Hanya di mobile (<992px)
- **Position:** Header kiri
- **Action:** Buka/tutup sidebar slide-in

### **2. 📱 Mobile Sidebar (Slide-in)**
```html
<div id="mainSidebar" class="sidebar">
    <!-- SISI MENU SAMA DENGAN DESKTOP -->
    <a href="/dashboard">📊 Dashboard</a>
    <a href="/loans">💰 Pinjaman</a>
    <a href="/members">👥 Anggota</a>
    <!-- ... semua menu lainnya ... -->
</div>
```
- **Behavior:** Slide dari kiri saat toggle diklik
- **Content:** Sama persis dengan desktop sidebar
- **Backdrop:** Overlay gelap saat terbuka

### **3. 👤 Header User Menu**
```html
<button id="mobileMenuToggle">
    <span class="navbar-toggler-icon"></span>
</button>
```
- **Visible:** Hanya di mobile
- **Position:** Header kanan
- **Action:** Buka dropdown user menu (Logout)

---

## 🔄 Alur Mobile Navigation

### **Step 1: Buka Sidebar**
```
User klik ☰ → Sidebar slide-in → Menu muncul
```

### **Step 2: Pilih Menu**
```
User klik menu item → Sidebar close → Navigasi ke halaman
```

### **Step 3: Tutup Sidebar**
```
- Klik backdrop → Sidebar close
- Klik outside → Sidebar close
- Klik menu → Sidebar close otomatis
```

---

## 📍 Lokasi File & Kode

### **File Utama:** `App/src/Views/layout_admin.php`

#### **Mobile Toggle Button (Baris 426-431):**
```php
<button class="btn me-2 d-block d-lg-none" id="mobileSidebarToggle">
    <svg width="20" height="20" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"></path>
    </svg>
</button>
```

#### **Sidebar Container (Baris 334-...):**
```php
<div class="sidebar d-flex flex-column p-3 text-white" id="mainSidebar">
    <!-- Menu items sama dengan desktop -->
    <ul class="nav nav-pills flex-column mb-auto" id="mainNavigation">
        <li><a href="/dashboard">📊 Dashboard</a></li>
        <li><a href="/loans">💰 Pinjaman</a></li>
        <!-- ... -->
    </ul>
</div>
```

---

## 🎨 Visual Guide

### **Mobile Header Layout:**
```
┌─────────────────────────────────────────────────────┐
│ [☰] 🏢 KSP LGJ                    [👤 User] [☰]    │
│ 1   2        3                    4       5        │
│                                                         │
│ 1 = Mobile Sidebar Toggle (PENGANTI SIDEBAR)          │
│ 2 = Brand Logo                                         │
│ 3 = Header Brand                                       │
│ 4 = User Dropdown                                      │
│ 5 = Header Nav Toggle                                  │
└─────────────────────────────────────────────────────┘
```

### **Mobile Sidebar (saat terbuka):**
```
┌─────────────┐  ┌─────────────────────────────────────┐
│ 📊 Dashboard│  │  MAIN CONTENT (dibelakang sidebar)   │
│ 💰 Pinjaman │  │                                     │
│ 👥 Anggota  │  │  Content sedang ditampilkan...        │
│ 📦 Produk   │  │                                     │
│ 📋 Survei   │  │                                     │
│ 💳 Angsuran │  │                                     │
│ 📈 Laporan  │  │                                     │
│ 👤 Pengguna  │  │                                     │
│ 🕒 Audit    │  │                                     │
│ 📄 Surat    │  │                                     │
│ 🚪 Logout   │  │                                     │
└─────────────┘  └─────────────────────────────────────┘
```

---

## ✅ Jawaban Singkat

**Menu pengganti sidebar di HP adalah:**

### **🍔 Tombol Hamburger (☰)**
- **ID:** `mobileSidebarToggle`
- **Lokasi:** Header kiri atas
- **Icon:** SVG 3 garis horizontal
- **Fungsi:** Membuka sidebar slide-in dengan menu lengkap

**Isinya sama persis dengan desktop sidebar:**
- Dashboard, Pinjaman, Anggota, Produk, dll
- Sistem permission yang sama
- Icons dan styling yang sama
- Hanya berbeda cara tampil (slide-in vs fixed)

---

## 🧪 Testing

### **URL Test:** `http://localhost/maruba/test_mobile.html`
### **Resize browser** ke <992px untuk melihat:
- ✅ Tombol ☰ muncul di header kiri
- ✅ Sidebar tersembunyi default
- ✅ Klik ☰ → sidebar slide-in
- ✅ Klik menu → sidebar close

**Status:** 🟢 Mobile navigation siap digunakan!
