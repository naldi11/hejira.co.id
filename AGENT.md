# AGENT.md — Panduan Agent untuk Proyek HEJIRA

Dokumen ini menjelaskan arsitektur, konvensi, dan resep kerja untuk melanjutkan
**migrasi HEJIRA dari Blade + Alpine.js → Inertia.js + React 19**. Baca ini dulu
sebelum mengerjakan tugas apa pun.

> Status ringkas (per 2026-06-04): **Phase 1 (Gudang) SELESAI**, **Phase 2 (Jihans)
> ±80% — tersisa 3 subsistem besar (POS, Reports, Tortilla)**, **Phase 3 (Hendhys)
> belum dimulai**. Detail lengkap ada di `agen-tasklist.md`.

---

## 1. Tentang Proyek

HEJIRA adalah sistem POS & manajemen inventori multi-entitas:

| Entitas  | Peran                          | Warna tema | Layout React        |
|----------|--------------------------------|------------|---------------------|
| Gudang   | Gudang Utama + Master Data     | Indigo     | `GudangLayout.jsx`  |
| Jihans   | Produksi tortilla + Kasir POS  | Orange     | `JihansLayout.jsx`  |
| Hendhys  | Cabang/outlet brownies         | Amber      | *(belum dibuat)*    |
| Owner    | Dashboard pemilik              | -          | *(belum dimigrasi)* |

## 2. Tech Stack

- **Backend:** Laravel 13 (PHP 8.3), Spatie Permission, Maatwebsite Excel, DomPDF, Reverb/Echo.
- **Inertia:** `inertiajs/inertia-laravel` v3 + `@inertiajs/react` v3 + React 19.
- **Routing JS:** **Ziggy** (`@routes` directive). Helper global `window.route()`.
  Di setiap file React: `const route = window.route;`.
- **Build:** Vite + `@vitejs/plugin-react`. Tailwind v3 via PostCSS.
- **Dual entry point** (`vite.config.js`): `resources/js/app.js` (Alpine/Blade legacy,
  masih dipakai layar yg belum dimigrasi) **dan** `resources/js/app.jsx` (Inertia/React).
  Keduanya hidup berdampingan selama migrasi — JANGAN hapus `app.js`.

### File fondasi (Phase 0 — sudah ada, jangan diubah tanpa alasan)
- `resources/views/app.blade.php` — root template Inertia (`@routes @vite @inertia`).
- `app/Http/Middleware/HandleInertiaRequests.php` — shared props: `auth.user`,
  `flash.success/error`, `notifications.gudang_pending`.
- `tailwind.config.js` — tema (warna `primary`, font Poppins) + keyframe **`shimmer`**.
- `resources/js/app.jsx` — entry Inertia, `resolvePageComponent('./Pages/...')`.

## 3. Struktur Folder React (`resources/js`)

```
Layouts/      GudangLayout.jsx, JihansLayout.jsx
Components/    Icon, Skeleton (shimmer), Modal, Pagination, StatusBadge,
              EmptyState, FlashToasts
lib/          format.js (formatQty, formatRupiah, formatDate)
Pages/
  Gudang/     Dashboard, Stock/, PurchaseOrders/, Receivings/, TransferRequests/,
              TransferOut/, Returns/
  Master/     Branches/, Users/, Suppliers/, Customers/, Products/
  Jihans/     Dashboard, Stock/, Transactions/, TransferRequests/, Pending/,
              ProductionConfig, Returns/
```

## 4. RESEP MIGRASI per layar (WAJIB diikuti)

Untuk tiap layar yang dimigrasi:

1. **Validasi → Form Request** di `app/Http/Requests/<Modul>/`. Tidak ada
   `$request->validate()` di controller. Pakai `prepareForValidation()` untuk
   normalisasi input (lihat `StoreReceivingRequest`).
2. **Controller kurus**: terima Form Request, panggil `$request->validated()`,
   delegasikan ke Service, kembalikan `Inertia::render('Modul/Halaman', [...])`.
3. **API Resource** di `app/Http/Resources/<Modul>/` untuk SEMUA props (tidak ada
   model mentah ke React). Pakai `whenLoaded`/`whenCounted`. Reuse resource antar
   modul jika cocok (DRY) — contoh: `ProductStockResource`, `StockMovementResource`,
   `GudangReturnResource` dipakai ulang oleh Jihans.
4. **Eager loading** (`with`/`withCount`) — hindari N+1.
5. **Halaman React** di `resources/js/Pages/<Modul>/` pakai layout entitas yang benar,
   micro-component yang reusable, dan **skeleton shimmer** saat reload filter
   (`<SkeletonTableRows>` + state `loading` via `onStart/onFinish`).
6. **Hapus Blade lama** setelah React-nya jalan. KECUALI view print/PDF/faktur
   (itu dibiarkan Blade — server-rendered document).
7. **Feature test** (PHPUnit) di `tests/Feature/<Modul>/`: cek `assertInertia`
   component + props, aturan validasi, business rule, otorisasi, efek samping DB.
8. `npm run build` + `php artisan test` harus hijau sebelum lanjut.

## 5. Pola penting yang sudah ditetapkan

- **Shared master controllers** (`Supplier/Customer/Product`) dipakai 3 entitas via
  trait `ScopesMasterData`. Controllernya **branch on scope**:
  `if ($info['scope'] === 'gudang') return Inertia::render(...); else return view(...)`.
  Saat migrasi Jihans/Hendhys: **TAMBAH cabang `if`-nya, jangan tulis ulang.**
  *(Saat ini master Jihans/Hendhys masih Blade dan berfungsi via full-page nav.)*
- **Product form**: `category_id/unit_id/brand_id` dikirim sebagai **NAMA** (string);
  controller `resolveRelations()` melakukan `firstOrCreate` by name.
- **Print/Faktur/PDF tetap Blade**: `*/print.blade.php`, `jihans/transactions/show`
  (faktur), reports `*_pdf`. Link ke sana pakai `<a target="_blank">`, bukan Inertia Link.
- **JSON API tetap utuh**: Pending `store`/`show` dipakai POS via axios — hanya
  `index` yang jadi Inertia.
- **Route closure → controller**: jika dashboard dsb. masih `fn () => view(...)`
  dengan query inline, pindahkan ke controller (logic keluar dari view).

## 6. Testing

- Jalan di **sqlite :memory:** (`phpunit.xml`) + `RefreshDatabase`.
- Jalankan per modul: `php artisan test tests/Feature/Gudang/` (atau `Master/`, `Jihans/`).
- **Gotcha sqlite yang sudah diperbaiki** (jangan ulangi):
  - Migrasi MySQL-only `ALTER ... MODIFY ... ENUM` harus diberi guard
    `if (driver === 'sqlite') return;`. Konsekuensinya, **enum di migrasi `create_*`
    harus sudah memuat SEMUA nilai final** (karena widener-nya di-skip pada sqlite,
    dan sqlite memberlakukan CHECK constraint). Contoh yg diperbaiki:
    `jihans_stock_movements.source`.
  - Ordering pakai `CASE ... WHEN` (portable), bukan `FIELD()` (MySQL-only).
  - Customer `type` nullable → guard `($data['type'] ?? null) ?:`.

## 7. Perintah

```bash
npm run build                       # build React + Alpine (wajib sebelum lihat hasil)
php artisan test                    # seluruh suite
php artisan test tests/Feature/Jihans/   # per modul
php artisan route:list --path=jihans     # cek route
```

## 8. Angka saat ini

- 19 controller pakai `Inertia::render`, 21 Form Request, 16 Resource.
- 39 halaman React + 9 component/layout. 14 file feature test (63 test hijau).
- Lihat `agen-tasklist.md` untuk daftar lengkap selesai / belum.
