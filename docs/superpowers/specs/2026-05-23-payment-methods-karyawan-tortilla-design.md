# Design Spec: Master Metode Pembayaran, Karyawan & Produksi Tortilla

**Tanggal:** 2026-05-23  
**Scope:** Jihan's Food + Hendhys Brownies  
**Status:** Approved

---

## 1. Ringkasan

Tiga fitur baru yang saling terhubung:

1. **Master Metode Pembayaran** — daftar metode pembayaran (cash, transfer, QRIS, dll) per entitas, dipakai di POS kasir Jihan's dan Hendhys menggantikan enum hardcode `cash|transfer`.
2. **Master Karyawan** — daftar karyawan Jihan's, dipakai di modul produksi tortilla.
3. **Produksi Tortilla Karyawan (Jihan's)** — modul terpisah untuk mencatat output produksi per karyawan (TB, TS, TK, TC, KRIBAB), dengan rekap mingguan untuk penggajian.

---

## 2. Schema Database

### 2.1 `master_payment_methods`

```sql
id                BIGINT AUTO_INCREMENT PK
entity_scope      ENUM('gudang','jihans','hendhys','all') NOT NULL
name              VARCHAR(100) NOT NULL          -- "BCA - Jihan's Food", "Tunai", "QRIS Hendhys"
bank_name         VARCHAR(100) NULLABLE          -- "BCA", "Mandiri", null untuk cash
account_number    VARCHAR(50)  NULLABLE
account_name      VARCHAR(100) NULLABLE
image             VARCHAR(255) NULLABLE          -- path ke QR code / logo bank
is_active         BOOLEAN DEFAULT true
deleted_at        TIMESTAMP NULLABLE (softDeletes)
created_at, updated_at
```

> "Tunai/Cash" juga masuk sebagai baris di tabel ini (bank_name, account_number = null).  
> entity_scope `gudang` tidak dipakai untuk fitur ini, tapi kolom tetap mengikuti pola arsitektur.

---

### 2.2 `master_karyawan`

```sql
id                BIGINT AUTO_INCREMENT PK
entity_scope      ENUM('gudang','jihans','hendhys','all') DEFAULT 'jihans'
name              VARCHAR(150) NOT NULL
phone             VARCHAR(20)  NULLABLE
address           TEXT         NULLABLE
is_active         BOOLEAN DEFAULT true
deleted_at        TIMESTAMP NULLABLE (softDeletes)
created_at, updated_at
```

---

### 2.3 `master_production_rates`

```sql
id                BIGINT AUTO_INCREMENT PK
entity_scope      ENUM('gudang','jihans','hendhys','all') DEFAULT 'jihans'
tb_rate           DECIMAL(15,2) NOT NULL DEFAULT 0   -- Rp per pcs Tortilla Besar
ts_rate           DECIMAL(15,2) NOT NULL DEFAULT 0   -- Rp per pcs Tortilla Sedang
tk_rate           DECIMAL(15,2) NOT NULL DEFAULT 0   -- Rp per pcs Tortilla Kecil
tc_rate           DECIMAL(15,2) NOT NULL DEFAULT 0   -- Rp per pcs TC
kribab_rate       DECIMAL(15,2) NOT NULL DEFAULT 0   -- Rp per pcs KRIBAB
notes             TEXT NULLABLE
updated_by        BIGINT FK → master_users
created_at, updated_at
```

> Satu baris aktif per entity_scope. Tidak softDelete — ini setting, bukan transaksional.  
> Seed awal: satu baris `entity_scope = 'jihans'` dengan semua rate = 0.  
> Controller menggunakan `updateOrCreate(['entity_scope' => 'jihans'], $data)` — tidak perlu cek apakah baris sudah ada.

---

### 2.4 `jihans_tortilla_sessions`

```sql
id                BIGINT AUTO_INCREMENT PK
session_number    VARCHAR(30) UNIQUE              -- auto-generate: JTS-YYYY-XXXX
date              DATE NOT NULL
notes             TEXT NULLABLE
created_by        BIGINT FK → master_users
created_at, updated_at

INDEX(date)
```

---

### 2.5 `jihans_tortilla_session_details`

```sql
id                BIGINT AUTO_INCREMENT PK
session_id        BIGINT FK → jihans_tortilla_sessions (cascadeOnDelete)
karyawan_id       BIGINT FK → master_karyawan
tb_qty            INT NOT NULL DEFAULT 0
ts_qty            INT NOT NULL DEFAULT 0
tk_qty            INT NOT NULL DEFAULT 0
tc_qty            INT NOT NULL DEFAULT 0
kribab_qty        INT NOT NULL DEFAULT 0
tb_rate           DECIMAL(15,2) NOT NULL           -- snapshot tarif saat simpan
ts_rate           DECIMAL(15,2) NOT NULL
tk_rate           DECIMAL(15,2) NOT NULL
tc_rate           DECIMAL(15,2) NOT NULL
kribab_rate       DECIMAL(15,2) NOT NULL
total_amount      DECIMAL(15,2) NOT NULL            -- dihitung: sum(qty×rate)
created_at, updated_at

UNIQUE(session_id, karyawan_id)                    -- 1 karyawan max 1x per sesi
```

> `total_amount` = `(tb_qty×tb_rate) + (ts_qty×ts_rate) + (tk_qty×tk_rate) + (tc_qty×tc_rate) + (kribab_qty×kribab_rate)`

---

### 2.6 Modifikasi tabel POS yang sudah ada

**`hendhys_transaction_payments`** dan **`jihans_transaction_payments`**:
- Tambah kolom: `payment_method_id BIGINT NULLABLE FK → master_payment_methods`
- Kolom `payment_method` (enum lama) dan `bank_name` tetap ada untuk backward compat, tapi akan diisi dari relasi

---

## 3. Models

| Model | File | Keterangan |
|---|---|---|
| `PaymentMethod` | `app/Models/PaymentMethod.php` | FK scope ke entity |
| `Karyawan` | `app/Models/Karyawan.php` | Hanya Jihans scope |
| `ProductionRate` | `app/Models/ProductionRate.php` | Single-row per scope |
| `JihansTortillaSession` | `app/Models/JihansTortillaSession.php` | hasMany details |
| `JihansTortillaSessionDetail` | `app/Models/JihansTortillaSessionDetail.php` | belongsTo session, karyawan |

---

## 4. Controllers & Routes

### 4.1 Master Metode Pembayaran

**Controller:** `app/Http/Controllers/Master/PaymentMethodController.php`  
Menggunakan trait `ScopesMasterData` (pola sama dengan CustomerController).

Routes ditambahkan di `routes/hendhys.php` dan `routes/jihans.php`:
```php
Route::resource('payment-methods', PaymentMethodController::class)->except(['show']);
```

### 4.2 Master Karyawan

**Controller:** `app/Http/Controllers/Master/KaryawanController.php`  
Routes hanya di `routes/jihans.php`:
```php
Route::resource('karyawan', KaryawanController::class)->except(['show']);
```

### 4.3 Master Tarif Produksi

**Controller:** `app/Http/Controllers/Master/ProductionRateController.php`  
Hanya 2 method: `edit` dan `update` (bukan resource penuh).
Routes di `routes/jihans.php`:
```php
Route::get('production-rates', [ProductionRateController::class, 'edit'])->name('production-rates.edit');
Route::put('production-rates', [ProductionRateController::class, 'update'])->name('production-rates.update');
```

### 4.4 Produksi Tortilla

**Controller:** `app/Http/Controllers/Jihans/TortillaProductionController.php`

Methods:
- `index` — list sesi, filter by date range
- `create` — form input (pass karyawan aktif + tarif aktif ke view)
- `store` — simpan sesi + details dalam DB::transaction, snapshot tarif
- `show` — detail satu sesi
- `recap` — rekap mingguan (filter by week)
- `recapExport` — export Excel rekap

Routes di `routes/jihans.php`:
```php
Route::resource('tortilla-productions', TortillaProductionController::class)->except(['edit','update','destroy']);
Route::get('tortilla-recap', [TortillaProductionController::class, 'recap'])->name('tortilla-productions.recap');
Route::get('tortilla-recap/export', [TortillaProductionController::class, 'recapExport'])->name('tortilla-productions.recap.export');
```

---

## 5. UI Views

```
resources/views/
├── master/
│   ├── payment-methods/
│   │   ├── index.blade.php     (CRUD modal inline, pola sama dengan brands/units)
│   │   └── form.blade.php      (dedicated form untuk create/edit)
│   ├── karyawan/
│   │   ├── index.blade.php
│   │   └── form.blade.php
│   └── production-rates/
│       └── edit.blade.php      (form tunggal, bukan list)
└── jihans/
    └── tortilla-productions/
        ├── index.blade.php     (list sesi + link ke rekap)
        ├── form.blade.php      (Alpine.js + TomSelect per baris karyawan)
        ├── show.blade.php      (detail sesi)
        └── recap.blade.php     (tabel rekap mingguan + export)
```

---

## 6. Form Produksi Tortilla (Alpine.js)

```javascript
Alpine.data('tortillaForm', (karyawanList, rates) => ({
    items: [{ id: Date.now(), karyawan_id: '', tb:0, ts:0, tk:0, tc:0, kribab:0 }],

    get rowTotal() { /* per-row */ },
    get grandTotal() { /* sum all rows */ },

    addRow() { this.items.push({...}) },
    removeRow(index) { if (this.items.length > 1) this.items.splice(index, 1) }
}))
```

- `rates` di-pass dari controller ke view sebagai JSON (tb_rate, ts_rate, dll)
- Total per baris dihitung real-time: `(tb*rates.tb + ts*rates.ts + ...)`
- TomSelect: exclude karyawan yang sudah dipilih di baris lain (watch `x-model`)
- Semua qty input: `type="number" step="1" min="0"`, `Math.floor()` via Alpine, validasi `integer|min:0` backend

---

## 7. Rekap Mingguan

Query di `TortillaProductionController@recap`:

```php
$details = JihansTortillaSessionDetail::query()
    ->join('jihans_tortilla_sessions', ...)
    ->whereDate('date', '>=', $from)
    ->whereDate('date', '<=', $to)
    ->selectRaw('karyawan_id,
        COUNT(DISTINCT session_id) as total_hadir,
        SUM(tb_qty) as tb, SUM(ts_qty) as ts,
        SUM(tk_qty) as tk, SUM(tc_qty) as tc,
        SUM(kribab_qty) as kribab,
        SUM(total_amount) as total_upah')
    ->groupBy('karyawan_id')
    ->with('karyawan')
    ->get();
```

Export Excel: class `TortillaRecapExport` di `app/Exports/Jihans/`.

---

## 8. Integrasi POS

### Perubahan di POS Jihan's dan Hendhys:

1. **Migration:** tambah `payment_method_id` FK ke tabel payment masing-masing
2. **Controller `store`:** validasi `payment_method_id` exists di `master_payment_methods` sesuai entity_scope, gantikan hardcode `in:cash,transfer`
3. **View POS:** ganti dropdown `payment_method` dengan TomSelect/select dari daftar metode aktif (di-load via JSON dari controller)
4. Kalau metode punya `image` → tampilkan thumbnail QR di sebelah nama metode

---

## 9. Urutan Implementasi yang Disarankan

1. Migrations (semua sekaligus)
2. Models + seeders (`master_production_rates` seed awal)
3. Master Metode Pembayaran (controller + views)
4. Master Karyawan (controller + views)
5. Master Tarif Produksi (controller + view)
6. Produksi Tortilla — form input + store
7. Produksi Tortilla — rekap mingguan + export
8. Integrasi POS (payment_method_id)

---

## 10. Yang Tidak Diubah

- Modul `hendhys_productions` (produksi bakery Hendhys) — tidak terpengaruh
- Modul `jihans_productions` (produksi umum Jihan's) — tidak terpengaruh  
- Struktur stok, transfer, PO — tidak terpengaruh
- Auth, roles, middleware — tidak diubah
