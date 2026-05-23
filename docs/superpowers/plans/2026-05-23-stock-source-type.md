# Stock Source Type Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tambahkan `source_type` ke `master_products` untuk membedakan produk produksi sendiri vs dari supplier, hubungkan varian tortilla Jihans ke produk stok, dan blokir produk produksi dari jalur Transfer Gudang.

**Architecture:** Dua migrasi baru menambahkan kolom `source_type` di `master_products` dan mapping `*_product_id` di `master_production_rates`. `TortillaProductionController@store` diperbarui untuk memanggil `StockService::creditJihans()` setelah produksi disimpan. Tiga controller Transfer mendapat validasi blokir `source_type=produced`.

**Tech Stack:** Laravel 11, PHP 8.2, MySQL, Blade/Alpine.js, Tailwind CSS (design tokens existing), Tom Select (sudah ada di form produk)

---

## File Map

| File | Status | Tanggung Jawab |
|---|---|---|
| `database/migrations/2026_05_23_100001_add_source_type_to_master_products.php` | Baru | Tambah kolom source_type |
| `database/migrations/2026_05_23_100002_add_product_mapping_to_master_production_rates.php` | Baru | Tambah 5 FK product_id |
| `app/Models/Product.php` | Ubah | Tambah source_type ke fillable |
| `app/Models/ProductionRate.php` | Ubah | Tambah product_id ke fillable + 5 relasi |
| `app/Http/Controllers/Master/ProductController.php` | Ubah | Tambah validasi source_type di store & update |
| `resources/views/master/products/form.blade.php` | Ubah | Tambah field source_type di section tipe |
| `app/Http/Controllers/Master/ProductionRateController.php` | Ubah | Pass $producedProducts ke view, tambah validasi |
| `resources/views/master/production-rates/edit.blade.php` | Ubah | Tambah section mapping produk |
| `app/Http/Controllers/Jihans/TortillaProductionController.php` | Ubah | Inject StockService, tambah creditJihans di store |
| `app/Http/Controllers/Jihans/TransferRequestController.php` | Ubah | Tambah validasi source_type |
| `app/Http/Controllers/Hendhys/TransferRequestController.php` | Ubah | Tambah validasi source_type |
| `app/Http/Controllers/Gudang/TransferOutController.php` | Ubah | Tambah validasi source_type |

---

## Task 1: Migrasi — Tambah `source_type` ke `master_products`

**Files:**
- Create: `database/migrations/2026_05_23_100001_add_source_type_to_master_products.php`

- [ ] **Step 1: Buat file migrasi**

```bash
php artisan make:migration add_source_type_to_master_products --table=master_products
```

- [ ] **Step 2: Isi konten migrasi** (ganti isi file yang dibuat artisan dengan kode berikut)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->enum('source_type', ['produced', 'purchased'])
                  ->default('purchased')
                  ->after('product_type');
        });
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }
};
```

- [ ] **Step 3: Jalankan migrasi**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_05_23_100001_add_source_type_to_master_products` → `Migrated`

- [ ] **Step 4: Verifikasi kolom ada di DB**

```bash
php artisan tinker --execute="echo Schema::hasColumn('master_products', 'source_type') ? 'OK' : 'GAGAL';"
```

Expected output: `OK`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat: add source_type column to master_products"
```

---

## Task 2: Migrasi — Tambah mapping product_id ke `master_production_rates`

**Files:**
- Create: `database/migrations/2026_05_23_100002_add_product_mapping_to_master_production_rates.php`

- [ ] **Step 1: Buat file migrasi**

```bash
php artisan make:migration add_product_mapping_to_master_production_rates --table=master_production_rates
```

- [ ] **Step 2: Isi konten migrasi**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_production_rates', function (Blueprint $table) {
            $table->foreignId('tb_product_id')->nullable()->after('kribab_rate')
                  ->constrained('master_products')->nullOnDelete();
            $table->foreignId('ts_product_id')->nullable()->after('tb_product_id')
                  ->constrained('master_products')->nullOnDelete();
            $table->foreignId('tk_product_id')->nullable()->after('ts_product_id')
                  ->constrained('master_products')->nullOnDelete();
            $table->foreignId('tc_product_id')->nullable()->after('tk_product_id')
                  ->constrained('master_products')->nullOnDelete();
            $table->foreignId('kribab_product_id')->nullable()->after('tc_product_id')
                  ->constrained('master_products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('master_production_rates', function (Blueprint $table) {
            $table->dropForeign(['tb_product_id']);
            $table->dropForeign(['ts_product_id']);
            $table->dropForeign(['tk_product_id']);
            $table->dropForeign(['tc_product_id']);
            $table->dropForeign(['kribab_product_id']);
            $table->dropColumn(['tb_product_id', 'ts_product_id', 'tk_product_id', 'tc_product_id', 'kribab_product_id']);
        });
    }
};
```

- [ ] **Step 3: Jalankan migrasi**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_05_23_100002_add_product_mapping_to_master_production_rates` → `Migrated`

- [ ] **Step 4: Verifikasi**

```bash
php artisan tinker --execute="echo Schema::hasColumn('master_production_rates', 'tb_product_id') ? 'OK' : 'GAGAL';"
```

Expected: `OK`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat: add tortilla product_id mapping columns to master_production_rates"
```

---

## Task 3: Update Model `Product` & `ProductionRate`

**Files:**
- Modify: `app/Models/Product.php`
- Modify: `app/Models/ProductionRate.php`

- [ ] **Step 1: Tambah `source_type` ke `Product::$fillable`**

Di `app/Models/Product.php`, ganti baris `$fillable`:

```php
// SEBELUM:
protected $fillable = [
    'code', 'barcode', 'name', 'category_id', 'unit_id', 'brand_id',
    'rack', 'jenis', 'hpp', 'selling_price', 'stock_min',
    'ppn_type', 'ppn_rate', 'product_type', 'entity_scope',
    'visible_gudang', 'visible_jihans', 'visible_hendhys',
    'status', 'notes', 'image', 'created_by',
];

// SESUDAH:
protected $fillable = [
    'code', 'barcode', 'name', 'category_id', 'unit_id', 'brand_id',
    'rack', 'jenis', 'hpp', 'selling_price', 'stock_min',
    'ppn_type', 'ppn_rate', 'product_type', 'source_type', 'entity_scope',
    'visible_gudang', 'visible_jihans', 'visible_hendhys',
    'status', 'notes', 'image', 'created_by',
];
```

- [ ] **Step 2: Update `ProductionRate.php` — fillable + relasi**

Ganti seluruh isi `app/Models/ProductionRate.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRate extends Model
{
    protected $table = 'master_production_rates';

    protected $fillable = [
        'entity_scope',
        'tb_rate', 'ts_rate', 'tk_rate', 'tc_rate', 'kribab_rate',
        'tb_product_id', 'ts_product_id', 'tk_product_id', 'tc_product_id', 'kribab_product_id',
        'notes', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tb_rate'     => 'decimal:2',
            'ts_rate'     => 'decimal:2',
            'tk_rate'     => 'decimal:2',
            'tc_rate'     => 'decimal:2',
            'kribab_rate' => 'decimal:2',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tbProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'tb_product_id');
    }

    public function tsProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ts_product_id');
    }

    public function tkProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'tk_product_id');
    }

    public function tcProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'tc_product_id');
    }

    public function kribabProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kribab_product_id');
    }
}
```

- [ ] **Step 3: Verifikasi model lewat tinker**

```bash
php artisan tinker --execute="use App\Models\ProductionRate; \$r = new ProductionRate; echo implode(', ', \$r->getFillable());"
```

Expected: output mengandung `tb_product_id`, `ts_product_id`, dst.

- [ ] **Step 4: Commit**

```bash
git add app/Models/Product.php app/Models/ProductionRate.php
git commit -m "feat: add source_type to Product and product mapping to ProductionRate model"
```

---

## Task 4: `ProductController` — Tambah Validasi `source_type`

**Files:**
- Modify: `app/Http/Controllers/Master/ProductController.php`

- [ ] **Step 1: Tambah validasi `source_type` di method `store()`**

Di method `store()`, dalam array `$request->validate([...])`, tambahkan satu baris setelah `'product_type'`:

```php
// Cari baris ini:
'product_type'       => 'required|in:INV,NON',

// Tambahkan tepat setelahnya:
'source_type'        => 'required|in:produced,purchased',
```

- [ ] **Step 2: Tambah validasi `source_type` di method `update()`**

Di method `update()`, dalam array validasi, lakukan hal yang sama:

```php
// Cari baris ini:
'product_type'    => 'required|in:INV,NON',

// Tambahkan tepat setelahnya:
'source_type'     => 'required|in:produced,purchased',
```

- [ ] **Step 3: Verifikasi sintaks**

```bash
php artisan route:list --path=products 2>&1 | head -5
```

Expected: tidak ada error, daftar route produk muncul.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Master/ProductController.php
git commit -m "feat: add source_type validation to ProductController store and update"
```

---

## Task 5: View Form Master Produk — Tambah Field `source_type`

**Files:**
- Modify: `resources/views/master/products/form.blade.php`

- [ ] **Step 1: Sisipkan field `source_type` setelah field `product_type`**

Cari blok ini di file (sekitar baris 348–353):

```blade
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">
                            Tipe Produk
                            <span class="text-outline font-normal normal-case"> — apakah stok ditrack?</span>
                        </label>
                        <select name="product_type"
                            class="select2 w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest">
                            <option value="INV" {{ old('product_type', $product->product_type ?? 'INV') === 'INV' ? 'selected' : '' }}>INV — Stok dicatat &amp; ditrack</option>
                            <option value="NON" {{ old('product_type', $product->product_type ?? '') === 'NON' ? 'selected' : '' }}>NON — Stok tidak ditrack (jasa / non-fisik)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Status</label>
```

Ganti menjadi (tambahkan blok `source_type` di antara keduanya):

```blade
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">
                            Tipe Produk
                            <span class="text-outline font-normal normal-case"> — apakah stok ditrack?</span>
                        </label>
                        <select name="product_type"
                            class="select2 w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest">
                            <option value="INV" {{ old('product_type', $product->product_type ?? 'INV') === 'INV' ? 'selected' : '' }}>INV — Stok dicatat &amp; ditrack</option>
                            <option value="NON" {{ old('product_type', $product->product_type ?? '') === 'NON' ? 'selected' : '' }}>NON — Stok tidak ditrack (jasa / non-fisik)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">
                            Sumber Stok
                            <span class="text-outline font-normal normal-case"> — dari mana stok produk ini?</span>
                        </label>
                        <select name="source_type"
                            class="select2 w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest">
                            <option value="purchased" {{ old('source_type', $product->source_type ?? 'purchased') === 'purchased' ? 'selected' : '' }}>Dari Supplier / Gudang</option>
                            <option value="produced" {{ old('source_type', $product->source_type ?? '') === 'produced' ? 'selected' : '' }}>Produksi Sendiri</option>
                        </select>
                        @error('source_type') <p class="text-error font-label-sm text-label-sm mt-xs">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Status</label>
```

- [ ] **Step 2: Verifikasi manual di browser**

Buka halaman Tambah Produk. Pastikan:
- Field "Sumber Stok" muncul di antara "Tipe Produk" dan "Status"
- Default terpilih "Dari Supplier / Gudang"
- Coba simpan produk baru — tidak ada error validasi

- [ ] **Step 3: Commit**

```bash
git add resources/views/master/products/form.blade.php
git commit -m "feat: add source_type field to master products form"
```

---

## Task 6: `ProductionRateController` — Pass Products & Simpan Mapping

**Files:**
- Modify: `app/Http/Controllers/Master/ProductionRateController.php`

- [ ] **Step 1: Tambah `use App\Models\Product;` di bagian import**

Di bagian atas file setelah `use App\Models\ProductionRate;`, tambahkan:

```php
use App\Models\Product;
```

- [ ] **Step 2: Update method `edit()` untuk pass `$producedProducts`**

Ganti seluruh method `edit()`:

```php
public function edit(Request $request)
{
    $info = $this->getScopeInfo($request);
    $rate = ProductionRate::where('entity_scope', $info['scope'])->first();

    $producedProducts = collect();
    if ($info['scope'] === 'jihans') {
        $producedProducts = Product::with('unit')
            ->where('entity_scope', 'jihans')
            ->where('source_type', 'produced')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    return view('master.production-rates.edit', [
        'rate'             => $rate,
        'layout'           => $info['layout'],
        'routePrefix'      => $info['route'],
        'currentScope'     => $info['scope'],
        'producedProducts' => $producedProducts,
    ]);
}
```

- [ ] **Step 3: Update method `update()` untuk validasi dan simpan product_id**

Ganti seluruh method `update()`:

```php
public function update(Request $request)
{
    $info = $this->getScopeInfo($request);
    $data = $request->validate([
        'tb_rate'           => 'required|numeric|min:0',
        'ts_rate'           => 'required|numeric|min:0',
        'tk_rate'           => 'required|numeric|min:0',
        'tc_rate'           => 'required|numeric|min:0',
        'kribab_rate'       => 'required|numeric|min:0',
        'tb_product_id'     => 'nullable|exists:master_products,id',
        'ts_product_id'     => 'nullable|exists:master_products,id',
        'tk_product_id'     => 'nullable|exists:master_products,id',
        'tc_product_id'     => 'nullable|exists:master_products,id',
        'kribab_product_id' => 'nullable|exists:master_products,id',
        'notes'             => 'nullable|string',
    ]);

    $data['updated_by']   = auth()->id();
    $data['entity_scope'] = $info['scope'];

    ProductionRate::updateOrCreate(
        ['entity_scope' => $info['scope']],
        $data
    );

    $this->logger->log('update', 'master.production_rate', "Update tarif produksi {$info['scope']}");

    return redirect()->route($info['route'] . 'production-rates.edit')
        ->with('success', 'Tarif produksi berhasil diperbarui.');
}
```

- [ ] **Step 4: Verifikasi sintaks**

```bash
php artisan route:list --path=production-rates 2>&1 | head -5
```

Expected: tidak ada error.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Master/ProductionRateController.php
git commit -m "feat: ProductionRateController passes produced products and saves product mapping"
```

---

## Task 7: View Production Rates — Tambah Section Mapping Produk

**Files:**
- Modify: `resources/views/master/production-rates/edit.blade.php`

> **Catatan:** Mapping produk disisipkan ke dalam form yang sudah ada (satu form, satu submit). Ini mencegah kolom `product_id` ter-null saat hanya form tarif yang di-submit.

- [ ] **Step 1: Sisipkan section mapping sebelum footer submit button di dalam form yang sudah ada**

Cari baris ini (footer card dengan tombol "Simpan Tarif"):

```blade
                    <div class="px-md py-sm bg-surface-container-low border-t border-outline-variant flex justify-between items-center">
                        <p class="text-xs text-on-surface-variant italic">
                            @if(isset($rate) && $rate->updated_at)
                                Terakhir diperbarui: {{ $rate->updated_at->format('d M Y H:i') }}
                            @endif
                        </p>
                        <button type="submit" class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all">
                            <span class="material-symbols-outlined text-[18px]">save</span>
                            Simpan Tarif
                        </button>
                    </div>
                </div>
            </form>
```

Ganti dengan (sisipkan section mapping antara penutup `</div>` card-body dan footer):

```blade
                        {{-- Mapping Produk ke Stok — hanya tampil untuk Jihans --}}
                        @if($currentScope === 'jihans')
                        <div class="border-t border-outline-variant pt-md mt-md">
                            <h4 class="font-label-md text-label-md font-semibold text-on-surface-variant mb-xs uppercase tracking-wider">
                                Mapping Varian ke Produk Stok
                            </h4>
                            <p class="font-body-sm text-body-sm text-on-surface-variant mb-md">
                                Pilih produk yang stoknya bertambah otomatis saat produksi tortilla disimpan.
                            </p>

                            @if($producedProducts->isEmpty())
                                <div class="bg-surface-container-low rounded-lg p-sm flex items-center gap-sm border border-outline-variant">
                                    <span class="material-symbols-outlined text-outline">info</span>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                                        Belum ada produk Jihans dengan Sumber Stok "Produksi Sendiri".
                                        <a href="{{ route($routePrefix . 'products.create') }}" class="underline font-medium text-primary">Buat produk</a> terlebih dahulu.
                                    </p>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                                    @foreach([
                                        ['tb_product_id',     'TB — Tortilla Besar'],
                                        ['ts_product_id',     'TS — Tortilla Sedang'],
                                        ['tk_product_id',     'TK — Tortilla Kecil'],
                                        ['tc_product_id',     'TC — Tortilla Catering'],
                                        ['kribab_product_id', 'KRIBAB — Sisa Potongan'],
                                    ] as [$field, $label])
                                        <div>
                                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">{{ $label }}</label>
                                            <select name="{{ $field }}"
                                                class="w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest py-sm px-sm">
                                                <option value="">— Belum di-mapping —</option>
                                                @foreach($producedProducts as $prod)
                                                    <option value="{{ $prod->id }}"
                                                        {{ old($field, $rate?->{$field}) == $prod->id ? 'selected' : '' }}>
                                                        {{ $prod->name }}{{ $prod->unit ? ' ('.$prod->unit->abbreviation.')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endif

                    </div>

                    <div class="px-md py-sm bg-surface-container-low border-t border-outline-variant flex justify-between items-center">
                        <p class="text-xs text-on-surface-variant italic">
                            @if(isset($rate) && $rate->updated_at)
                                Terakhir diperbarui: {{ $rate->updated_at->format('d M Y H:i') }}
                            @endif
                        </p>
                        <button type="submit" class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all">
                            <span class="material-symbols-outlined text-[18px]">save</span>
                            Simpan Tarif & Mapping
                        </button>
                    </div>
                </div>
            </form>
```

- [ ] **Step 2: Verifikasi manual di browser**

Buka halaman Production Rates untuk Jihans. Pastikan:
- Section "Mapping Varian ke Produk Stok" muncul di bawah section tarif
- Jika belum ada produk `produced`, muncul pesan info dengan link ke tambah produk
- Jika ada produk `produced`, muncul 5 dropdown

- [ ] **Step 3: Commit**

```bash
git add resources/views/master/production-rates/edit.blade.php
git commit -m "feat: add product mapping section to production rates form (Jihans only)"
```

---

## Task 8: `TortillaProductionController` — Integrasi StockService

**Files:**
- Modify: `app/Http/Controllers/Jihans/TortillaProductionController.php`

- [ ] **Step 1: Tambah import `Product` dan `StockService`**

Di bagian atas file, cari blok `use`:

```php
use App\Models\JihansTortillaSession;
use App\Models\JihansTortillaSessionDetail;
use App\Models\Karyawan;
use App\Models\ProductionRate;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
```

Tambahkan dua baris baru:

```php
use App\Models\JihansTortillaSession;
use App\Models\JihansTortillaSessionDetail;
use App\Models\Karyawan;
use App\Models\Product;
use App\Models\ProductionRate;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
```

- [ ] **Step 2: Tambah `StockService` ke constructor**

Ganti constructor:

```php
// SEBELUM:
public function __construct(
    private NumberGeneratorService $numbers,
    private ActivityLogService $logger
) {}

// SESUDAH:
public function __construct(
    private NumberGeneratorService $numbers,
    private ActivityLogService $logger,
    private StockService $stockService
) {}
```

- [ ] **Step 3: Update method `store()` — tambah agregasi dan creditJihans**

Cari blok `foreach ($request->details as $detail)` di dalam `DB::transaction` dan keseluruhan penutupan transaksi. Ganti blok `DB::transaction` menjadi:

```php
DB::transaction(function () use ($request, $rates) {
    $session = JihansTortillaSession::create([
        'session_number' => $this->numbers->generateYearly('JHS-TOR', 'jihans_tortilla_sessions', 'session_number'),
        'date'           => $request->date,
        'notes'          => $request->notes,
        'created_by'     => auth()->id(),
    ]);

    foreach ($request->details as $detail) {
        $total = ($detail['tb_qty'] * $rates->tb_rate) +
                 ($detail['ts_qty'] * $rates->ts_rate) +
                 ($detail['tk_qty'] * $rates->tk_rate) +
                 ($detail['tc_qty'] * $rates->tc_rate) +
                 ($detail['kribab_qty'] * $rates->kribab_rate);

        $session->details()->create([
            'karyawan_id'  => $detail['karyawan_id'],
            'tb_qty'       => $detail['tb_qty'],
            'ts_qty'       => $detail['ts_qty'],
            'tk_qty'       => $detail['tk_qty'],
            'tc_qty'       => $detail['tc_qty'],
            'kribab_qty'   => $detail['kribab_qty'],
            'tb_rate'      => $rates->tb_rate,
            'ts_rate'      => $rates->ts_rate,
            'tk_rate'      => $rates->tk_rate,
            'tc_rate'      => $rates->tc_rate,
            'kribab_rate'  => $rates->kribab_rate,
            'total_amount' => $total,
        ]);
    }

    // Agregasi total per varian dan update stok Jihans
    $details = collect($request->details);
    $variantMap = [
        [$rates->tb_product_id,     (int) $details->sum('tb_qty')],
        [$rates->ts_product_id,     (int) $details->sum('ts_qty')],
        [$rates->tk_product_id,     (int) $details->sum('tk_qty')],
        [$rates->tc_product_id,     (int) $details->sum('tc_qty')],
        [$rates->kribab_product_id, (int) $details->sum('kribab_qty')],
    ];

    foreach ($variantMap as [$productId, $totalQty]) {
        if ($productId && $totalQty > 0) {
            $product = Product::find($productId);
            $this->stockService->creditJihans(
                $productId,
                $product->unit_id,
                $totalQty,
                'production',
                $session->id,
                auth()->id()
            );
        }
    }

    $this->logger->log('create', 'jihans.tortilla', "Input produksi tortilla: {$session->session_number}", $session);
});
```

- [ ] **Step 4: Verifikasi sintaks**

```bash
php artisan route:list --path=tortilla 2>&1 | head -5
```

Expected: tidak ada error parse.

- [ ] **Step 5: Test manual**

1. Pastikan sudah ada minimal 1 produk tortilla dengan `source_type=produced` di Master Produk
2. Set mapping di Production Rates Jihans
3. Input produksi tortilla dengan qty > 0 untuk salah satu varian
4. Cek `jihans_stock` via tinker:
   ```bash
   php artisan tinker --execute="use App\Models\JihansStock; JihansStock::all()->each(fn(\$s) => print(\$s->product_id . ' => ' . \$s->quantity . PHP_EOL));"
   ```
   Expected: stok produk yang di-mapping bertambah sesuai total qty

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Jihans/TortillaProductionController.php
git commit -m "feat: TortillaProductionController now credits jihans_stock after production save"
```

---

## Task 9: Validasi Transfer — Blokir Produk `produced` di 3 Controller

**Files:**
- Modify: `app/Http/Controllers/Jihans/TransferRequestController.php`
- Modify: `app/Http/Controllers/Hendhys/TransferRequestController.php`
- Modify: `app/Http/Controllers/Gudang/TransferOutController.php`

### 9a — Jihans\TransferRequestController

- [ ] **Step 1: Tambah validasi di method `store()` sebelum `DB::transaction`**

Cari baris `DB::transaction(function () use ($request) {` di method `store()` dan sisipkan blok berikut tepat sebelumnya:

```php
// Blokir produk produksi sendiri dari Transfer Request
$producedNames = Product::whereIn('id', collect($request->items)->pluck('product_id'))
    ->where('source_type', 'produced')
    ->pluck('name');

if ($producedNames->isNotEmpty()) {
    return back()->withInput()->withErrors([
        'items' => 'Produk berikut adalah produk produksi sendiri dan tidak bisa diminta dari Gudang: '
                   . $producedNames->implode(', '),
    ]);
}
```

Pastikan `use App\Models\Product;` sudah ada di import (sudah ada berdasarkan kode yang dilihat sebelumnya).

### 9b — Hendhys\TransferRequestController

- [ ] **Step 2: Tambah import `Product` jika belum ada**

Cek bagian atas file. Jika `use App\Models\Product;` belum ada, tambahkan.

- [ ] **Step 3: Tambah validasi di method `store()` sebelum `try {`**

Cari baris `try {` di method `store()` dan sisipkan blok berikut tepat sebelumnya:

```php
// Blokir produk produksi sendiri dari Transfer Request
$producedNames = Product::whereIn('id', collect($request->items)->pluck('product_id'))
    ->where('source_type', 'produced')
    ->pluck('name');

if ($producedNames->isNotEmpty()) {
    return back()->withInput()->withErrors([
        'items' => 'Produk berikut adalah produk produksi sendiri dan tidak bisa diminta dari Gudang: '
                   . $producedNames->implode(', '),
    ]);
}
```

### 9c — Gudang\TransferOutController

- [ ] **Step 4: Tambah validasi di method `store()` setelah validasi stok sufficiency**

Di method `store()`, cari blok validasi stok (loop `foreach ($request->items as $item)` yang mengecek stok gudang). Tambahkan blok berikut tepat setelah loop tersebut (sebelum `DB::transaction`):

```php
// Blokir produk produksi sendiri dari Transfer Out
$producedNames = Product::whereIn('id', collect($request->items)->pluck('product_id'))
    ->where('source_type', 'produced')
    ->pluck('name');

if ($producedNames->isNotEmpty()) {
    return back()->withInput()->withErrors([
        'items' => 'Produk berikut adalah produk produksi sendiri dan tidak bisa dikirim dari Gudang: '
                   . $producedNames->implode(', '),
    ]);
}
```

- [ ] **Step 5: Test manual validasi**

1. Buka form Transfer Request Jihans
2. Coba tambahkan produk yang `source_type=produced`
3. Expected: form gagal submit dengan pesan error "Produk ... adalah produk produksi sendiri dan tidak bisa diminta dari Gudang"

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Jihans/TransferRequestController.php \
        app/Http/Controllers/Hendhys/TransferRequestController.php \
        app/Http/Controllers/Gudang/TransferOutController.php
git commit -m "feat: block produced products from transfer requests and transfer out"
```

---

## Task 10: Setup Data Awal

**Tidak ada perubahan kode** — ini adalah instruksi konfigurasi setelah semua kode di-deploy.

- [ ] **Step 1: Buat 5 produk tortilla Jihans di Master Produk**

Login sebagai admin Jihans atau Gudang, buka Master Produk → Tambah Produk. Buat 5 produk berikut:

| Kode | Nama | Entity Scope | Sumber Stok | Tipe Produk | Status |
|---|---|---|---|---|---|
| JHS-TB | Tortilla Besar | jihans | Produksi Sendiri | INV | Aktif |
| JHS-TS | Tortilla Sedang | jihans | Produksi Sendiri | INV | Aktif |
| JHS-TK | Tortilla Kecil | jihans | Produksi Sendiri | INV | Aktif |
| JHS-TC | Tortilla Catering | jihans | Produksi Sendiri | INV | Aktif |
| JHS-KRB | KRIBAB | jihans | Produksi Sendiri | INV | Aktif |

Satuan bisa diisi sesuai satuan yang sudah ada (mis: "Bungkus", "Lembar", atau "Pak").

- [ ] **Step 2: Set `source_type=produced` untuk produk Hendhys**

Untuk semua produk Hendhys yang diproduksi sendiri (brownies, bolu, kue, roti), buka halaman edit produk dan ubah "Sumber Stok" ke "Produksi Sendiri".

Atau via SQL (jalankan hanya jika SEMUA produk Hendhys memang produksi sendiri):

```sql
UPDATE master_products 
SET source_type = 'produced' 
WHERE entity_scope = 'hendhys' AND status = 'active';
```

- [ ] **Step 3: Konfigurasi mapping di Production Rates Jihans**

Login Jihans, buka Master → Pengaturan Tarif Produksi. Di bagian bawah "Mapping Varian ke Produk Stok", pilih produk yang sesuai untuk setiap varian (TB → Tortilla Besar, dst.) lalu klik "Simpan Mapping".

- [ ] **Step 4: Verifikasi end-to-end**

1. Input produksi tortilla dengan jumlah tertentu (mis. 10 TB, 5 TS)
2. Cek stok Jihans → stok "Tortilla Besar" harus bertambah 10, "Tortilla Sedang" bertambah 5
3. Cek `jihans_stock_movements` — harus ada entri baru dengan `source='production'`

```bash
php artisan tinker --execute="
use App\Models\JihansStock;
use Illuminate\Support\Facades\DB;
\$stocks = JihansStock::with('product')->get();
foreach (\$stocks as \$s) {
    echo \$s->product->name . ': ' . \$s->quantity . PHP_EOL;
}
"
```

- [ ] **Step 5: Final commit**

```bash
git add -A
git status
git commit -m "feat: complete stock source type separation - produced vs purchased"
```
