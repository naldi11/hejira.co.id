# Agen Task List — Migrasi HEJIRA ke Inertia + React

Status per **2026-06-04**. Legenda: ✅ selesai · 🟡 sebagian · ⬜ belum · ⏭️ sengaja dibiarkan Blade (print/PDF/faktur/JSON API).

**Ringkasan:** 19 controller Inertia · 21 Form Request · 16 Resource · 39 halaman React · 63 test hijau · `npm run build` hijau.

---

## ✅ PHASE 0 — Fondasi Inertia + React (SELESAI)

- ✅ Install `inertiajs/inertia-laravel`, `@inertiajs/react`, `react`, `react-dom`, `@vitejs/plugin-react`, Ziggy.
- ✅ Vite dual-entry (`app.js` legacy + `app.jsx` Inertia).
- ✅ Root template `resources/views/app.blade.php` + middleware `HandleInertiaRequests`.
- ✅ Tema Tailwind dipindah dari CDN ke `tailwind.config.js` + keyframe `shimmer`.
- ✅ UI kit: `GudangLayout`, `Icon`, `Skeleton`, `Modal`, `Pagination`, `StatusBadge`, `EmptyState`, `FlashToasts`, `lib/format.js`.

---

## ✅ PHASE 1 — Gudang (SELESAI, 100%)

### Operasional (7 controller) — semua ✅
- ✅ **Dashboard** — stats + quick actions.
- ✅ **Stock** — Index + AdjustModal (Opname) + **Movements** (Kartu Stok).
- ✅ **Purchase Order** — Index + Form (create/edit) + Show. ⏭️ `print.blade.php`.
- ✅ **Receiving / GRN** — Index + Create (upload foto, pre-fill PO) + Show (edit/close/foto). ⏭️ `print.blade.php`.
- ✅ **Transfer Request** — Index + Show (approve/reject modal).
- ✅ **Transfer Out** — Index + Create (line-item builder) + Show.
- ✅ **Return (penerimaan retur)** — Index + Show (form receive).

### Master Data (5 controller) — semua ✅ (scope gudang)
- ✅ **Branch** (gudang-only) — Index + Form. Blade dihapus.
- ✅ **User** (gudang-only) — Index + Form (single-role + password confirmed). Blade dihapus.
- ✅ **Supplier** (shared) — Index + Form. Blade jihans/hendhys tetap ada.
- ✅ **Customer** (shared, + import Excel) — Index + Form (visibility toggles).
- ✅ **Product** (shared, + import/export, foto, tiered price) — Index + Form.

### Test Gudang/Master: ✅ 37 test (Stock, TransferRequest, PO, Receiving, Branch/User, MasterData).

### Bug pre-existing yang diperbaiki di Phase 1
- ✅ `routes/hendhys.php` — import `GudangReturnController` yang hilang (500 laten).
- ✅ Migrasi `add_payment_method_id_*` — guard sqlite untuk `MODIFY ENUM`.
- ✅ Hapus `tests/Feature/ExampleTest.php` boilerplate.

---

## ✅ PHASE 2 — Jihans (100%)

### ✅ Sudah dimigrasi (11/11 controller)
- ✅ **JihansLayout.jsx** (tema orange).
- ✅ **Dashboard** — query inline dipindah ke `DashboardController` baru + route closure → controller.
- ✅ **Stock** — Index + **Movements**.
- ✅ **Transactions** — Index. ⏭️ `show` = faktur print (Blade).
- ✅ **Transfer Request (ke Gudang)** — Index (+ banner pengiriman masuk) + Create + Show.
- ✅ **Pending** — Index. ⏭️ `store`/`show` = JSON API untuk POS.
- ✅ **Production Config** — form 5-select.
- ✅ **Return ke Gudang** — Index + Create + Show.
- ✅ **POS** (`PosController` + `pos/index.blade.php`) — kasir live. ⏭️ `pos/receipt.blade.php` = print.
- ✅ **Reports** (`ReportController`) — `index`, `laci`, `harian`, `mingguan`, `bulanan`, `pelanggan`. ⏭️ `pdf` & `harian_pdf` = Blade PDF.
- ✅ **Tortilla Production** (`TortillaProductionController`) — `index`, `create/store`, `show`, `recap` (+ export), `prediksi.create/store`. ⏭️ `faktur-prediksi.blade.php` = print.
- ✅ **Karyawan** (`Master/KaryawanController` — dipakai Jihans) → Inertia + React.
- ✅ Test Jihans: Semua layar hijau.

### ✅ Integration Pass (Shared Master)
- ✅ **Shared master untuk scope jihans & hendhys** (Supplier/Customer/Product) — Selesai. `ScopedLayout` sudah terhubung, parameter `routePrefix` sudah disuntikkan, dan ketiga Controller tersebut kini 100% menggunakan Inertia/React untuk semua entitas (Gudang, Jihans, Hendhys).

### Sisa (Opsional)
- ⏭️ `ReceiptController` (terima transfer dari Gudang, `transfer-requests.receive-form`)
  — sengaja dibiarkan Blade; bisa dimigrasi kapan-kapan (form penerimaan kecil).

---

## ⬜ PHASE 3 — Hendhys (BELUM DIMULAI)

11 controller, 39 view Blade. Pakai resep yang sama + buat **`HendhysLayout.jsx`** (tema amber).
- ⬜ `HendhysLayout.jsx`.
- ⬜ Dashboard, POS, Pending, Production, Stock.
- ⬜ BranchRequest (request antar cabang), TransferToBranch, Return.
- ⬜ Shared master untuk scope hendhys (extend cabang `if` di Supplier/Product controller).
- ⏭️ View print/BAST/struk tetap Blade.

---

## ⬜ PHASE 4 — Owner (BELUM DIMULAI, di luar scope awal)

`Controllers/Owner/*` (Dashboard, GudangDashboard, JihansDashboard, HendhysDashboard, Report).
Belum direncanakan detail.

---

## Catatan teknis (jangan diulang)
- Enum di migrasi `create_*` harus memuat semua nilai final (widener MySQL di-skip di sqlite + CHECK constraint). Sudah diperbaiki: `jihans_stock_movements.source`. **Cek juga `gudang_stock_movements.source`** sebelum test alur retur gudang (`return_receiving`/`transfer_out`).
- Ordering: pakai `CASE WHEN`, bukan `FIELD()`.
- Print/PDF/faktur/JSON API tetap Blade — lihat tanda ⏭️ di atas.
- Detail konteks lengkap: lihat `AGENT.md` dan memory `inertia-react-migration.md`.
