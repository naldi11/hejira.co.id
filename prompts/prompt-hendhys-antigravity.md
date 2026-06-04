# PROMPT — Migrasi Modul Hendhys ke Inertia + React (untuk Antigravity)

Kamu mengerjakan **Phase 3: modul Hendhys** dari proyek HEJIRA (Laravel + Inertia.js +
React 19). Modul Gudang sudah 100% selesai dan Jihans sedang dikerjakan agent lain
secara paralel. Polanya sudah mapan — ikuti persis, jangan menemukan pola baru.

## WAJIB baca dulu (sumber kebenaran)
1. **`AGENT.md`** (root proyek) — arsitektur, tech stack, dan **RESEP MIGRASI 8 langkah**.
   Ini kontrak utama; ikuti konvensinya tanpa kecuali.
2. **`agen-tasklist.md`** — status proyek + daftar tugas Hendhys (bagian "PHASE 3").
3. Contoh referensi yang SUDAH jadi (tiru polanya, jangan diubah):
   - Layout: `resources/js/Layouts/GudangLayout.jsx`, `JihansLayout.jsx`
   - Layar list+filter+shimmer: `resources/js/Pages/Gudang/Stock/Index.jsx`
   - Form line-item builder: `resources/js/Pages/Jihans/TransferRequests/Create.jsx`
   - Controller kurus + Resource: `app/Http/Controllers/Gudang/StockController.php`
     + `app/Http/Resources/Gudang/ProductStockResource.php`
   - Test: `tests/Feature/Jihans/JihansStockTest.php`

## Batas wilayah (PENTING — kerja paralel, jangan bentrok)
- **HANYA buat/ubah:**
  - `resources/js/Layouts/HendhysLayout.jsx` (BARU, tema **amber**)
  - `resources/js/Pages/Hendhys/*` (BARU)
  - `app/Http/Controllers/Hendhys/*`
  - `app/Http/Requests/Hendhys/*`, `app/Http/Resources/Hendhys/*` (BARU)
  - `routes/hendhys.php`
  - `tests/Feature/Hendhys/*` (BARU)
  - migrasi yang khusus tabel `hendhys_*`
- **JANGAN sentuh (read-only / reuse saja):** `Gudang/*`, `Jihans/*`, controller
  master shared (`Supplier/Customer/Product`), `tailwind.config.js`, `vite.config.js`,
  `app.jsx`, dan komponen shared di `resources/js/Components/*`
  (`Icon`, `Skeleton`, `Modal`, `Pagination`, `StatusBadge`, `EmptyState`, `FlashToasts`)
  + `lib/format.js`. Reuse komponen ini; jangan modifikasi.
- **Master data Hendhys (Supplier/Product) BIARKAN Blade** untuk sekarang — sudah
  berfungsi via full-page navigation. JANGAN edit controller master shared (itu milik
  langkah integrasi final, dikerjakan satu agent setelah semua fase selesai).
- Git: kerja di branch `feat/inertia-hendhys` agar merge-nya bersih (file set disjoint).

## Yang dikerjakan
1. **`HendhysLayout.jsx`** — port `resources/views/layouts/hendhys.blade.php`, tema
   amber, sidebar + topbar + `<FlashToasts />`, responsif. Tiru struktur `JihansLayout.jsx`.
2. Migrasi controller Hendhys per resep (Form Request → controller kurus → Resource →
   `Inertia::render` → halaman React + skeleton shimmer → hapus Blade → test):
   - `Dashboard` (jika route closure, pindahkan query ke controller)
   - `StockController` (Stock + Movements)
   - `TransactionController` (index → Inertia; ⏭️ receipt/faktur = Blade print)
   - `PendingController` (index → Inertia; ⏭️ store/show JSON utuh untuk POS)
   - `PosController` (kasir live — subsistem besar)
   - `ProductionController`
   - `BranchRequestController` (request stok antar cabang)
   - `TransferToBranchController` (kirim ke cabang; ⏭️ BAST = Blade print)
   - `ReturnController` + `GudangReturnController` (retur ke pusat/gudang)
   - `TransferRequestController`
3. ⏭️ Semua view print/BAST/struk/PDF TETAP Blade — link pakai `<a target="_blank">`.

## Catatan khusus Hendhys
- Hendhys berbasis **cabang**: pakai middleware `check.branch` + kolom `branch_id`.
  Banyak query difilter per cabang user (`auth()->user()->branch`). Pertahankan logika ini.
- **GOTCHA SQLITE WAJIB DIPERBAIKI:** migrasi `create_hendhys_stock_movements_table`
  punya enum `source` yang BELUM lengkap (widener `MODIFY ENUM`-nya di-skip di sqlite,
  dan sqlite memberlakukan CHECK constraint → test gagal). Perlebar enum base ke set final:
  `['transfer_gudang','production','transfer_to_branch','receive_from_pusat',
  'return_from_branch','return_to_pusat','return_gudang','pos_sale','adjustment']`.
  (Pola sama persis dgn fix `jihans_stock_movements.source` di Phase 2.)
- Ordering: pakai `CASE WHEN`, bukan `FIELD()` (MySQL-only, gagal di sqlite).

## Definition of Done
- Semua method Hendhys pakai `Inertia::render` (kecuali print/PDF/JSON API).
- Form Request untuk tiap validasi, Resource untuk tiap props, eager loading anti-N+1.
- Halaman React pakai `HendhysLayout` + skeleton shimmer + micro-components reusable.
- Blade lama dihapus kecuali print/BAST/struk/PDF.
- `tests/Feature/Hendhys/*` HIJAU + `npm run build` hijau + `php artisan test` hijau.
- TIDAK ada perubahan di file milik Gudang/Jihans/shared (cek `git diff --stat`).
- Update bagian "PHASE 3" di `agen-tasklist.md`.
