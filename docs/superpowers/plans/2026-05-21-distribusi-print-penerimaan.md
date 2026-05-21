# Distribusi Print + Penerimaan Cabang Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tambah cetak faktur di branch request, perbaiki surat jalan untuk distribusi manual, dan buat form penerimaan cabang yang lengkap dengan qty aktual, foto opsional, catatan, dan kredit stok sesuai qty diterima.

**Architecture:** Tiga area perubahan independen yang berurutan: (1) DB + model untuk data penerimaan, (2) controller enhancements, (3) view updates. Data penerimaan disimpan di kolom baru pada tabel yang sudah ada — tidak ada tabel baru, cukup kolom tambahan.

**Tech Stack:** Laravel 13, Blade, Alpine.js v3, Tailwind CSS (Material Design tokens), MySQL

---

## File Map

| File | Action | Purpose |
|---|---|---|
| `database/migrations/2026_05_21_000010_add_receive_fields_to_hendhys_transfer_to_branch.php` | Create | `receive_notes`, `receive_photo` ke transfer header |
| `database/migrations/2026_05_21_000011_add_received_quantity_to_hendhys_transfer_to_branch_details.php` | Create | `received_quantity` per detail |
| `app/Models/HendhysTransferToBranch.php` | Modify | Tambah `receive_notes`, `receive_photo` ke `$fillable` |
| `app/Models/HendhysTransferToBranchDetail.php` | Modify | Tambah `received_quantity` ke `$fillable` |
| `app/Http/Controllers/Hendhys/TransferToBranchController.php` | Modify | Tambah `showReceiveForm()`, perbarui `receive()` |
| `routes/hendhys.php` | Modify | Tambah `GET /transfer-to-branch/{id}/receive` |
| `resources/views/hendhys/transfer-to-branch/receive.blade.php` | Create | Form penerimaan cabang |
| `resources/views/hendhys/transfer-to-branch/show.blade.php` | Modify | Fix manual distribution (no request_id), update tombol terima ke GET |
| `resources/views/hendhys/branch-requests/show.blade.php` | Modify | Tambah tombol cetak faktur + print CSS |

---

## Task 1: Database Migrations

**Files:**
- Create: `database/migrations/2026_05_21_000010_add_receive_fields_to_hendhys_transfer_to_branch.php`
- Create: `database/migrations/2026_05_21_000011_add_received_quantity_to_hendhys_transfer_to_branch_details.php`

- [ ] **Step 1: Buat migration untuk transfer header**

```php
<?php
// database/migrations/2026_05_21_000010_add_receive_fields_to_hendhys_transfer_to_branch.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('hendhys_transfer_to_branch', function (Blueprint $table) {
            $table->text('receive_notes')->nullable()->after('notes');
            $table->string('receive_photo', 255)->nullable()->after('receive_notes');
        });
    }
    public function down(): void {
        Schema::table('hendhys_transfer_to_branch', function (Blueprint $table) {
            $table->dropColumn(['receive_notes', 'receive_photo']);
        });
    }
};
```

- [ ] **Step 2: Buat migration untuk detail quantity diterima**

```php
<?php
// database/migrations/2026_05_21_000011_add_received_quantity_to_hendhys_transfer_to_branch_details.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('hendhys_transfer_to_branch_details', function (Blueprint $table) {
            $table->decimal('received_quantity', 15, 3)->nullable()->after('quantity');
        });
    }
    public function down(): void {
        Schema::table('hendhys_transfer_to_branch_details', function (Blueprint $table) {
            $table->dropColumn('received_quantity');
        });
    }
};
```

- [ ] **Step 3: Jalankan migrations**

```bash
php artisan migrate
```

Expected:
```
2026_05_21_000010_add_receive_fields_to_hendhys_transfer_to_branch ......... DONE
2026_05_21_000011_add_received_quantity_to_hendhys_transfer_to_branch_details DONE
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_21_000010_add_receive_fields_to_hendhys_transfer_to_branch.php database/migrations/2026_05_21_000011_add_received_quantity_to_hendhys_transfer_to_branch_details.php
git commit -m "feat: add receive_notes, receive_photo, received_quantity to transfer tables"
```

---

## Task 2: Update Models

**Files:**
- Modify: `app/Models/HendhysTransferToBranch.php`
- Modify: `app/Models/HendhysTransferToBranchDetail.php`

- [ ] **Step 1: Tambah kolom baru ke fillable HendhysTransferToBranch**

Ganti `$fillable` array dari:
```php
protected $fillable = [
    'transfer_number', 'request_id', 'branch_id', 'date', 
    'status', 'notes', 'created_by', 'received_by'
];
```
Menjadi:
```php
protected $fillable = [
    'transfer_number', 'request_id', 'branch_id', 'date',
    'status', 'notes', 'receive_notes', 'receive_photo',
    'created_by', 'received_by'
];
```

- [ ] **Step 2: Tambah received_quantity ke fillable HendhysTransferToBranchDetail**

Ganti `$fillable` dari:
```php
protected $fillable = [
    'transfer_id', 'product_id', 'quantity', 'unit_id'
];
```
Menjadi:
```php
protected $fillable = [
    'transfer_id', 'product_id', 'quantity', 'received_quantity', 'unit_id'
];
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/HendhysTransferToBranch.php app/Models/HendhysTransferToBranchDetail.php
git commit -m "feat: add receive fields to Transfer model fillables"
```

---

## Task 3: Routes + Controller — showReceiveForm() dan receive() baru

**Files:**
- Modify: `routes/hendhys.php`
- Modify: `app/Http/Controllers/Hendhys/TransferToBranchController.php`

- [ ] **Step 1: Tambah GET route untuk form penerimaan di routes/hendhys.php**

Temukan blok transfer-to-branch routes (sekitar):
```php
Route::resource('transfer-to-branch', TransferToBranchController::class)->except(['edit', 'update', 'destroy']);
Route::post('transfer-to-branch/{transfer_to_branch}/receive', [TransferToBranchController::class, 'receive'])->name('transfer-to-branch.receive');
```

Ganti dengan:
```php
Route::resource('transfer-to-branch', TransferToBranchController::class)->except(['edit', 'update', 'destroy']);
Route::get('transfer-to-branch/{transfer_to_branch}/receive', [TransferToBranchController::class, 'showReceiveForm'])->name('transfer-to-branch.receive-form');
Route::post('transfer-to-branch/{transfer_to_branch}/receive', [TransferToBranchController::class, 'receive'])->name('transfer-to-branch.receive');
```

- [ ] **Step 2: Tambah method showReceiveForm() ke TransferToBranchController**

Tambahkan method ini SEBELUM `receive()`:

```php
public function showReceiveForm(HendhysTransferToBranch $transferToBranch)
{
    $user = auth()->user();

    if ($user->branch->type !== 'cabang') {
        abort(403, 'Hanya cabang yang dapat mengakses halaman ini.');
    }
    if ($transferToBranch->branch_id !== $user->branch_id) {
        abort(403, 'Akses ditolak.');
    }
    if ($transferToBranch->status !== 'sent') {
        return redirect()->route('hendhys.transfer-to-branch.show', $transferToBranch->id)
            ->with('error', 'Transfer ini sudah diproses sebelumnya.');
    }

    $transferToBranch->load(['branch', 'branchRequest', 'creator', 'details.product', 'details.unit']);
    return view('hendhys.transfer-to-branch.receive', compact('transferToBranch'));
}
```

- [ ] **Step 3: Perbarui method receive() di TransferToBranchController**

Ganti seluruh method `receive()` yang ada dengan:

```php
public function receive(Request $request, HendhysTransferToBranch $transferToBranch)
{
    $user = auth()->user();

    if ($user->branch->type !== 'cabang' || $transferToBranch->branch_id !== $user->branch_id) {
        abort(403, 'Hanya cabang penerima yang dapat melakukan konfirmasi ini.');
    }
    if ($transferToBranch->status !== 'sent') {
        return back()->with('error', 'Transfer ini sudah diproses sebelumnya.');
    }

    $request->validate([
        'received_quantities'   => 'required|array|min:1',
        'received_quantities.*' => 'required|numeric|min:0',
        'receive_notes'         => 'nullable|string|max:1000',
        'receive_photo'         => 'nullable|image|max:5120',
    ]);

    try {
        $photoPath = null;
        if ($request->hasFile('receive_photo')) {
            $photoPath = $request->file('receive_photo')->store('transfer-receipts', 'public');
        }

        DB::transaction(function () use ($request, $transferToBranch, $user, $photoPath) {
            $transferToBranch->load('details');

            foreach ($transferToBranch->details as $detail) {
                $receivedQty = (float) ($request->received_quantities[$detail->id] ?? 0);
                $receivedQty = min($receivedQty, (float) $detail->quantity); // tidak boleh melebihi qty kirim

                $detail->update(['received_quantity' => $receivedQty]);

                if ($receivedQty > 0) {
                    $this->stockService->creditHendhys(
                        $detail->product_id,
                        $detail->unit_id,
                        $receivedQty,
                        $transferToBranch->branch_id,
                        'receive_from_pusat',
                        $transferToBranch->id,
                        $user->id
                    );
                }
            }

            $transferToBranch->update([
                'status'        => 'received',
                'received_by'   => $user->id,
                'receive_notes' => $request->receive_notes,
                'receive_photo' => $photoPath,
            ]);
        });

        return redirect()->route('hendhys.transfer-to-branch.show', $transferToBranch->id)
            ->with('success', 'Penerimaan barang berhasil dikonfirmasi. Stok cabang telah bertambah sesuai qty diterima.');

    } catch (\Exception $e) {
        return back()->with('error', 'Gagal memproses penerimaan: ' . $e->getMessage());
    }
}
```

- [ ] **Step 4: Verifikasi routes terdaftar**

```bash
php artisan route:list --path=hendhys/transfer-to-branch 2>&1
```

Expected: Ada baris `hendhys.transfer-to-branch.receive-form` (GET) dan `hendhys.transfer-to-branch.receive` (POST).

- [ ] **Step 5: Commit**

```bash
git add routes/hendhys.php app/Http/Controllers/Hendhys/TransferToBranchController.php
git commit -m "feat: add showReceiveForm() and update receive() to handle per-item qty and photo"
```

---

## Task 4: View Penerimaan Cabang (receive.blade.php)

**Files:**
- Create: `resources/views/hendhys/transfer-to-branch/receive.blade.php`

- [ ] **Step 1: Buat file view penerimaan**

Buat file `resources/views/hendhys/transfer-to-branch/receive.blade.php` dengan konten berikut:

```blade
@extends('layouts.hendhys')
@section('title', 'Konfirmasi Penerimaan Barang')
@section('page-title', 'Konfirmasi Penerimaan: ' . $transferToBranch->transfer_number)

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full space-y-md">

    {{-- Header --}}
    <div class="flex items-center gap-sm">
        <a href="{{ route('hendhys.transfer-to-branch.show', $transferToBranch->id) }}"
            class="flex items-center justify-center w-9 h-9 rounded-full bg-surface-container border border-outline-variant text-on-surface-variant hover:bg-surface-container-high transition-colors active:scale-95">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-sm text-headline-sm font-bold text-on-surface">Konfirmasi Penerimaan Barang</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $transferToBranch->transfer_number }} · Dari Pusat Hendhys</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-error-container border border-error/30 text-on-error-container rounded-xl p-md flex items-start gap-sm">
        <span class="material-symbols-outlined text-error shrink-0 mt-0.5">error</span>
        <div>
            <p class="font-label-lg text-label-lg font-bold mb-xs">Ada kesalahan:</p>
            <ul class="list-disc list-inside space-y-xs font-body-sm text-body-sm">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ route('hendhys.transfer-to-branch.receive', $transferToBranch->id) }}"
          method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Info Pengiriman --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden mb-md">
            <div class="px-md py-sm border-b border-outline-variant bg-surface-container-low flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary text-[20px]">local_shipping</span>
                <h3 class="font-label-lg text-label-lg font-bold text-on-surface">Informasi Pengiriman</h3>
            </div>
            <div class="p-md grid grid-cols-2 md:grid-cols-3 gap-md">
                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">No. Transfer</p>
                    <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ $transferToBranch->transfer_number }}</p>
                </div>
                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Tanggal Kirim</p>
                    <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ \Carbon\Carbon::parse($transferToBranch->date)->translatedFormat('d F Y') }}</p>
                </div>
                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Dikirim Oleh</p>
                    <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ $transferToBranch->creator->name }}</p>
                </div>
            </div>
        </div>

        {{-- Tabel Qty Diterima --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden mb-md">
            <div class="px-md py-sm border-b border-outline-variant bg-surface-container-low flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary text-[20px]">inventory_2</span>
                <h3 class="font-label-lg text-label-lg font-bold text-on-surface">Daftar Barang — Isi Qty yang Diterima</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Produk</th>
                            <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-right">Qty Dikirim</th>
                            <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-right w-48">Qty Diterima <span class="text-error">*</span></th>
                            <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider w-24">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach($transferToBranch->details as $detail)
                        <tr class="hover:bg-surface-container/40 transition-colors">
                            <td class="px-md py-sm">
                                <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ $detail->product->name }}</p>
                            </td>
                            <td class="px-md py-sm text-right">
                                <span class="font-label-lg text-label-lg text-on-surface-variant">{{ number_format((float)$detail->quantity, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-md py-sm">
                                <input type="number"
                                    name="received_quantities[{{ $detail->id }}]"
                                    value="{{ old('received_quantities.' . $detail->id, (float)$detail->quantity) }}"
                                    min="0"
                                    max="{{ (float)$detail->quantity }}"
                                    step="0.001"
                                    required
                                    class="w-full text-right text-sm border border-outline-variant rounded-lg px-sm py-xs focus:border-primary focus:ring-0 bg-surface font-bold text-on-surface">
                            </td>
                            <td class="px-md py-sm">
                                <span class="font-label-sm text-label-sm text-on-surface-variant font-bold">{{ $detail->unit->abbreviation ?? $detail->unit->name }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Foto & Catatan --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden mb-md">
            <div class="px-md py-sm border-b border-outline-variant bg-surface-container-low flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary text-[20px]">attach_file</span>
                <h3 class="font-label-lg text-label-lg font-bold text-on-surface">Bukti & Keterangan</h3>
            </div>
            <div class="p-md space-y-md">
                {{-- Upload Foto --}}
                <div x-data="{ preview: null, fileName: '' }">
                    <label class="block font-label-md text-label-md font-bold text-on-surface-variant mb-xs">
                        Foto Bukti Serah Terima <span class="font-normal text-on-surface-variant">(opsional)</span>
                    </label>
                    <div class="border-2 border-dashed border-outline-variant rounded-xl p-lg flex flex-col items-center justify-center gap-sm cursor-pointer hover:border-primary hover:bg-primary-fixed/20 transition-all"
                         @click="$refs.photoInput.click()"
                         @dragover.prevent
                         @drop.prevent="
                            const file = $event.dataTransfer.files[0];
                            if (file && file.type.startsWith('image/')) {
                                preview = URL.createObjectURL(file);
                                fileName = file.name;
                                const dt = new DataTransfer();
                                dt.items.add(file);
                                $refs.photoInput.files = dt.files;
                            }">
                        <template x-if="!preview">
                            <div class="text-center">
                                <span class="material-symbols-outlined text-outline text-[48px] mb-sm block">photo_camera</span>
                                <p class="font-label-lg text-label-lg text-on-surface-variant">Klik atau drag foto ke sini</p>
                                <p class="font-body-sm text-body-sm text-outline mt-xs">JPG, PNG, WEBP — maks. 5 MB</p>
                            </div>
                        </template>
                        <template x-if="preview">
                            <div class="text-center">
                                <img :src="preview" class="max-h-40 max-w-full rounded-lg shadow-sm mx-auto mb-sm object-contain" alt="Preview">
                                <p class="font-label-sm text-label-sm text-on-surface-variant" x-text="fileName"></p>
                                <button type="button" @click.stop="preview = null; fileName = ''; $refs.photoInput.value = ''"
                                    class="mt-xs text-error font-label-sm text-label-sm hover:underline">Hapus foto</button>
                            </div>
                        </template>
                    </div>
                    <input type="file" name="receive_photo" accept="image/*" class="hidden" x-ref="photoInput"
                           @change="
                            const file = $event.target.files[0];
                            if (file) { preview = URL.createObjectURL(file); fileName = file.name; }
                           ">
                    @error('receive_photo') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block font-label-md text-label-md font-bold text-on-surface-variant mb-xs">
                        Catatan ke Pusat <span class="font-normal text-on-surface-variant">(opsional — tuliskan jika ada masalah)</span>
                    </label>
                    <textarea name="receive_notes" rows="3"
                        placeholder="Contoh: 2 pcs Roti Abon datang dalam kondisi rusak. Kotak pengiriman terbuka."
                        class="w-full font-body-md text-body-md bg-surface-container border border-outline-variant focus:border-primary focus:ring-0 rounded-xl text-on-surface px-sm py-sm resize-none">{{ old('receive_notes') }}</textarea>
                    @error('receive_notes') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Tombol Aksi --}}
        <div class="flex items-center justify-end gap-sm">
            <a href="{{ route('hendhys.transfer-to-branch.show', $transferToBranch->id) }}"
                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant bg-surface border border-outline-variant rounded-lg hover:bg-surface-container transition-colors">
                Batal
            </a>
            <button type="submit"
                onclick="return confirm('Konfirmasi penerimaan barang? Stok cabang akan bertambah sesuai qty yang diterima.')"
                class="inline-flex items-center gap-xs px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg font-bold shadow-sm hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all">
                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                Konfirmasi Terima Barang
            </button>
        </div>

    </form>
</div>
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/hendhys/transfer-to-branch/receive.blade.php
git commit -m "feat: add receive form view for branch with per-item qty, photo, and notes"
```

---

## Task 5: Update show.blade.php — Fix Manual Transfer + Tombol Terima → GET

**Files:**
- Modify: `resources/views/hendhys/transfer-to-branch/show.blade.php`

- [ ] **Step 1: Fix No. Request Asal agar tidak error untuk distribusi manual**

Temukan baris:
```html
<p class="text-sm text-gray-600">No. Request Asal: <span class="font-bold text-gray-800">{{ $transferToBranch->branchRequest->request_number }}</span></p>
```

Ganti dengan:
```html
@if($transferToBranch->branchRequest)
<p class="text-sm text-gray-600">No. Request Asal: <span class="font-bold text-gray-800">{{ $transferToBranch->branchRequest->request_number }}</span></p>
@else
<p class="text-sm text-gray-600">Tipe: <span class="font-bold text-gray-800">Distribusi Manual</span></p>
@endif
```

- [ ] **Step 2: Ganti tombol "Konfirmasi Terima Barang" dari form POST ke link GET**

Temukan blok paling bawah (cabang confirm section):
```html
@if(!$isPusat && $transferToBranch->status === 'sent' && $transferToBranch->branch_id === auth()->user()->branch_id)
<div class="p-6 bg-amber-50 border-t border-amber-100 flex justify-between items-center no-print print:hidden">
    <div>
        <p class="font-bold text-amber-800">Konfirmasi Penerimaan Barang</p>
        <p class="text-sm text-amber-700">Pastikan fisik barang sesuai dengan surat jalan sebelum menekan tombol terima.</p>
    </div>
    <form action="{{ route('hendhys.transfer-to-branch.receive', $transferToBranch->id) }}" method="POST">
        @csrf
        <button type="submit" onclick="return confirm('Apakah Anda yakin barang sudah diterima dan sesuai? Stok cabang akan bertambah.')" class="bg-[#d97706] hover:bg-[#b45309] text-white px-6 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm">
            Konfirmasi Terima Barang
        </button>
    </form>
</div>
@endif
```

Ganti dengan:
```html
@if(!$isPusat && $transferToBranch->status === 'sent' && $transferToBranch->branch_id === auth()->user()->branch_id)
<div class="p-6 bg-amber-50 border-t border-amber-100 flex justify-between items-center no-print print:hidden">
    <div>
        <p class="font-bold text-amber-800">Konfirmasi Penerimaan Barang</p>
        <p class="text-sm text-amber-700">Periksa fisik barang, isi qty yang diterima, dan foto bukti sebelum konfirmasi.</p>
    </div>
    <a href="{{ route('hendhys.transfer-to-branch.receive-form', $transferToBranch->id) }}"
       class="bg-[#d97706] hover:bg-[#b45309] text-white px-6 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Terima & Konfirmasi Barang
    </a>
</div>
@endif
```

- [ ] **Step 3: Tambahkan tampilan received_quantity dan info penerimaan di halaman show (setelah status 'received')**

Temukan bagian tanda tangan di bawah:
```html
{{-- Tanda Tangan --}}
<div class="mt-16 grid grid-cols-2 gap-8 text-center text-sm">
```

Tambahkan SEBELUM bagian tanda tangan:
```html
{{-- Informasi Penerimaan (tampil jika sudah diterima) --}}
@if($transferToBranch->status === 'received')
<div class="mt-6 pt-6 border-t border-gray-200">
    <h4 class="text-sm font-bold text-gray-700 mb-3">Informasi Penerimaan</h4>
    <table class="w-full text-left border-collapse border border-gray-400 text-sm mb-4">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-3 font-bold border border-gray-400">Produk</th>
                <th class="py-2 px-3 font-bold border border-gray-400 text-right">Qty Dikirim</th>
                <th class="py-2 px-3 font-bold border border-gray-400 text-right">Qty Diterima</th>
                <th class="py-2 px-3 font-bold border border-gray-400">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transferToBranch->details as $detail)
            <tr>
                <td class="py-2 px-3 border border-gray-400">{{ $detail->product->name }}</td>
                <td class="py-2 px-3 border border-gray-400 text-right">{{ (int)$detail->quantity }}</td>
                <td class="py-2 px-3 border border-gray-400 text-right font-bold
                    {{ ($detail->received_quantity !== null && $detail->received_quantity < $detail->quantity) ? 'text-red-600' : 'text-green-700' }}">
                    {{ $detail->received_quantity !== null ? (int)$detail->received_quantity : '-' }}
                </td>
                <td class="py-2 px-3 border border-gray-400 text-xs">
                    @if($detail->received_quantity !== null && $detail->received_quantity < $detail->quantity)
                        <span class="text-red-600 font-bold">Kurang {{ (int)($detail->quantity - $detail->received_quantity) }}</span>
                    @elseif($detail->received_quantity !== null)
                        <span class="text-green-700 font-bold">Sesuai</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($transferToBranch->receive_notes)
    <div class="bg-yellow-50 border border-yellow-300 rounded p-3 text-sm mb-3">
        <p class="font-bold text-yellow-800 mb-1">Catatan dari Cabang:</p>
        <p class="text-yellow-900">{{ $transferToBranch->receive_notes }}</p>
    </div>
    @endif
    @if($transferToBranch->receive_photo)
    <div class="mb-4">
        <p class="text-xs font-bold text-gray-600 mb-2">Foto Bukti Serah Terima:</p>
        <img src="{{ asset('storage/' . $transferToBranch->receive_photo) }}" 
             alt="Bukti Serah Terima" class="max-h-48 rounded border border-gray-300 object-contain">
    </div>
    @endif
</div>
@endif
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/hendhys/transfer-to-branch/show.blade.php
git commit -m "feat: fix manual transfer display, update receive button to GET form, show receive info"
```

---

## Task 6: Cetak Faktur di Branch Request Show

**Files:**
- Modify: `resources/views/hendhys/branch-requests/show.blade.php`

- [ ] **Step 1: Tambah print CSS di bagian atas show.blade.php**

Tambahkan `@push('styles')` setelah baris `@section('content')`:

```blade
@push('styles')
<style>
@media print {
    aside, nav, header,
    [class*="sidebar"], [class*="navbar"], [class*="topbar"],
    .no-print { display: none !important; }
    body { background: white !important; margin: 0 !important; padding: 0 !important; }
    main, [class*="content"], #app {
        margin: 0 !important; padding: 0 !important;
        width: 100% !important; max-width: 100% !important;
    }
    .print-card {
        max-width: 100% !important; margin: 0 !important;
        box-shadow: none !important; border: 1.5px solid #000 !important;
        border-radius: 0 !important;
    }
    @page { margin: 10mm; size: A4 portrait; }
}
</style>
@endpush
```

- [ ] **Step 2: Tambah tombol cetak di header section (samping status badge)**

Temukan div header (flex items-center justify-between) di atas info cards. Di dalam `<div class="flex items-center gap-sm flex-wrap">`, tambahkan tombol cetak SEBELUM status badge:

```html
<button onclick="window.print()"
    class="no-print inline-flex items-center gap-xs px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors active:scale-95">
    <span class="material-symbols-outlined text-[18px]">print</span>
    Cetak Faktur
</button>
```

- [ ] **Step 3: Bungkus konten utama dengan class print-card agar print-friendly**

Temukan div pembungkus utama:
```html
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full space-y-md">
```

Tambahkan wrapper `print-card` di dalam `@section('content')`:

Setelah `<div class="p-margin-mobile ...">`, tambahkan `<div class="print-card">` dan tutup `</div>` sebelum `</div>` penutup section.

Atau lebih sederhana — di file `show.blade.php` yang ada, wrap semua konten dalam `<div class="print-card">...</div>` untuk ketika print.

Implementasi: Cukup tambahkan `print-card` ke div pembungkus terluar section content, ganti:
```html
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full space-y-md">
```
Menjadi:
```html
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full space-y-md print-card">
```

- [ ] **Step 4: Tambah header faktur yang hanya muncul saat print**

Di dalam `<div class="p-margin-mobile...">`, sebelum div header halaman, tambahkan:

```html
{{-- Hanya tampil saat cetak --}}
<div class="hidden print:block mb-6 pb-4 border-b-2 border-gray-800">
    <h1 class="text-2xl font-bold uppercase tracking-wide">Faktur Permintaan Stok Cabang</h1>
    <p class="text-sm text-gray-600 mt-1">Hendhys Bakery — Sistem Bisnis Terpadu</p>
    <p class="text-sm text-gray-600">Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
</div>
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/hendhys/branch-requests/show.blade.php
git commit -m "feat: add print invoice button and print CSS to branch request show page"
```

---

## Self-Review

### Spec Coverage
- [x] Cetak Faktur di Branch Request → Task 6
- [x] Tombol cetak + `window.print()` → Task 6 Step 2
- [x] Faktur: nomor, tanggal, cabang, tabel produk, tanda tangan → konten sudah ada di view, print CSS membuat visible
- [x] Surat Jalan fix untuk distribusi manual (no request_id) → Task 5 Step 1
- [x] Tombol Terima → form penerimaan (GET route) → Task 3 + 5 Step 2
- [x] DB migration: receive_notes, receive_photo → Task 1
- [x] DB migration: received_quantity per detail → Task 1
- [x] Form penerimaan: tabel qty per produk → Task 4
- [x] Input qty default = qty kirim, max = qty kirim → Task 4
- [x] Upload foto opsional + drag & drop → Task 4
- [x] Textarea catatan ke pusat opsional → Task 4
- [x] Stok bertambah sesuai qty DITERIMA (bukan qty kirim) → Task 3 Step 3
- [x] After confirm → show detail dengan qty terima, catatan, foto → Task 5 Step 3

### Placeholder Scan
- Tidak ada TBD, TODO, atau steps tanpa kode.

### Type Consistency
- `received_quantities[{detail->id}]` di view (Task 4) ↔ `$request->received_quantities[$detail->id]` di controller (Task 3) — konsisten.
- `receive_notes`, `receive_photo` di migration (Task 1) ↔ fillable (Task 2) ↔ controller update() (Task 3) ↔ view display (Task 5) — konsisten.
- Route name `hendhys.transfer-to-branch.receive-form` di Task 3 ↔ dipakai di Task 5 Step 2 ↔ di `receive.blade.php` action (Task 4) — konsisten.
