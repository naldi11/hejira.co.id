# Prediksi Produksi Tortilla — Design Spec

**Tanggal:** 2026-05-30
**Modul:** Jihan's Food — Produksi Tortilla

---

## Konteks

Kasir Jihan's perlu mencetak faktur penjualan ke pelanggan di pagi hari sebelum produksi selesai. Saat ini hanya ada input "Aktual Produksi" yang mengunci stok — artinya kasir tidak bisa buat faktur sampai produksi benar-benar selesai sore hari.

Solusi: tambah fase **Prediksi** sebagai data sementara pagi hari yang memungkinkan kasir mencetak faktur, lalu di-override oleh **Aktual** sore hari yang mengupdate stok.

---

## Alur

```
PAGI (Kasir)                      SORE (Admin/Kepala Dapur)
─────────────────────             ─────────────────────────
Buka menu "Prediksi Produksi"     Buka menu "Aktual Produksi" (existing)
Isi qty per karyawan              Isi qty aktual per karyawan
Simpan (type='prediksi')          Simpan (type='aktual')
Cetak faktur penjualan   →        Prediksi hari ini → overridden_at = now()
                                  Stok Jihans diupdate via StockService ✅
```

---

## Database

### Migration baru: tambah 2 kolom ke `jihans_tortilla_sessions`

| Kolom | Tipe | Default | Keterangan |
|---|---|---|---|
| `type` | `enum('prediksi','aktual')` | `'aktual'` | Backward compatible — data lama tetap aktual |
| `overridden_at` | `timestamp, nullable` | `null` | Diisi saat aktual menggantikan prediksi hari itu |

### Aturan bisnis
- Per tanggal: maks 1 prediksi + 1 aktual
- Prediksi **tidak** memanggil `StockService`
- Aktual memanggil `StockService::creditJihans()` (sama seperti sekarang) + set `overridden_at` di prediksi hari itu
- Prediksi yang sudah `overridden_at != null` → read-only, tidak bisa diedit/dihapus

---

## Controller

File: `app/Http/Controllers/Jihans/TortillaProductionController.php`

### Method baru
- `createPrediksi()` — tampilkan form prediksi (reuse view form existing, hidden input `type=prediksi`)
- `storePrediksi()` — simpan prediksi, skip StockService, redirect ke halaman faktur
- `printFaktur(JihansTortillaSession $session)` — render PDF faktur prediksi

### Perubahan di `store()` (aktual existing)
- Setelah simpan aktual: cari prediksi hari yang sama → set `overridden_at = now()`

### Guard/validasi tambahan
- `storePrediksi`: tolak jika prediksi atau aktual hari itu sudah ada
- `store` (aktual): tolak jika aktual hari itu sudah ada
- `printFaktur`: hanya boleh dari sesi `type='prediksi'`

---

## Routes (routes/jihans.php)

```php
GET  /tortilla/prediksi/create    → createPrediksi()
POST /tortilla/prediksi           → storePrediksi()
GET  /tortilla/{session}/faktur   → printFaktur()
```

---

## Views

### Menu sidebar
Tambah item "Prediksi Produksi" di atas "Aktual Produksi" (rename menu existing).

### Form prediksi
- Reuse `resources/views/jihans/tortilla/form.blade.php`
- Tambah hidden input `type = prediksi`
- Ubah judul: "Input Prediksi Produksi"
- Tombol submit: "Simpan & Cetak Faktur"
- Warning banner jika prediksi hari ini sudah ada

### Index list
Tambah kolom **Type** + badge:
- 🟡 Prediksi — badge kuning
- 🟢 Aktual — badge hijau
- Status "Digantikan" jika `overridden_at` tidak null

### Detail show
Tambah badge PREDIKSI/AKTUAL di header. Jika digantikan, tampilkan info sesi aktual pengganti.

### Faktur PDF (`resources/views/jihans/tortilla/faktur-prediksi.blade.php`)
- CSS reuse dari `receipt.blade.php` (`@page { size: 9.5in auto; margin: 0; }`)
- Konten: header Jihaan's Food, nomor sesi, tanggal, tabel 5 varian + total qty
- Label **"* DATA PREDIKSI — Belum Final *"** di footer
- `window.print()` auto saat halaman load

---

## Model

File: `app/Models/JihansTortillaSession.php`

Tambah:
- `type` dan `overridden_at` di `$fillable`
- Method helper: `isPrediksi()`, `isOverridden()`

---

## Verifikasi

1. Login sebagai `kasir_jihans`
2. Buka menu Prediksi Produksi → isi form → simpan → faktur terbuka otomatis → cetak
3. Login sebagai `admin_jihans` → input Aktual hari yang sama → prediksi ter-mark overridden
4. Cek list index: prediksi tampil badge 🟡, aktual 🟢, prediksi yang digantikan tampil "Digantikan"
5. Stok hanya berubah setelah aktual disimpan (bukan setelah prediksi)
