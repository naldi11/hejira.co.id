# Prediksi Produksi Tortilla Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tambah fitur Prediksi Produksi Tortilla sehingga kasir Jihan's bisa input estimasi produksi di pagi hari, cetak faktur penjualan, lalu di-override oleh data aktual sore hari yang mengupdate stok.

**Architecture:** Tambah kolom `type` (`prediksi`/`aktual`) dan `overridden_at` ke tabel `jihans_tortilla_sessions`. Method `store()` existing tetap untuk aktual, tambah `storePrediksi()` yang skip StockService. Saat aktual disimpan, prediksi hari itu otomatis di-mark `overridden_at`.

**Tech Stack:** Laravel 13, Alpine.js, Blade, dompdf (sudah terpasang), CSS dot matrix print

---

## File Structure

| File | Aksi | Keterangan |
|---|---|---|
| `database/migrations/..._add_type_to_jihans_tortilla_sessions.php` | Create | Tambah kolom `type` + `overridden_at` |
| `app/Models/JihansTortillaSession.php` | Modify | Tambah fillable, casts, helper methods |
| `app/Http/Controllers/Jihans/TortillaProductionController.php` | Modify | Tambah `createPrediksi`, `storePrediksi`, `printFaktur`; update `store` |
| `routes/jihans.php` | Modify | Tambah 3 route baru sebelum resource route |
| `resources/views/jihans/tortilla/form.blade.php` | Modify | Support `$type` dan `$formAction` variable |
| `resources/views/jihans/tortilla/index.blade.php` | Modify | Tambah kolom Type + badge + tombol Prediksi Baru |
| `resources/views/jihans/tortilla/show.blade.php` | Modify | Tambah badge type + info overridden |
| `resources/views/jihans/tortilla/faktur-prediksi.blade.php` | Create | View cetak faktur untuk dot matrix |
| `resources/views/layouts/jihans.blade.php` | Modify | Tambah menu "Prediksi Produksi" di sidebar |

---

## Task 1: Migration — Tambah kolom `type` dan `overridden_at`

**Files:**
- Create: migration baru via artisan

- [ ] **Step 1: Buat migration**

```bash
php artisan make:migration add_type_to_jihans_tortilla_sessions_table
```

- [ ] **Step 2: Isi migration**

Buka file yang baru dibuat di `database/migrations/` dan isi:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            $table->enum('type', ['prediksi', 'aktual'])->default('aktual')->after('session_number');
            $table->timestamp('overridden_at')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            $table->dropColumn(['type', 'overridden_at']);
        });
    }
};
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

Expected output: `... add_type_to_jihans_tortilla_sessions_table ... DONE`

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: migration tambah kolom type dan overridden_at ke jihans_tortilla_sessions"
```

---

## Task 2: Update Model `JihansTortillaSession`

**Files:**
- Modify: `app/Models/JihansTortillaSession.php`

- [ ] **Step 1: Update model**

Ganti seluruh isi `app/Models/JihansTortillaSession.php` dengan:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JihansTortillaSession extends Model
{
    protected $table = 'jihans_tortilla_sessions';

    protected $fillable = [
        'session_number', 'type', 'overridden_at', 'date', 'notes', 'created_by',
        'tb_product_id', 'ts_product_id', 'tk_product_id', 'tc_product_id', 'kribab_product_id',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'overridden_at' => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(JihansTortillaSessionDetail::class, 'session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPrediksi(): bool
    {
        return $this->type === 'prediksi';
    }

    public function isOverridden(): bool
    {
        return $this->overridden_at !== null;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/JihansTortillaSession.php
git commit -m "feat: update model JihansTortillaSession untuk support type prediksi/aktual"
```

---

## Task 3: Update Routes

**Files:**
- Modify: `routes/jihans.php`

- [ ] **Step 1: Tambah 3 route baru SEBELUM resource route**

Buka `routes/jihans.php`. Cari baris:
```php
Route::resource('tortilla', \App\Http\Controllers\Jihans\TortillaProductionController::class)->except(['edit', 'update', 'destroy']);
```

Tambahkan 3 baris ini DI ATAS baris tersebut:

```php
Route::get('tortilla/prediksi/create', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'createPrediksi'])->name('tortilla.prediksi.create');
Route::post('tortilla/prediksi', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'storePrediksi'])->name('tortilla.prediksi.store');
Route::get('tortilla/{tortilla}/faktur', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'printFaktur'])->name('tortilla.faktur');
```

> **PENTING:** Harus di atas resource route agar `prediksi/create` tidak ditangkap sebagai `{tortilla}` parameter.

- [ ] **Step 2: Commit**

```bash
git add routes/jihans.php
git commit -m "feat: tambah routes prediksi dan faktur tortilla"
```

---

## Task 4: Update Controller

**Files:**
- Modify: `app/Http/Controllers/Jihans/TortillaProductionController.php`

- [ ] **Step 1: Update method `store()` — tambah override prediksi setelah simpan aktual**

Di dalam `store()`, cari baris setelah session dibuat:
```php
$session = JihansTortillaSession::create([
    'session_number'    => ...
```

Tambahkan baris `'type' => 'aktual',` di dalam array create, dan **setelah** blok `DB::transaction`, tambahkan kode override prediksi.

Ubah `DB::transaction(function () use ($request) {` menjadi `DB::transaction(function () use ($request, &$session) {` dan ganti isi create session + tambah override:

Di dalam transaction, ganti:
```php
$session = JihansTortillaSession::create([
    'session_number'    => $this->numbers->generateYearly('JHS-TOR', 'jihans_tortilla_sessions', 'session_number'),
    'date'              => $request->date,
    'notes'             => $request->notes,
    'created_by'        => auth()->id(),
    'tb_product_id'     => $config->tb_product_id,
    'ts_product_id'     => $config->ts_product_id,
    'tk_product_id'     => $config->tk_product_id,
    'tc_product_id'     => $config->tc_product_id,
    'kribab_product_id' => $config->kribab_product_id,
]);
```

Menjadi:
```php
$session = JihansTortillaSession::create([
    'session_number'    => $this->numbers->generateYearly('JHS-TOR', 'jihans_tortilla_sessions', 'session_number'),
    'type'              => 'aktual',
    'date'              => $request->date,
    'notes'             => $request->notes,
    'created_by'        => auth()->id(),
    'tb_product_id'     => $config->tb_product_id,
    'ts_product_id'     => $config->ts_product_id,
    'tk_product_id'     => $config->tk_product_id,
    'tc_product_id'     => $config->tc_product_id,
    'kribab_product_id' => $config->kribab_product_id,
]);

// Override prediksi hari yang sama jika ada
JihansTortillaSession::where('type', 'prediksi')
    ->whereDate('date', $request->date)
    ->whereNull('overridden_at')
    ->update(['overridden_at' => now()]);
```

- [ ] **Step 2: Tambah method `createPrediksi()` setelah method `create()`**

```php
public function createPrediksi()
{
    // Cek apakah prediksi atau aktual hari ini sudah ada
    $existingToday = JihansTortillaSession::whereDate('date', today())
        ->whereIn('type', ['prediksi', 'aktual'])
        ->first();

    $warning = null;
    if ($existingToday) {
        $warning = $existingToday->type === 'aktual'
            ? 'Aktual produksi hari ini sudah diinput. Prediksi tidak diperlukan lagi.'
            : 'Prediksi hari ini sudah ada. Menyimpan ulang akan gagal.';
    }

    $karyawans = Karyawan::where('entity_scope', 'jihans')
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('jihans.tortilla.form', [
        'karyawans'  => $karyawans,
        'type'       => 'prediksi',
        'formAction' => route('jihans.tortilla.prediksi.store'),
        'warning'    => $warning,
    ]);
}
```

- [ ] **Step 3: Tambah method `storePrediksi()` setelah `createPrediksi()`**

```php
public function storePrediksi(Request $request)
{
    $request->validate([
        'date'                  => 'required|date',
        'notes'                 => 'nullable|string',
        'details'               => 'required|array|min:1',
        'details.*.karyawan_id' => 'required|exists:master_karyawan,id',
        'details.*.tb_qty'      => 'required|integer|min:0',
        'details.*.ts_qty'      => 'required|integer|min:0',
        'details.*.tk_qty'      => 'required|integer|min:0',
        'details.*.tc_qty'      => 'required|integer|min:0',
        'details.*.kribab_qty'  => 'required|integer|min:0',
    ]);

    // Tolak jika sudah ada prediksi atau aktual hari ini
    $existing = JihansTortillaSession::whereDate('date', $request->date)
        ->whereIn('type', ['prediksi', 'aktual'])
        ->first();

    if ($existing) {
        $msg = $existing->type === 'aktual'
            ? 'Aktual produksi tanggal ini sudah ada. Prediksi tidak bisa dibuat.'
            : 'Prediksi untuk tanggal ini sudah ada.';
        return back()->withInput()->withErrors(['date' => $msg]);
    }

    $totalQtyAll = collect($request->details)->sum(function ($d) {
        return ($d['tb_qty'] ?? 0) + ($d['ts_qty'] ?? 0) + ($d['tk_qty'] ?? 0)
             + ($d['tc_qty'] ?? 0) + ($d['kribab_qty'] ?? 0);
    });

    if ($totalQtyAll <= 0) {
        return back()->withInput()->withErrors(['details' => 'Minimal ada 1 karyawan dengan jumlah produksi > 0.']);
    }

    $session = null;

    DB::transaction(function () use ($request, &$session) {
        $config = JihansProductionConfig::current();

        $session = JihansTortillaSession::create([
            'session_number'    => $this->numbers->generateYearly('JHS-TOR', 'jihans_tortilla_sessions', 'session_number'),
            'type'              => 'prediksi',
            'date'              => $request->date,
            'notes'             => $request->notes,
            'created_by'        => auth()->id(),
            'tb_product_id'     => $config->tb_product_id,
            'ts_product_id'     => $config->ts_product_id,
            'tk_product_id'     => $config->tk_product_id,
            'tc_product_id'     => $config->tc_product_id,
            'kribab_product_id' => $config->kribab_product_id,
        ]);

        foreach ($request->details as $detail) {
            $session->details()->create([
                'karyawan_id' => $detail['karyawan_id'],
                'tb_qty'      => $detail['tb_qty'],
                'ts_qty'      => $detail['ts_qty'],
                'tk_qty'      => $detail['tk_qty'],
                'tc_qty'      => $detail['tc_qty'],
                'kribab_qty'  => $detail['kribab_qty'],
            ]);
        }

        $this->logger->log('create', 'jihans.tortilla', "Input prediksi produksi tortilla: {$session->session_number}", $session);
    });

    // Redirect ke halaman faktur setelah simpan
    return redirect()->route('jihans.tortilla.faktur', $session)
        ->with('success', 'Prediksi berhasil disimpan. Cetak faktur di bawah ini.');
}
```

- [ ] **Step 4: Tambah method `printFaktur()` setelah `storePrediksi()`**

```php
public function printFaktur(JihansTortillaSession $tortilla)
{
    if (!$tortilla->isPrediksi()) {
        return redirect()->route('jihans.tortilla.show', $tortilla)
            ->withErrors(['type' => 'Faktur hanya tersedia untuk sesi prediksi.']);
    }

    $tortilla->load(['details.karyawan', 'creator']);

    // Hitung total per varian
    $totals = [
        'tb'     => $tortilla->details->sum('tb_qty'),
        'ts'     => $tortilla->details->sum('ts_qty'),
        'tk'     => $tortilla->details->sum('tk_qty'),
        'tc'     => $tortilla->details->sum('tc_qty'),
        'kribab' => $tortilla->details->sum('kribab_qty'),
    ];

    $variants = [
        'tb'     => 'Tortilla Besar',
        'ts'     => 'Tortilla Sedang',
        'tk'     => 'Tortilla Kecil',
        'tc'     => 'Tortilla Catering',
        'kribab' => 'Kribab',
    ];

    return view('jihans.tortilla.faktur-prediksi', compact('tortilla', 'totals', 'variants'));
}
```

- [ ] **Step 5: Update method `create()` — tambah default variable untuk backward compat**

Ganti:
```php
public function create()
{
    $karyawans = Karyawan::where('entity_scope', 'jihans')
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('jihans.tortilla.form', compact('karyawans'));
}
```

Menjadi:
```php
public function create()
{
    $karyawans = Karyawan::where('entity_scope', 'jihans')
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('jihans.tortilla.form', [
        'karyawans'  => $karyawans,
        'type'       => 'aktual',
        'formAction' => route('jihans.tortilla.store'),
        'warning'    => null,
    ]);
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Jihans/TortillaProductionController.php
git commit -m "feat: tambah createPrediksi, storePrediksi, printFaktur di TortillaProductionController"
```

---

## Task 5: Update View Form

**Files:**
- Modify: `resources/views/jihans/tortilla/form.blade.php`

- [ ] **Step 1: Update baris 1-4 (title & action) dan tambah hidden input + warning banner**

Ganti bagian atas file (baris 1-24):
```blade
@extends('layouts.jihans')
@section('title', $type === 'prediksi' ? 'Input Prediksi Produksi Tortilla' : 'Input Produksi Tortilla')
@section('page-title', $type === 'prediksi' ? 'Prediksi Produksi Tortilla' : 'Input Produksi Tortilla')

@section('content')
<div class="space-y-6" x-data="productionForm()">

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="flex gap-3">
            <span class="material-symbols-outlined text-red-500 text-[20px] shrink-0 mt-0.5">error</span>
            <div>
                <p class="text-sm font-bold text-red-700 mb-1">Terdapat kesalahan:</p>
                <ul class="text-sm text-red-600 list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if($warning ?? null)
    <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-800 px-5 py-4 rounded-2xl shadow-sm">
        <span class="material-symbols-outlined text-amber-500 text-[20px]">warning</span>
        <p class="text-sm font-semibold">{{ $warning }}</p>
    </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="space-y-6">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
```

- [ ] **Step 2: Update tombol submit di bagian bawah form**

Cari tombol submit (biasanya ada teks "Simpan Data Produksi"). Ganti dengan:
```blade
<button type="submit"
        class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-xl text-sm font-black hover:bg-orange-700 transition-all shadow-lg shadow-orange-600/20 active:scale-[0.98]">
    <span class="material-symbols-outlined text-[18px]">save</span>
    {{ $type === 'prediksi' ? 'Simpan Prediksi & Cetak Faktur' : 'Simpan Data Produksi' }}
</button>
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/jihans/tortilla/form.blade.php
git commit -m "feat: update form produksi tortilla support mode prediksi/aktual"
```

---

## Task 6: Update View Index

**Files:**
- Modify: `resources/views/jihans/tortilla/index.blade.php`

- [ ] **Step 1: Tambah tombol "Prediksi Baru" di header**

Cari tombol `Input Produksi Baru` di header (sekitar baris 34-38), tambahkan tombol prediksi di sebelah kirinya:

```blade
<a href="{{ route('jihans.tortilla.prediksi.create') }}"
   class="inline-flex items-center gap-2 px-5 py-2.5 bg-yellow-500 text-white rounded-xl text-sm font-bold hover:bg-yellow-600 transition-all shadow-lg shadow-yellow-500/20 active:scale-[0.98]">
    <span class="material-symbols-outlined text-[18px]">edit_note</span>
    Prediksi Baru
</a>
```

- [ ] **Step 2: Tambah kolom "Type" di header tabel**

Cari baris `<th` untuk header tabel, tambahkan kolom Type setelah kolom "No. Sesi":

```blade
<th class="px-6 py-4 font-black text-slate-500 text-xs uppercase tracking-wider">Type</th>
```

- [ ] **Step 3: Tambah badge type di setiap baris tabel**

Cari baris yang menampilkan `session_number`, tambahkan cell Type setelahnya:

```blade
<td class="px-6 py-4">
    @if($session->type === 'prediksi')
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200">
            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 inline-block"></span>
            Prediksi
            @if($session->isOverridden())
                <span class="ml-1 text-yellow-400 font-normal">(Digantikan)</span>
            @endif
        </span>
    @else
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
            Aktual
        </span>
    @endif
</td>
```

- [ ] **Step 4: Tambah tombol "Faktur" di kolom Aksi untuk sesi prediksi**

Cari kolom Aksi (tombol Detail), tambahkan tombol Faktur sebelumnya:

```blade
@if($session->isPrediksi())
<a href="{{ route('jihans.tortilla.faktur', $session) }}"
   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-xs font-bold hover:bg-yellow-100 transition-all">
    <span class="material-symbols-outlined text-[14px]">receipt</span>
    Faktur
</a>
@endif
```

- [ ] **Step 5: Update colspan di empty state dari 6 menjadi 7**

Cari `colspan="6"` di baris empty state dan ubah menjadi `colspan="7"`.

- [ ] **Step 6: Commit**

```bash
git add resources/views/jihans/tortilla/index.blade.php
git commit -m "feat: update index tortilla tampilkan badge type dan tombol faktur prediksi"
```

---

## Task 7: Update View Show

**Files:**
- Modify: `resources/views/jihans/tortilla/show.blade.php`

- [ ] **Step 1: Tambah badge type di header halaman**

Cari judul/header halaman show, tambahkan badge setelah session_number:

```blade
@if($tortilla->isPrediksi())
<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200">
    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 inline-block"></span>
    PREDIKSI
    @if($tortilla->isOverridden())
        — Digantikan pada {{ $tortilla->overridden_at->format('d/m/Y H:i') }}
    @endif
</span>
@else
<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
    <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
    AKTUAL
</span>
@endif
```

- [ ] **Step 2: Tambah tombol "Cetak Faktur" di action buttons (hanya untuk prediksi)**

Cari tombol kembali/action di halaman show, tambahkan:

```blade
@if($tortilla->isPrediksi())
<a href="{{ route('jihans.tortilla.faktur', $tortilla) }}"
   class="inline-flex items-center gap-2 px-5 py-2.5 bg-yellow-500 text-white rounded-xl text-sm font-bold hover:bg-yellow-600 transition-all shadow-lg shadow-yellow-500/20">
    <span class="material-symbols-outlined text-[18px]">receipt</span>
    Cetak Faktur
</a>
@endif
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/jihans/tortilla/show.blade.php
git commit -m "feat: update show tortilla tampilkan badge prediksi/aktual dan tombol cetak faktur"
```

---

## Task 8: Buat View Faktur Prediksi

**Files:**
- Create: `resources/views/jihans/tortilla/faktur-prediksi.blade.php`

- [ ] **Step 1: Buat file faktur**

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur Prediksi - {{ $tortilla->session_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: 9.5in auto;
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            background: #fff;
            color: #000;
            font-size: 13px;
            line-height: 1.4;
        }

        .page-wrapper {
            width: 8.2in;
            padding: 9mm 6mm 6mm 6mm;
            margin: 0;
        }

        .action-bar {
            max-width: 100%;
            margin: 10px auto;
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 8px 0;
            background: #f3f4f6;
            border-radius: 6px;
        }

        .btn {
            padding: 5px 15px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-back { background: #e5e7eb; color: #374151; }
        .btn-print { background: #c2410c; color: white; }

        .header-section {
            width: 100%;
            display: table;
            margin-bottom: 8px;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .invoice-title {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .brand-name {
            font-size: 13px;
            font-weight: bold;
        }

        .brand-sub {
            font-size: 10px;
            font-weight: bold;
        }

        .brand-detail {
            font-size: 10px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .meta-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.items-table th {
            padding: 4px 6px;
            font-size: 12px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
            text-align: left;
        }

        table.items-table td {
            padding: 5px 6px;
            font-size: 12px;
        }

        table.items-table td.text-right,
        table.items-table th.text-right { text-align: right; }

        .total-row td {
            border-top: 1px solid #000;
            font-weight: bold;
            padding-top: 5px;
        }

        .footer-section {
            margin-top: 10px;
            width: 100%;
            display: table;
        }

        .footer-left, .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .signature-box {
            text-align: center;
        }

        .signature-space {
            height: 45px;
        }

        .prediksi-banner {
            text-align: center;
            margin-top: 8px;
            font-weight: bold;
            font-size: 11px;
            border: 1px dashed #000;
            padding: 3px 0;
            letter-spacing: 1px;
        }

        @media print {
            .action-bar { display: none !important; }
            body { background: white; }
            .page-wrapper { margin: 0; box-shadow: none; width: 8.2in; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <a href="{{ route('jihans.tortilla.index') }}" class="btn btn-back">← Kembali</a>
    <button onclick="window.print()" class="btn btn-print">🖨 Cetak Faktur</button>
</div>

<div class="page-wrapper">

    {{-- HEADER --}}
    <div class="header-section">
        <div class="header-left">
            <div class="invoice-title">FAKTUR PREDIKSI PRODUKSI</div>
            <div class="brand-name">JIHAAN'S FOOD</div>
            <div class="brand-sub">MANUFACTURE FOR KEBAB &amp; TORTILLA</div>
            <div class="brand-detail">JL. Beringin Pasar 7</div>
            <div class="brand-detail">081362148090 - 085373736060</div>
        </div>
        <div class="header-right">
            <table class="meta-table">
                <tr>
                    <td>No. Sesi</td>
                    <td>: {{ $tortilla->session_number }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>: {{ $tortilla->date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Dibuat oleh</td>
                    <td>: {{ strtoupper($tortilla->creator->name ?? 'KASIR') }}</td>
                </tr>
                @if($tortilla->notes)
                <tr>
                    <td>Catatan</td>
                    <td>: {{ $tortilla->notes }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <div style="border-top: 2px solid #000; margin-bottom: 4px;"></div>

    {{-- ITEMS TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">No.</th>
                <th style="width: 60%;">Nama Produk</th>
                <th class="text-right" style="width: 20%;">Qty Prediksi</th>
                <th class="text-right" style="width: 14%;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; $grandTotal = 0; @endphp
            @foreach($variants as $key => $label)
            @if($totals[$key] > 0)
            @php $grandTotal += $totals[$key]; @endphp
            <tr>
                <td>{{ $no++ }}</td>
                <td style="font-weight: bold;">{{ $label }}</td>
                <td class="text-right font-bold">{{ $totals[$key] }}</td>
                <td class="text-right">Pcs</td>
            </tr>
            @endif
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align: right; padding-right: 10px;">TOTAL</td>
                <td class="text-right">{{ $grandTotal }}</td>
                <td class="text-right">Pcs</td>
            </tr>
        </tbody>
    </table>

    {{-- FOOTER --}}
    <div class="footer-section" style="margin-top: 14px;">
        <div class="footer-left">
            <div class="signature-box">
                <div>Dibuat oleh,</div>
                <div class="signature-space"></div>
                <div>( ................ )</div>
            </div>
        </div>
        <div class="footer-right">
            <div class="signature-box">
                <div>Penerima,</div>
                <div class="signature-space"></div>
                <div>( ................ )</div>
            </div>
        </div>
    </div>

    <div class="prediksi-banner">
        *** DATA PREDIKSI — BELUM FINAL — AKAN DIPERBARUI SETELAH PRODUKSI SELESAI ***
    </div>

</div>

<script>
    window.onload = function () {
        setTimeout(function () { window.print(); }, 600);
    };
</script>
</body>
</html>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/jihans/tortilla/faktur-prediksi.blade.php
git commit -m "feat: buat view faktur prediksi produksi tortilla untuk dot matrix"
```

---

## Task 9: Update Sidebar Layout

**Files:**
- Modify: `resources/views/layouts/jihans.blade.php`

- [ ] **Step 1: Tambah menu Prediksi Produksi di sidebar**

Cari baris ini di `resources/views/layouts/jihans.blade.php`:
```blade
<a href="{{ route('jihans.tortilla.index') }}"
   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('jihans.tortilla.*') ? 'bg-orange-800 shadow-md text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50 hover:text-white' }}">
    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
    <span class="text-sm">Produksi Tortilla</span>
</a>
```

Ganti dengan:
```blade
<a href="{{ route('jihans.tortilla.prediksi.create') }}"
   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('jihans.tortilla.prediksi.*') ? 'bg-orange-800 shadow-md text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50 hover:text-white' }}">
    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
    <span class="text-sm">Prediksi Produksi</span>
</a>

<a href="{{ route('jihans.tortilla.index') }}"
   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('jihans.tortilla.*') && !request()->routeIs('jihans.tortilla.prediksi.*') ? 'bg-orange-800 shadow-md text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50 hover:text-white' }}">
    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
    <span class="text-sm">Aktual Produksi</span>
</a>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/layouts/jihans.blade.php
git commit -m "feat: tambah menu Prediksi Produksi dan rename menu Aktual di sidebar Jihan's"
```

---

## Task 10: Push & Verifikasi

- [ ] **Step 1: Push ke remote**

```bash
git push origin main
```

- [ ] **Step 2: Verifikasi manual**

1. Login sebagai `kasir_jihans`
2. Sidebar → **Prediksi Produksi** → isi form → klik "Simpan Prediksi & Cetak Faktur"
3. Faktur terbuka → dialog cetak muncul otomatis → cetak ke dot matrix
4. Login sebagai `admin_jihans` → sidebar → **Aktual Produksi** → isi data aktual hari yang sama → simpan
5. Cek halaman index: prediksi lama badge 🟡 dengan label "Digantikan", aktual baru badge 🟢
6. Pastikan stok Jihans bertambah setelah aktual disimpan (bukan setelah prediksi)
