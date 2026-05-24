# Design Spec: POS Reports & Invoice A5 Redesign
**Date:** 2026-05-25  
**Entities:** Jihans Food & Hendhys Brownies (kode dipisah, tanpa shared service)  
**Implementor:** Gemini  
**Tester:** Claude  

---

## Scope

1. **DB Migration** — tambah kolom `type` ke `master_payment_methods`
2. **Laporan Kasir** — Laci, Harian, Mingguan, Bulanan (Jihans & Hendhys terpisah)
3. **Laporan Per Pelanggan** (Jihans & Hendhys terpisah)
4. **Faktur A5 Jihans** — redesign `receipt.blade.php` → ukuran A5 + header baru
5. **Faktur A5 Hendhys** — file baru `invoice.blade.php` ukuran A5

---

## 1. Database Migration

### 1.1 Tambah `type` ke `master_payment_methods`

File: `database/migrations/TIMESTAMP_add_type_to_master_payment_methods.php`

```php
Schema::table('master_payment_methods', function (Blueprint $table) {
    $table->enum('type', ['tunai', 'kredit', 'kartu_debit', 'kartu_kredit'])
          ->default('tunai')
          ->after('name');
});
```

**Seeder update** (`MasterPaymentMethodSeeder` atau di dalam `DatabaseSeeder`):
Pastikan payment methods yang ada di-seed dengan tipe yang benar:
- "Tunai" / "Cash" → `type = 'tunai'`
- "Kartu Debit" / "Debit" / "GoPay" / "OVO" / "Dana" → `type = 'kartu_debit'`
- "Kartu Kredit" / "Credit Card" → `type = 'kartu_kredit'`
- "Kredit" / "Hutang" → `type = 'kredit'`

**Catatan "Kredit" dalam laporan:**  
Kredit = transaksi dengan `status = 'pending'` (belum bayar). Nilainya diambil dari `transactions.grand_total`, bukan dari tabel payments.

---

## 2. Laporan Jihans

### 2.1 Route

File: `routes/jihans.php` — tambahkan dalam group middleware yang sudah ada:

```php
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/',           [\App\Http\Controllers\Jihans\ReportController::class, 'index'])->name('index');
    Route::get('/laci',       [\App\Http\Controllers\Jihans\ReportController::class, 'laci'])->name('laci');
    Route::get('/harian',     [\App\Http\Controllers\Jihans\ReportController::class, 'harian'])->name('harian');
    Route::get('/mingguan',   [\App\Http\Controllers\Jihans\ReportController::class, 'mingguan'])->name('mingguan');
    Route::get('/bulanan',    [\App\Http\Controllers\Jihans\ReportController::class, 'bulanan'])->name('bulanan');
    Route::get('/pelanggan',  [\App\Http\Controllers\Jihans\ReportController::class, 'pelanggan'])->name('pelanggan');
});
```

Route names akan menjadi: `jihans.reports.laci`, `jihans.reports.harian`, dll.

### 2.2 Controller

File: `app/Http/Controllers/Jihans/ReportController.php`

```php
namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Query helper — semua laporan laci/harian/mingguan/bulanan pakai ini
    private function buildSummaryQuery(Request $request, ?int $kasirId = null)
    {
        return DB::table('jihans_transactions as t')
            ->leftJoin('jihans_transaction_payments as p', 'p.transaction_id', '=', 't.id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('t.status', '!=', 'cancelled')
            ->when($kasirId, fn($q) => $q->where('t.created_by', $kasirId))
            ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to));
    }

    public function index()
    {
        return view('jihans.reports.index');
    }

    public function laci(Request $request)
    {
        // Laci kasir: hanya data kasir yang sedang login, group by date
        $rows = $this->buildSummaryQuery($request, auth()->id())
            ->selectRaw("
                t.date,
                COUNT(DISTINCT t.id)                                                      as jumlah_transaksi,
                SUM(DISTINCT t.grand_total)                                               as total_transaksi,
                SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END)          as tunai,
                SUM(CASE WHEN t.status = 'pending'     THEN t.grand_total ELSE 0 END)     as kredit,
                SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END)          as kartu_debit,
                SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END)          as kartu_kredit
            ")
            ->groupBy('t.date')
            ->orderBy('t.date', 'desc')
            ->paginate(30)
            ->withQueryString();

        return view('jihans.reports.laci', compact('rows'));
    }

    public function harian(Request $request)
    {
        // Harian: semua kasir, group by date
        $rows = $this->buildSummaryQuery($request)
            ->selectRaw("
                t.date,
                COUNT(DISTINCT t.id)                                                      as jumlah_transaksi,
                SUM(DISTINCT t.grand_total)                                               as total_transaksi,
                SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END)          as tunai,
                SUM(CASE WHEN t.status = 'pending'     THEN t.grand_total ELSE 0 END)     as kredit,
                SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END)          as kartu_debit,
                SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END)          as kartu_kredit
            ")
            ->groupBy('t.date')
            ->orderBy('t.date', 'desc')
            ->paginate(30)
            ->withQueryString();

        return view('jihans.reports.harian', compact('rows'));
    }

    public function mingguan(Request $request)
    {
        // Mingguan: group by YEARWEEK
        $rows = $this->buildSummaryQuery($request)
            ->selectRaw("
                YEARWEEK(t.date, 1)                                                       as tahun_minggu,
                MIN(t.date)                                                                as minggu_mulai,
                MAX(t.date)                                                                as minggu_akhir,
                COUNT(DISTINCT t.id)                                                      as jumlah_transaksi,
                SUM(DISTINCT t.grand_total)                                               as total_transaksi,
                SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END)          as tunai,
                SUM(CASE WHEN t.status = 'pending'     THEN t.grand_total ELSE 0 END)     as kredit,
                SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END)          as kartu_debit,
                SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END)          as kartu_kredit
            ")
            ->groupByRaw('YEARWEEK(t.date, 1)')
            ->orderByRaw('YEARWEEK(t.date, 1) DESC')
            ->paginate(20)
            ->withQueryString();

        return view('jihans.reports.mingguan', compact('rows'));
    }

    public function bulanan(Request $request)
    {
        // Bulanan: group by YEAR + MONTH
        $rows = $this->buildSummaryQuery($request)
            ->selectRaw("
                DATE_FORMAT(t.date, '%Y-%m')                                              as tahun_bulan,
                DATE_FORMAT(t.date, '%M %Y')                                              as label_bulan,
                COUNT(DISTINCT t.id)                                                      as jumlah_transaksi,
                SUM(DISTINCT t.grand_total)                                               as total_transaksi,
                SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END)          as tunai,
                SUM(CASE WHEN t.status = 'pending'     THEN t.grand_total ELSE 0 END)     as kredit,
                SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END)          as kartu_debit,
                SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END)          as kartu_kredit
            ")
            ->groupByRaw("DATE_FORMAT(t.date, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(t.date, '%Y-%m') DESC")
            ->paginate(12)
            ->withQueryString();

        return view('jihans.reports.bulanan', compact('rows'));
    }

    public function pelanggan(Request $request)
    {
        $rows = DB::table('jihans_transactions as t')
            ->leftJoin('jihans_transaction_payments as p', 'p.transaction_id', '=', 't.id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('t.status', '!=', 'cancelled')
            ->whereNotNull('t.customer_name')
            ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to))
            ->when($request->search, fn($q) => $q->where('t.customer_name', 'like', '%'.$request->search.'%'))
            ->selectRaw("
                t.customer_name                                                            as pelanggan,
                MIN(t.date)                                                                as tanggal_pertama,
                MAX(t.date)                                                                as tanggal_terakhir,
                COUNT(DISTINCT t.id)                                                      as jumlah_transaksi,
                SUM(DISTINCT t.grand_total)                                               as total_transaksi,
                SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END)          as tunai,
                SUM(CASE WHEN t.status = 'pending'     THEN t.grand_total ELSE 0 END)     as kredit,
                SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END)          as kartu_debit,
                SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END)          as kartu_kredit
            ")
            ->groupBy('t.customer_name')
            ->orderBy('total_transaksi', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('jihans.reports.pelanggan', compact('rows'));
    }
}
```

### 2.3 Views Jihans

Semua view extends `layouts.jihans`. Buat file berikut:

- `resources/views/jihans/reports/index.blade.php` — dashboard navigasi laporan (card link ke tiap laporan)
- `resources/views/jihans/reports/laci.blade.php`
- `resources/views/jihans/reports/harian.blade.php`
- `resources/views/jihans/reports/mingguan.blade.php`
- `resources/views/jihans/reports/bulanan.blade.php`
- `resources/views/jihans/reports/pelanggan.blade.php`

**Struktur tabel yang sama untuk laci/harian/bulanan/mingguan:**

| Kolom Header | Field |
|---|---|
| Tanggal / Periode | `date` / `label_bulan` / range minggu |
| Jumlah Transaksi | `jumlah_transaksi` |
| Total Transaksi (Rp) | `total_transaksi` |
| Tunai | `tunai` |
| Kredit | `kredit` |
| Kartu Debit | `kartu_debit` |
| Kartu Kredit | `kartu_kredit` |

**Filter input** (form GET di tiap view):
- `date_from` (date input)
- `date_to` (date input)
- Tombol Reset filter

**Total row** di footer tabel: SUM setiap kolom numerik dari current page.

**Styling:** Gunakan pola Tailwind yang sama dengan view Jihans lainnya (bg-orange-800, rounded-xl, dll).

---

## 3. Laporan Hendhys

### 3.1 Route

File: `routes/hendhys.php` — tambahkan dalam group middleware yang sudah ada:

```php
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/',          [\App\Http\Controllers\Hendhys\ReportController::class, 'index'])->name('index');
    Route::get('/laci',      [\App\Http\Controllers\Hendhys\ReportController::class, 'laci'])->name('laci');
    Route::get('/harian',    [\App\Http\Controllers\Hendhys\ReportController::class, 'harian'])->name('harian');
    Route::get('/mingguan',  [\App\Http\Controllers\Hendhys\ReportController::class, 'mingguan'])->name('mingguan');
    Route::get('/bulanan',   [\App\Http\Controllers\Hendhys\ReportController::class, 'bulanan'])->name('bulanan');
    Route::get('/pelanggan', [\App\Http\Controllers\Hendhys\ReportController::class, 'pelanggan'])->name('pelanggan');
});
```

Route names: `hendhys.reports.laci`, `hendhys.reports.harian`, dll.

### 3.2 Controller

File: `app/Http/Controllers/Hendhys/ReportController.php`

Identik dengan `Jihans\ReportController` tapi:
- Source tabel: `hendhys_transactions`, `hendhys_transaction_payments`
- Method `buildSummaryQuery`: tambah filter `branch_id` untuk kasir cabang

```php
private function buildSummaryQuery(Request $request, ?int $kasirId = null)
{
    $user    = auth()->user();
    $isPusat = $user->branch->type === 'pusat';

    return DB::table('hendhys_transactions as t')
        ->leftJoin('hendhys_transaction_payments as p', 'p.transaction_id', '=', 't.id')
        ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
        ->where('t.status', '!=', 'cancelled')
        ->when(!$isPusat, fn($q) => $q->where('t.branch_id', $user->branch_id))
        ->when($kasirId, fn($q) => $q->where('t.created_by', $kasirId))
        ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
        ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to));
}
```

> **Catatan:** Cek apakah kolom `branch_id` ada di `hendhys_transactions`. Jika belum ada, skip filter branch untuk sementara dan catat sebagai known gap.

### 3.3 Views Hendhys

Extends `layouts.hendhys`. File yang dibuat:
- `resources/views/hendhys/reports/index.blade.php`
- `resources/views/hendhys/reports/laci.blade.php`
- `resources/views/hendhys/reports/harian.blade.php`
- `resources/views/hendhys/reports/mingguan.blade.php`
- `resources/views/hendhys/reports/bulanan.blade.php`
- `resources/views/hendhys/reports/pelanggan.blade.php`

Styling mengikuti Material Design 3 pattern yang sudah dipakai di Hendhys (primary-container, on-surface, surface-container-lowest, dll).

---

## 4. Faktur A5 Jihans

### 4.1 File

Ubah langsung: `resources/views/jihans/pos/receipt.blade.php`

### 4.2 Ukuran Halaman

```css
@page {
    size: A5 portrait;   /* 148mm × 210mm */
    margin: 8mm 10mm;
}

.page-wrapper {
    max-width: 148mm;
    /* hapus max-width: 210mm yang lama */
}
```

### 4.3 Header Baru

Layout: logo di kiri, blok teks di kanan logo (flex row, align-items: flex-start).

```
┌──────────────────────────────────────────┐
│  [LOGO 72×72]   FAKTUR PENJUALAN         │
│                 JIHAN'S FOOD             │
│                 MANUFACTURE FOR KEBAB    │
│                 & TORTILLA               │
│                 Jl. Beringin Pasar 7     │
│                                          │
│  ──────────────────────────────────────  │
│  No: JH-20260525-001   25 Mei 2026 10:30 │
└──────────────────────────────────────────┘
```

HTML struktur:
```html
<div class="invoice-header">
    <div class="brand-block">
        <img src="{{ asset('logo/jihans-logo.png') }}" class="brand-logo">
        <div class="brand-info">
            <div class="invoice-label">FAKTUR PENJUALAN</div>
            <h1>JIHAN'S FOOD</h1>
            <p>MANUFACTURE FOR KEBAB &amp; TORTILLA</p>
            <p>Jl. Beringin Pasar 7</p>
        </div>
    </div>
</div>
<div class="invoice-meta-bar">
    <span>No: {{ $transaction->transaction_number }}</span>
    <span>{{ Carbon::parse($transaction->date)->translatedFormat('d F Y') }} {{ $transaction->time }}</span>
</div>
```

### 4.4 Ukuran Font A5

Semua font dikecilkan agar konten muat di A5:
- Body: `11px`
- Table header: `9px`
- Table body: `10px`
- Grand total: `13px`
- Brand title: `16px`
- Invoice label: `14px font-weight: 900 letter-spacing: 2px`

### 4.5 Tombol Aksi

Pertahankan tombol Kembali + Cetak di `.action-bar` (tidak ikut print via `@media print { display: none }`).

---

## 5. Faktur A5 Hendhys

### 5.1 File Baru

`resources/views/hendhys/pos/invoice.blade.php`

Hendhys saat ini hanya punya `receipt.blade.php` (format thermal 380px). File baru ini adalah faktur A5 formal.

### 5.2 Route

Tambahkan di `routes/hendhys.php`:
```php
Route::get('pos/{transaction}/invoice', [\App\Http\Controllers\Hendhys\PosController::class, 'invoice'])
     ->name('pos.invoice');
```

### 5.3 Header Hendhys A5

```
┌──────────────────────────────────────────┐
│  [LOGO 72×72]   FAKTUR PENJUALAN         │
│                 HENDHYS BROWNIES         │
│                 {Nama Cabang}            │
│                 {Alamat Cabang}          │
└──────────────────────────────────────────┘
```

Nama dan alamat cabang diambil dari: `auth()->user()->branch->name` dan `auth()->user()->branch->address`.

Warna utama Hendhys: `#d97706` (amber-600), konsisten dengan pola yang ada.

### 5.4 PosController Method

Di `app/Http/Controllers/Hendhys/PosController.php`, tambahkan:

```php
public function invoice(\App\Models\HendhysTransaction $transaction)
{
    $transaction->load(['details.unit', 'payments.method', 'creator']);
    return view('hendhys.pos.invoice', compact('transaction'));
}
```

### 5.5 Isi Faktur Hendhys

Identik dengan Jihans A5 tapi dengan warna amber dan data dari `hendhys_transactions` + `hendhys_transaction_details`.

---

## 6. Navigasi Menu

Tambahkan link "Laporan" di sidebar/nav:

**Jihans** (`layouts/jihans.blade.php` atau partial nav):
```html
<a href="{{ route('jihans.reports.index') }}">Laporan</a>
```

**Hendhys** (`layouts/hendhys.blade.php` atau partial nav):
```html
<a href="{{ route('hendhys.reports.index') }}">Laporan</a>
```

---

## 7. Checklist Implementasi untuk Gemini

- [ ] Migration: `add_type_to_master_payment_methods`
- [ ] Jalankan `php artisan migrate`
- [ ] Update seeder payment methods dengan field `type`
- [ ] Buat `Jihans\ReportController` dengan 6 method
- [ ] Tambah 6 route di `routes/jihans.php`
- [ ] Buat 6 view Jihans reports (extends layouts.jihans)
- [ ] Buat `Hendhys\ReportController` dengan 6 method
- [ ] Tambah 6 route di `routes/hendhys.php`
- [ ] Buat 6 view Hendhys reports (extends layouts.hendhys)
- [ ] Update `resources/views/jihans/pos/receipt.blade.php` → A5 + header baru
- [ ] Buat `resources/views/hendhys/pos/invoice.blade.php` (A5 baru)
- [ ] Tambah method `invoice()` di `Hendhys\PosController`
- [ ] Tambah route `hendhys.pos.invoice`
- [ ] Tambah link Laporan di nav Jihans
- [ ] Tambah link Laporan di nav Hendhys

---

## 8. Catatan & Asumsi

- "Kredit" = `jihans_transactions.status = 'pending'`, bukan metode pembayaran tunai. Nilainya `grand_total`, bukan dari tabel payments.
- Kolom `branch_id` di `hendhys_transactions` — perlu dicek apakah ada. Jika tidak ada, Hendhys ReportController skip filter branch.
- Logo Jihans: `public/logo/jihans-logo.png` (sudah ada di receipt.blade.php).
- Logo Hendhys: gunakan placeholder atau `public/logo/hendhys-logo.png` jika ada.
- Laporan mingguan menggunakan `YEARWEEK(date, 1)` — mode 1 = minggu mulai Senin.
- Semua laporan menggunakan pagination, bukan single page dump.
- Tidak ada export Excel dalam spec ini — bisa ditambahkan sebagai iterasi berikutnya.

---

## 9. Testing Plan (untuk Claude setelah Gemini selesai)

### Migrasi & Setup
- [ ] `php artisan migrate:status` → semua Ran
- [ ] `master_payment_methods` memiliki kolom `type`

### Laporan Jihans
- [ ] GET `/jihans/reports` → 200 OK
- [ ] GET `/jihans/reports/laci` → tabel muncul, filter tanggal berfungsi
- [ ] GET `/jihans/reports/harian` → group by date benar
- [ ] GET `/jihans/reports/mingguan` → group by minggu benar
- [ ] GET `/jihans/reports/bulanan` → group by bulan benar
- [ ] GET `/jihans/reports/pelanggan` → group by customer, search berfungsi
- [ ] Kolom Tunai / Kredit / Kartu Debit / Kartu Kredit muncul di semua tabel
- [ ] Reset filter menghapus query string

### Laporan Hendhys
- [ ] Semua endpoint `/hendhys/reports/*` → 200 OK
- [ ] Data tidak tercampur dengan data Jihans

### Faktur A5 Jihans
- [ ] GET `/jihans/pos/{id}/receipt` → render A5, tidak overflow
- [ ] Header: logo kiri + "FAKTUR PENJUALAN" / "JIHAN'S FOOD" / "MANUFACTURE FOR KEBAB & TORTILLA" / "Jl. Beringin Pasar 7"
- [ ] Print preview browser menunjukkan ukuran A5

### Faktur A5 Hendhys
- [ ] GET `/hendhys/pos/{id}/invoice` → 200 OK
- [ ] Header menampilkan nama & alamat cabang yang benar
- [ ] Print preview A5
