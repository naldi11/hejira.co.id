# SISTEM MANAJEMEN BISNIS TERPADU
## Gudang Tempua | Jihan's Food | Hendhys Brownies

---

## APA YANG DIBANGUN

Sebuah **sistem manajemen bisnis multi-entitas berbasis web** yang menghubungkan tiga entitas bisnis dalam satu platform terpadu, dengan data realtime dan kontrol akses ketat per role.

---

## TIGA ENTITAS BISNIS

### 1. Gudang Tempua (Koperasi)
Pusat kendali inventory. Bertanggung jawab atas semua pengadaan bahan baku dan frozen food dari supplier, menyimpan stok, lalu mendistribusikan ke Jihan's Food dan Hendhys Brownies berdasarkan request yang diajukan.

### 2. Jihan's Food
Pabrik produksi Tortilla sekaligus retail. Di sisi pabrik mencatat produksi Tortilla kecil/sedang/besar setiap hari. Di sisi retail menjalankan POS untuk transaksi langsung ke pelanggan (retail) maupun agen (B2B). Stok diperoleh dari Gudang Tempua via sistem request-approval.

### 3. Hendhys Brownies
Bakery dengan satu pusat dan beberapa cabang dinamis. Pusat melakukan produksi (Bolu, Blackforest, Roti, Brownies, dll), mendistribusikan stok ke cabang, dan menerima return produk cacat. Setiap cabang punya POS dan stok sendiri. Bahan baku diperoleh dari Gudang Tempua.

---

## STRUKTUR ROLE & AKSES

| Role | Entitas | Akses |
|---|---|---|
| **Owner** | Semua | Read-only monitoring, semua dashboard & laporan |
| **Admin Gudang** | Gudang | Full access gudang + kelola semua user |
| **Kasir Jihan's** | Jihan's | POS retail, input produksi, request ke gudang |
| **Admin Jihan's** | Jihan's | Sama dengan Kasir Jihan's *(dormant, aktif jika dibutuhkan)* |
| **Kasir Hendhys** | Hendhys | POS, produksi, request ke gudang/pusat — dibedakan via branch |

---

## ALUR BISNIS UTAMA

```
SUPPLIER
   │
   │ Purchase Order + Penerimaan Barang
   ▼
GUDANG TEMPUA
   │
   ├─── Request Approval ◄── JIHAN'S FOOD
   │         │
   │         └── Transfer Keluar ──► Stok Jihan's ──► POS Retail/B2B
   │                                      │
   │                                      └── Produksi Tortilla (S/M/L)
   │
   └─── Request Approval ◄── HENDHYS PUSAT
             │
             └── Transfer Keluar ──► Stok Pusat ──► Produksi Bakery
                                          │
                                          └── Transfer ke Cabang
                                                    │
                                                    ├── POS Cabang
                                                    └── Return Produk Cacat ──► Pusat
```

---

## FITUR UTAMA PER MODUL

### Gudang Tempua
- Purchase Order ke supplier (Draft → Sent → Received)
- Penerimaan barang / GRN
- Manajemen stok gudang realtime
- Approval transfer request dari Jihan's & Hendhys
- Transfer keluar barang ke entitas lain
- Manajemen user (semua role)

### Jihan's Food
- Input produksi Tortilla harian (kecil/sedang/besar)
- Laporan produksi: Harian, Mingguan, Bulanan, Tahunan, Keseluruhan
- POS kasir: Retail & B2B/Agen
- Transaksi pending (hold & lanjut)
- Request bahan baku ke Gudang Tempua
- Invoice / faktur cetak siap print

### Hendhys Brownies
- Input produksi di pusat (multi-produk per sesi)
- Distribusi stok ke cabang
- Request produk dari cabang ke pusat
- Return produk cacat dari cabang
- POS di pusat dan semua cabang
- Transaksi pending per cabang
- Stok per cabang realtime

### Owner Dashboard
- Dashboard konsolidasi semua entitas
- Dashboard detail per entitas
- Semua laporan & log aktivitas
- Tanpa bisa intervensi (read-only)

### Fitur Global
- Notifikasi realtime (request masuk, approval, stok rendah)
- Log aktivitas lengkap semua user
- Laporan export ke Excel/CSV/PDF
- Invoice & faktur siap cetak dengan preview
- Master data: Supplier, Customer, Produk, Satuan, Brand, Kategori, Cabang

---

## TECH STACK

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13 (PHP 8.4+) |
| Frontend | Blade + Livewire 3 + Alpine.js |
| CSS Framework | Tailwind CSS |
| Database | MySQL 8.0 — Single DB, prefix-based |
| Realtime | Laravel Reverb (WebSocket) + Laravel Echo |
| Auth & Permission | Laravel Breeze + Spatie Laravel Permission |
| Export | maatwebsite/excel + barryvdh/laravel-dompdf |
| Queue | Laravel Queue (database driver) |
| IDE | VSCode (Antigravity) |
| Tools | Claude Code + Gemini CLI |

---

## SETUP PROJECT (Jalankan di Local)

### 1. Buat Project Laravel
```bash
composer create-project laravel/laravel project-ketua
cd project-ketua
```

### 2. Install Package Wajib
```bash
# Auth starter
composer require laravel/breeze --dev
php artisan breeze:install blade

# Role & Permission
composer require spatie/laravel-permission

# Export Excel & PDF
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf

# Realtime WebSocket
composer require laravel/reverb
php artisan reverb:install

# Frontend
npm install
npm run build
```

### 3. Publish Config
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

### 4. Setup .env
```env
APP_NAME="Sistem Manajemen Bisnis Terpadu"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_project_ketua
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=bisnis_app
REVERB_APP_KEY=bisnis_key_secret
REVERB_APP_SECRET=bisnis_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 5. Jalankan Migration & Seeder
```bash
php artisan migrate
php artisan db:seed
```

### 6. Jalankan Development Server
```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite (frontend hot reload)
npm run dev

# Terminal 3 — Reverb (WebSocket)
php artisan reverb:start

# Terminal 4 — Queue Worker (notifikasi)
php artisan queue:work
```

---

## STRUKTUR FOLDER PROJECT

```
bisnis-terpadu/
│
├── app/
│   ├── Console/
│   ├── Events/                          ← Realtime events
│   │   ├── TransferRequestCreated.php
│   │   ├── TransferRequestApproved.php
│   │   └── BranchRequestCreated.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── Master/                  ← CRUD master data
│   │   │   ├── Gudang/                  ← Modul gudang
│   │   │   ├── Jihans/                  ← Modul jihan's
│   │   │   ├── Hendhys/                 ← Modul hendhys
│   │   │   ├── Owner/                   ← Dashboard owner
│   │   │   └── Api/                     ← Internal API (search produk, dll)
│   │   │
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php
│   │   │   ├── CheckEntity.php
│   │   │   └── CheckBranch.php
│   │   │
│   │   └── Requests/                    ← Form validation per modul
│   │
│   ├── Models/
│   │   ├── Master/
│   │   │   ├── User.php
│   │   │   ├── Role.php
│   │   │   ├── Branch.php
│   │   │   ├── Supplier.php
│   │   │   ├── Customer.php
│   │   │   ├── Product.php
│   │   │   ├── ProductCategory.php
│   │   │   ├── Unit.php
│   │   │   ├── Brand.php
│   │   │   ├── ActivityLog.php
│   │   │   └── Notification.php
│   │   │
│   │   ├── Gudang/
│   │   │   ├── Stock.php
│   │   │   ├── PurchaseOrder.php
│   │   │   ├── PoDetail.php
│   │   │   ├── Receiving.php
│   │   │   ├── ReceivingDetail.php
│   │   │   ├── TransferRequest.php
│   │   │   ├── TransferRequestDetail.php
│   │   │   ├── TransferOut.php
│   │   │   └── TransferOutDetail.php
│   │   │
│   │   ├── Jihans/
│   │   │   ├── Stock.php
│   │   │   ├── StockMovement.php
│   │   │   ├── StockIn.php
│   │   │   ├── Production.php
│   │   │   ├── Transaction.php
│   │   │   ├── TransactionDetail.php
│   │   │   ├── TransactionPayment.php
│   │   │   ├── PendingTransaction.php
│   │   │   └── PendingDetail.php
│   │   │
│   │   └── Hendhys/
│   │       ├── StockPusat.php
│   │       ├── StockBranch.php
│   │       ├── StockMovement.php
│   │       ├── StockIn.php
│   │       ├── Production.php
│   │       ├── ProductionDetail.php
│   │       ├── BranchRequest.php
│   │       ├── BranchRequestDetail.php
│   │       ├── TransferToBranch.php
│   │       ├── TransferToBranchDetail.php
│   │       ├── ReturnFromBranch.php
│   │       ├── ReturnDetail.php
│   │       ├── Transaction.php
│   │       ├── TransactionDetail.php
│   │       ├── TransactionPayment.php
│   │       ├── PendingTransaction.php
│   │       └── PendingDetail.php
│   │
│   ├── Services/                        ← Business logic terpisah dari controller
│   │   ├── NumberGeneratorService.php   ← Auto-generate nomor dokumen
│   │   ├── StockService.php             ← Update stok terpusat
│   │   ├── NotificationService.php      ← Kirim notifikasi
│   │   ├── InvoiceService.php           ← Generate invoice PDF
│   │   └── ExportService.php            ← Export Excel/CSV
│   │
│   └── Observers/                       ← Auto log activity
│       └── ActivityLogObserver.php
│
├── database/
│   ├── migrations/
│   │   ├── master/
│   │   ├── gudang/
│   │   ├── jihans/
│   │   └── hendhys/
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RolePermissionSeeder.php
│       ├── BranchSeeder.php
│       ├── UnitSeeder.php
│       └── UserSeeder.php               ← User default per role
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php            ← Layout utama
│   │   │   ├── gudang.blade.php
│   │   │   ├── jihans.blade.php
│   │   │   ├── hendhys.blade.php
│   │   │   └── owner.blade.php
│   │   │
│   │   ├── master/                      ← CRUD master data
│   │   ├── gudang/                      ← Views modul gudang
│   │   ├── jihans/                      ← Views modul jihan's
│   │   ├── hendhys/                     ← Views modul hendhys
│   │   ├── owner/                       ← Dashboard owner
│   │   └── components/                  ← Blade components reusable
│   │
│   └── js/
│       ├── app.js
│       └── echo.js                      ← Laravel Echo setup
│
└── routes/
    ├── web.php                          ← Route utama
    ├── gudang.php                       ← Route khusus gudang
    ├── jihans.php                       ← Route khusus jihan's
    ├── hendhys.php                      ← Route khusus hendhys
    └── owner.php                        ← Route khusus owner
```

---

## URUTAN BUILD (Rekomendasi)

```
FASE 1 — FONDASI
├── Setup project & install packages
├── Konfigurasi database & .env
├── Buat semua migration (48 tabel)
├── Buat semua model dengan relasi
├── Setup role & permission (Spatie)
└── Auth system + middleware

FASE 2 — MASTER DATA
├── CRUD Supplier
├── CRUD Customer
├── CRUD Produk
├── CRUD Satuan, Brand, Kategori
└── CRUD Branch (Hendhys)

FASE 3 — GUDANG
├── Purchase Order
├── Penerimaan Barang (GRN)
├── Stok Gudang
├── Transfer Request (approval flow)
└── Transfer Keluar

FASE 4 — JIHAN'S
├── Stok Jihan's
├── Input Produksi Tortilla
├── POS Kasir
├── Transaksi Pending
└── Laporan Produksi

FASE 5 — HENDHYS
├── Stok Pusat & Cabang
├── Input Produksi
├── Branch Request & Distribusi
├── Return dari Cabang
└── POS Pusat & Cabang

FASE 6 — OWNER DASHBOARD
├── Dashboard konsolidasi
├── Dashboard per entitas
└── Semua laporan

FASE 7 — FINISHING
├── Notifikasi realtime (Reverb)
├── Invoice & faktur PDF
├── Export Excel/CSV
└── Audit log & activity log
```

---
