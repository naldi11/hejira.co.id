# HEJIRA Project — Agent Memory & Rules

## Stack
- **Backend**: Laravel 11, PHP, MySQL (db_hejira)
- **Frontend**: React (Inertia.js + Vite), Vanilla CSS + Tailwind utility classes
- **Server**: MAMP (localhost:8000), Nginx
- **Repo**: https://github.com/naldi11/hejira.co.id (branch: main)
- **Build**: `npm run build` setelah setiap perubahan JSX/JS

---

## Entitas Bisnis

| Entitas | Deskripsi | Layout |
|---------|-----------|--------|
| **Gudang Jihans** | Gudang utama/pusat penyimpanan stok produk | GudangLayout |
| **Jihan's Food** | Toko/Store (POS cabang Jihans) | JihansLayout |
| **Hendhys Brownies** | Toko/Store Hendhys | HendhysLayout |
| **Owner** | Dashboard pemilik | OwnerLayout |

> ⚠️ **Catatan penting**: "Gudang" = Gudang Jihans (bukan entitas terpisah). Tidak ada visibilitas "Gudang" di form master data — hanya "Jihan's" dan "Hendhys".

---

## Struktur Database Penting

### Stok
| Tabel | Keterangan |
|-------|-----------|
| `jihans_gudang_stocks` | Stok di Gudang Jihans (hasil produksi masuk sini) |
| `jihans_gudang_stock_movements` | Riwayat mutasi stok gudang |
| `jihans_retail_stocks` | Stok di Jihan's Store/POS |
| `jihans_retail_stock_movements` | Riwayat mutasi stok store |
| `hendhys_stock_branch` | Stok Hendhys cabang |
| `hendhys_stock_pusat` | Stok Hendhys pusat |

### Produksi
| Tabel | Keterangan |
|-------|-----------|
| `jihans_production_sessions` | Sesi produksi (prediksi & aktual) |
| `jihans_production_session_details` | Detail per karyawan per produk |

### Master Data
| Tabel | Keterangan |
|-------|-----------|
| `master_products` | Produk. `source_type`: `produced`\|`purchased` |
| `master_karyawan` | Karyawan. `entity_scope`: `jihans`\|`hendhys`\|`all` |
| `master_customers` | Pelanggan |

---

## Sistem Produksi (Jihans)

### Alur Produksi
```
[Input Prediksi] → disimpan sebagai type='prediksi' (TIDAK mempengaruhi stok)
      ↓
[Input Aktual] → disimpan sebagai type='aktual' 
      ↓
[Stok +qty] → masuk ke jihans_gudang_stocks (Gudang Jihans)
      ↓
[Distribusi] → transfer ke Jihan's Store jika diperlukan
```

### Rules Produksi
1. **Prediksi** → tidak mempengaruhi stok sama sekali
2. **Aktual** → langsung credit penuh qty ke Gudang Jihans
3. **Aktual tidak bisa diedit/dihapus** — hanya prediksi yang bisa
4. **Input Aktual otomatis pre-fill dari Prediksi** yang ada pada tanggal sama (user bisa override)
5. **Form produksi (prediksi & aktual) menggunakan matriks**: baris = karyawan, kolom = produk
6. Produk yang muncul di form produksi: hanya `source_type = 'produced'` dari master_products
7. **Qty selalu integer (parseInt)** — tidak ada desimal

### Filter Produk untuk Form Produksi
```php
Product::where('source_type', 'produced')
    ->where(function($q) {
        $q->where('entity_scope', 'jihans')->orWhere('entity_scope', 'all');
    })
    ->where('status', 'active')
```

### Controller
- `app/Http/Controllers/Jihans/ProductionController.php`
- Route prefix: `jihans.production.*`

### Routes Produksi (jihans.php)
```
GET  /jihans/production            → index
GET  /jihans/production/create     → create (aktual) — dapat query ?date=
POST /jihans/production            → store (aktual)
GET  /jihans/production/{id}       → show
GET  /jihans/production/prediksi/create → createPrediksi
POST /jihans/production/prediksi   → storePrediksi
GET  /jihans/production/prediksi/{id}/edit → editPrediksi
PUT  /jihans/production/prediksi/{id}      → updatePrediksi
DELETE /jihans/production/prediksi/{id}    → destroyPrediksi
GET  /jihans/production/recap      → recap
```

---

## StockService — Method Penting

```php
// Tambah stok Gudang Jihans (dipakai saat aktual produksi)
$stockService->creditJihansGudang($productId, $unitId, $qty, 'production', $sessionId, $userId);

// Kurangi stok Gudang Jihans
$stockService->debitJihansGudang($productId, $qty, 'source', $referenceId, $userId);

// Tambah stok Store Jihans (POS)
$stockService->creditJihansRetail(...)

// Sesuaikan stok (adjustment)
$stockService->adjustJihansGudang($productId, $unitId, $newQty, $userId);
```

---

## Visibilitas Entitas di Form Master Data

### Products (`resources/js/Pages/Master/Products/Form.jsx`)
```js
const VISIBILITY = [
    { key: 'visible_jihans', label: "Jihan's", icon: 'storefront' },
    { key: 'visible_hendhys', label: 'Hendhys', icon: 'cake' },
    // visible_gudang DIHAPUS dari UI (disembunyikan, tidak dihapus dari DB)
];
```

### Customers (`resources/js/Pages/Master/Customers/Form.jsx`)
```js
const VISIBILITY = [
    { key: 'visible_jihans', label: "Jihan's Food", icon: 'storefront' },
    { key: 'visible_hendhys', label: 'Hendhys Brownies', icon: 'cake' },
    // visible_gudang DIHAPUS dari UI
];
```

---

## Sidebar Menu (JihansLayout)

Menu "Data Produksi" mengarah ke `jihans.production.index`.
Menu "Konfigurasi Produksi" sudah **DIHAPUS**.
Menu lama "Produksi Tortilla" sudah **DIGANTI** menjadi "Data Produksi".

---

## Pages Produksi

| File | Keterangan |
|------|-----------|
| `resources/js/Pages/Jihans/Production/Index.jsx` | Daftar sesi produksi (prediksi & aktual) |
| `resources/js/Pages/Jihans/Production/Form.jsx` | Form input (prediksi & aktual) — matriks karyawan x produk |
| `resources/js/Pages/Jihans/Production/Show.jsx` | Detail sesi produksi |
| `resources/js/Pages/Jihans/Production/Recap.jsx` | Rekapitulasi produksi aktual |

### Halaman yang DIHAPUS
- `resources/js/Pages/Jihans/Tortilla/*` (Form, Index, Recap, Show)
- `resources/js/Pages/Jihans/ProductionConfig.jsx`
- `resources/js/Pages/Owner/Gudang.jsx`
- `resources/js/Pages/Owner/Hendhys.jsx`
- `resources/js/Pages/Owner/Jihans.jsx`

---

## Catatan Penting Data

### Produk Tortilla di Database
- **ID=645** "Tortilla Kecil" — `source_type=produced` ✅ (muncul di form produksi)
- **ID=646** "Tortilla Besar" — `source_type=produced` ✅ (muncul di form produksi)
- **ID=81** "Tortilla Besar" — `source_type=purchased` ❌ (TIDAK muncul di form produksi, ini dari supplier)

> Nama ID=646 dan ID=81 sama persis. Ini bisa membingungkan. Sarankan rename ID=81 menjadi "Tortilla Besar (Supplier)" di master data UI.

### Karyawan
- Table: `master_karyawan` (bukan `karyawans`)
- Model: `App\Models\Karyawan`
- Filter untuk Jihans: `entity_scope = 'jihans'`

---

## Migrasi Penting (Sudah Dijalankan)

```
2026_07_01_000000_rename_gudang_and_jihans_stock_tables.php
2026_07_01_043807_update_source_enum_in_jihans_gudang_stock_movements_table.php
2026_07_01_044716_rebuild_production_sessions_table.php
```

---

## Tips untuk AI Agent

1. **Setelah edit JSX/JS** → selalu jalankan `npm run build` di `/Applications/MAMP/htdocs/hejira.co.id`
2. **Jangan gunakan `cat`, `grep` dalam bash** → gunakan tools `view_file` dan `grep_search`
3. **Sebelum push** → `git add -A && git commit -m "..."` lalu `git push origin main`
4. **Cek stok movement** via `JihansGudangStockMovement::where('source','production')->get()`
5. **Prediksi tidak boleh mengubah stok** — hanya aktual
6. **Input Aktual selalu INT** — gunakan `parseInt()` bukan `parseFloat()` atau `Number()`
