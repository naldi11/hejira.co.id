# Design: Pemisahan Jalur Stok — Produksi vs Gudang

**Tanggal:** 2026-05-23  
**Status:** Approved  
**Scope:** Jihans Food & Hendhys Brownies & Gudang Tempua

---

## Latar Belakang

Sistem saat ini memiliki dua sumber stok yang berbeda secara fundamental:

1. **Produksi Sendiri** — Jihans memproduksi Tortilla (TB, TS, TK, TC, KRIBAB); Hendhys memproduksi brownies, bolu, kue, roti, dll.
2. **Dari Supplier / Gudang** — Gudang menerima barang dari supplier luar (daging, frozen food, saus, tepung, dll) lalu mentransfernya ke cabang.

**Masalah yang ada:**
- `TortillaProductionController@store` hanya menghitung gaji karyawan, tidak pernah memanggil `StockService::creditJihans()` → stok tortilla tidak bertambah dari produksi.
- Tidak ada pembeda di `master_products` antara produk produksi sendiri vs produk dari supplier.
- Tidak ada validasi yang mencegah produk produksi sendiri diminta lewat Transfer Request ke Gudang.

---

## Tujuan

1. Tambahkan `source_type` ke `master_products` sebagai single source of truth pembeda jalur stok.
2. Hubungkan varian tortilla Jihans ke `master_products` via mapping di `master_production_rates`.
3. Update `TortillaProductionController@store` agar otomatis menambah stok setelah produksi disimpan.
4. Blokir produk `produced` dari alur Transfer Request dan Transfer Out.

---

## Section 1 — Database

### Migrasi 1: `add_source_type_to_master_products`

```php
Schema::table('master_products', function (Blueprint $table) {
    $table->enum('source_type', ['produced', 'purchased'])
          ->default('purchased')
          ->after('product_type');
});
```

- Semua produk existing default `purchased`.
- Admin set `produced` untuk produk yang dibuat sendiri (tortilla, brownies, dll).

### Migrasi 2: `add_product_mapping_to_master_production_rates`

```php
Schema::table('master_production_rates', function (Blueprint $table) {
    $table->foreignId('tb_product_id')->nullable()->constrained('master_products')->nullOnDelete();
    $table->foreignId('ts_product_id')->nullable()->constrained('master_products')->nullOnDelete();
    $table->foreignId('tk_product_id')->nullable()->constrained('master_products')->nullOnDelete();
    $table->foreignId('tc_product_id')->nullable()->constrained('master_products')->nullOnDelete();
    $table->foreignId('kribab_product_id')->nullable()->constrained('master_products')->nullOnDelete();
});
```

- Nullable karena mapping boleh belum di-set (stok varian tersebut tidak akan diupdate jika NULL).
- `nullOnDelete` agar jika produk dihapus, mapping tidak crash.

---

## Section 2 — Alur Produksi Jihans (Stock Update)

### `TortillaProductionController@store` — Perubahan

**Tambah injeksi:**
```php
public function __construct(
    private NumberGeneratorService $numbers,
    private ActivityLogService $logger,
    private StockService $stockService  // BARU
) {}
```

**Logika baru setelah simpan session details (masih dalam DB::transaction):**
```php
// Mapping: key => [product_id dari rates, qty agregat]
$variantMap = [
    'tb'     => [$rates->tb_product_id,     collect($request->details)->sum('tb_qty')],
    'ts'     => [$rates->ts_product_id,     collect($request->details)->sum('ts_qty')],
    'tk'     => [$rates->tk_product_id,     collect($request->details)->sum('tk_qty')],
    'tc'     => [$rates->tc_product_id,     collect($request->details)->sum('tc_qty')],
    'kribab' => [$rates->kribab_product_id, collect($request->details)->sum('kribab_qty')],
];

foreach ($variantMap as [$productId, $totalQty]) {
    if ($productId && $totalQty > 0) {
        $product = Product::find($productId);
        $this->stockService->creditJihans(
            $productId,
            $product->unit_id,   // unit_id dari model Product, bukan hardcode
            (int) $totalQty,
            'production',
            $session->id,
            auth()->id()
        );
    }
}
```

**Catatan penting:** `unit_id` diambil dari `Product::find($productId)->unit_id` — bukan dari input user — agar konsisten dengan unit default produk tersebut.

---

## Section 3 — Validasi Transfer (Blokir Produk `produced`)

Tiga titik validasi. Logika yang sama diaplikasikan di ketiganya:

```php
// Ambil product_ids dari request items
$productIds = collect($request->items)->pluck('product_id');

// Cek apakah ada yang source_type = 'produced'
$producedProducts = Product::whereIn('id', $productIds)
    ->where('source_type', 'produced')
    ->pluck('name');

if ($producedProducts->isNotEmpty()) {
    return back()->withInput()->withErrors([
        'items' => 'Produk berikut adalah produk produksi sendiri dan tidak bisa diminta/dikirim dari Gudang: ' 
                   . $producedProducts->implode(', ')
    ]);
}
```

**Titik validasi:**

| Controller | Method | Keterangan |
|---|---|---|
| `Jihans\TransferRequestController` | `store()` | Validasi saat Jihans membuat request ke Gudang |
| `Hendhys\TransferRequestController` | `store()` | Validasi saat Hendhys membuat request ke Gudang |
| `Gudang\TransferOutController` | `store()` | Double-check saat Gudang memproses Transfer Out |

---

## Section 4 — Perubahan UI & Model

### Model `Product` (`app/Models/Product.php`)
- Tambah `'source_type'` ke `$fillable`.

### Model `ProductionRate` (`app/Models/ProductionRate.php`)
- Tambah 5 kolom ke `$fillable`: `tb_product_id`, `ts_product_id`, `tk_product_id`, `tc_product_id`, `kribab_product_id`.
- Tambah 5 relasi `belongsTo(Product::class)`: `tbProduct()`, `tsProduct()`, `tkProduct()`, `tcProduct()`, `kribabProduct()`.

### Form Master Produk (`resources/views/master/products/form.blade.php`)
- Tambah field `source_type` (radio atau select: "Produksi Sendiri" / "Dari Supplier/Gudang").
- Default: `purchased`.

### Form Production Rates Jihans (edit view)
- Tambah 5 dropdown pilih produk per varian.
- Filter produk yang ditampilkan: `entity_scope = 'jihans'` AND `source_type = 'produced'`.
- Tampilkan nama produk saat ini jika sudah ter-mapping.

### ProductionRateController (`app/Http/Controllers/Master/ProductionRateController.php`)
- Di method `edit()`: pass `$jihansProducedProducts` ke view.
```php
$jihansProducedProducts = Product::where('entity_scope', 'jihans')
    ->where('source_type', 'produced')
    ->where('status', 'active')
    ->orderBy('name')
    ->get();
```

---

## Section 5 — Setup Data Awal

Setelah migrasi dijalankan, admin perlu melakukan setup manual berikut (atau via seeder):

### 5.1 Buat produk Tortilla di Master Produk
Buat 5 produk baru dengan:
- `entity_scope = 'jihans'`
- `source_type = 'produced'`
- `product_type = 'INV'`

| Kode | Nama | Keterangan |
|---|---|---|
| JHS-TB | Tortilla Besar | Varian TB |
| JHS-TS | Tortilla Sedang | Varian TS |
| JHS-TK | Tortilla Kecil | Varian TK |
| JHS-TC | Tortilla Catering | Varian TC |
| JHS-KRB | KRIBAB | Sisa potongan produksi |

### 5.2 Set `source_type = 'produced'` untuk produk Hendhys
Semua produk Hendhys yang dibuat sendiri (brownies, bolu, kue, roti) diubah ke `source_type = 'produced'` via form edit produk atau SQL:
```sql
UPDATE master_products 
SET source_type = 'produced' 
WHERE entity_scope = 'hendhys';
```
*(Jalankan hanya jika semua produk Hendhys memang produksi sendiri)*

### 5.3 Set mapping di Production Rates Jihans
Buka halaman pengaturan Production Rates Jihans, pilih produk yang sesuai untuk tiap varian (TB → "Tortilla Besar", dst).

---

## Daftar File yang Berubah

| # | File | Tipe |
|---|---|---|
| 1 | `database/migrations/xxxx_add_source_type_to_master_products.php` | Baru |
| 2 | `database/migrations/xxxx_add_product_mapping_to_master_production_rates.php` | Baru |
| 3 | `app/Models/Product.php` | Update |
| 4 | `app/Models/ProductionRate.php` | Update |
| 5 | `app/Http/Controllers/Jihans/TortillaProductionController.php` | Update |
| 6 | `app/Http/Controllers/Master/ProductionRateController.php` | Update |
| 7 | View: form production rates Jihans | Update |
| 8 | `resources/views/master/products/form.blade.php` | Update |
| 9 | `app/Http/Controllers/Jihans/TransferRequestController.php` | Update |
| 10 | `app/Http/Controllers/Hendhys/TransferRequestController.php` | Update |
| 11 | `app/Http/Controllers/Gudang/TransferOutController.php` | Update |

---

## Keputusan yang Tidak Diambil (Out of Scope)

- **Bill of Materials (BOM)** — Tidak ada pengurangan bahan baku saat produksi. Scope diperluas hanya jika dibutuhkan di masa depan.
- **`source_type` untuk Gudang** — Gudang tidak punya produk `produced`, semua dari supplier. Tidak perlu flag khusus.
- `jihans_productions` table (lama) — Tabel vestigial dari desain awal, tidak digunakan dan tidak diubah.
