# PROMPT — Selesaikan Migrasi Jihans (untuk Claude)

Lanjutkan migrasi **modul Jihans** dari Blade ke Inertia + React. Phase 1 (Gudang)
sudah 100% selesai dan Phase 2 (Jihans) sudah ±80%. Tugasmu: kerjakan 3 subsistem
besar yang tersisa + Karyawan.

## WAJIB baca dulu
1. `AGENT.md` — arsitektur, tech stack, dan **RESEP MIGRASI 8 langkah** (ikuti persis).
2. `agen-tasklist.md` — status detail selesai/belum.
3. Memory `inertia-react-migration.md` (jika tersedia).

## Batas wilayah (PENTING — kerja paralel dgn agent Hendhys)
- **HANYA sentuh:** `app/Http/Controllers/Jihans/*`, `app/Http/Requests/Jihans/*`,
  `app/Http/Resources/Jihans/*`, `resources/js/Pages/Jihans/*`,
  `resources/js/Layouts/JihansLayout.jsx`, `routes/jihans.php`,
  `tests/Feature/Jihans/*`, dan migrasi yang khusus tabel `jihans_*`.
- **JANGAN sentuh:** apa pun di `Hendhys/*`, `Gudang/*`, controller master shared
  (`Supplier/Customer/Product`), `tailwind.config.js`, dan komponen shared di
  `resources/js/Components` & `resources/js/Layouts/GudangLayout.jsx` (reuse saja,
  read-only). Bila perlu komponen baru, taruh di `Pages/Jihans/` lokal.
- Git: kerja di branch `feat/inertia-jihans`.

## Yang harus dikerjakan (per resep di AGENT.md)

### 1. POS — `PosController` + `resources/views/jihans/pos/index.blade.php`
Kasir live. Baca controller + blade dulu. Bangun React `Pages/Jihans/Pos/Index.jsx`
pakai `JihansLayout`. Fitur: grid/daftar produk + search, keranjang (cart) dengan
qty/diskon, pilih pelanggan (ganti TomSelect → combobox React / `react-select`
opsional atau native + datalist), ringkasan + pembayaran (tunai/kembalian), tombol
**Hold** (POST ke `jihans.pending.store` JSON via axios/`router`) dan **Resume**
(GET `jihans.pending.show` JSON). Submit → `pos.store`.
- Validasi `store` → `app/Http/Requests/Jihans/StorePosTransactionRequest.php`.
- ⏭️ `pos/receipt.blade.php` (struk) TETAP Blade — link `<a target="_blank">`.
- ⏭️ Pending `store`/`show`/`destroy` TETAP JSON API (jangan diubah).

### 2. Reports — `ReportController` (308 baris)
Method: `index`, `laci`, `harian`, `mingguan`, `bulanan`, `pelanggan`. Ubah ke
`Inertia::render('Jihans/Reports/<Nama>')` + Resource untuk data. Buat halaman React
masing-masing (mungkin satu layout report shared + tab). Pertahankan filter tanggal.
- ⏭️ `pdf` & `harian_pdf` (export) TETAP Blade.

### 3. Tortilla Production — `TortillaProductionController` (621 baris, TERBESAR)
Method: `index`, `create/store`, `show`, `recap` (+ `exportRecap`), `prediksi.create/store`.
Ubah ke Inertia + React (`Pages/Jihans/Tortilla/*`). `store` punya logika produksi
(menambah stok produk jadi sesuai `JihansProductionConfig`). Pisahkan validasi ke
Form Request, pertahankan business logic di controller/Service.
- ⏭️ `faktur-prediksi.blade.php` = print, biarkan Blade.

### 4. Karyawan — `app/Http/Controllers/Master/KaryawanController.php`
CRUD karyawan (dipakai di menu master Jihans). Migrasi ke Inertia + React
(`Pages/Jihans/Karyawan/*` atau `Master/Karyawan/*`) + Form Request + Resource + Blade dihapus.

## Gotcha (lihat AGENT.md §6)
- Enum di migrasi `create_*` harus memuat semua nilai (widener MySQL di-skip di sqlite).
  POS pakai source `pos_sale` (sudah ada). Tortilla pakai `production` (sudah ada). Aman,
  tapi cek bila ada source baru.
- Print/PDF/faktur tetap Blade. JSON API Pending tetap utuh.

## Definition of Done
- Semua method Jihans di atas pakai `Inertia::render` (kecuali print/JSON).
- Form Request + Resource untuk tiap layar. Skeleton shimmer di list.
- Blade lama dihapus (kecuali print).
- Feature test per layar HIJAU + `npm run build` hijau + `php artisan test` hijau.
- Update `agen-tasklist.md`: tandai item selesai.
