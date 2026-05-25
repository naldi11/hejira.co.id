# Desain: Redesign Alur PO & Penerimaan Gudang

**Tanggal:** 2026-05-25
**Scope:** Gudang Tempua — Purchase Order ke Supplier & Penerimaan Stok (GRN)
**Arsitektur:** Opsi B — GRN langsung kredit stok, bisa diedit via delta sampai ditutup

---

## 1. Konteks & Masalah

### Kondisi saat ini

| Masalah | Detail |
|---|---|
| GRN langsung terkunci | Tidak bisa edit jumlah setelah simpan |
| Tidak ada bukti serah terima | Tidak ada dokumen cetak BAST |
| Tidak ada foto bukti | Tidak ada lampiran foto kondisi barang |
| Tidak ada catatan kendala | Tidak ada field untuk mencatat masalah penerimaan |
| PO tidak bisa dicetak | Tidak ada Surat Pesanan untuk dikirim ke supplier |
| Model `Supplier` salah referensi | `PurchaseOrder` & `Receiving` masih pakai `App\Models\Gudang\Supplier` (tidak ada) |

### Yang diinginkan
- GRN bisa diedit (jumlah, kondisi, catatan per item) selama belum ditutup
- Setiap penerimaan harus memiliki bukti serah terima yang bisa dicetak (BAST)
- Lampiran foto opsional, bisa multiple
- Field alasan kendala jika ada masalah
- Surat Pesanan PO bisa dicetak untuk dikirim ke supplier

---

## 2. Alur Status

### Purchase Order
```
draft → sent → partial → received
  |       |
  └───────┴──→ cancelled
```
- `draft`: bisa diedit, dihapus
- `sent`: sudah dikirim ke supplier, tidak bisa diedit
- `partial`: ada penerimaan tapi belum semua item terpenuhi
- `received`: semua qty terpenuhi
- `cancelled`: dibatalkan (hanya dari draft/sent)

**Aksi baru:** Tombol **Cetak Surat Pesanan** tersedia di semua status.

### GRN / Berita Acara Penerimaan
```
[buat] → open ──(edit bebas)──→ closed
                                   ↑
                           tombol "Selesaikan"
                           dokumen terkunci permanen
```
- **`open`**: Stok sudah naik sejak pertama kali disimpan. Bisa diedit (delta stok diterapkan otomatis). Bisa tambah/hapus foto. Belum final.
- **`closed`**: Tidak bisa diedit. Dokumen BAST siap cetak. Stok tidak berubah lagi.

**Aturan penting:**
- GRN hanya bisa ditutup (`closed`) jika sudah ada `received_by_name` dan `supplier_rep_name` diisi
- Foto tidak wajib (opsional), tapi sangat dianjurkan
- Stok tidak boleh minus — edit yang menghasilkan stok negatif ditolak dengan pesan error

---

## 3. Perubahan Database

### 3a. ALTER `gudang_receivings`

```sql
ALTER TABLE gudang_receivings
  ADD COLUMN status           ENUM('open','closed') NOT NULL DEFAULT 'open' AFTER notes,
  ADD COLUMN received_by_name VARCHAR(100)          NULL     AFTER status,
  ADD COLUMN supplier_rep_name VARCHAR(100)         NULL     AFTER received_by_name,
  ADD COLUMN kendala          TEXT                  NULL     AFTER supplier_rep_name,
  ADD COLUMN closed_at        TIMESTAMP             NULL     AFTER kendala,
  ADD COLUMN closed_by        BIGINT UNSIGNED       NULL     AFTER closed_at,
  ADD CONSTRAINT fk_grn_closed_by
    FOREIGN KEY (closed_by) REFERENCES master_users(id) ON DELETE SET NULL;
```

### 3b. ALTER `gudang_receiving_details`

```sql
ALTER TABLE gudang_receiving_details
  ADD COLUMN quantity_ordered  DECIMAL(15,3) NULL AFTER receiving_id,
  ADD COLUMN kondisi           ENUM('baik','rusak','kurang') NULL AFTER notes;
```

- `quantity_ordered`: di-copy dari PO detail saat GRN dibuat dari PO, untuk ditampilkan di dokumen BAST sebagai referensi perbandingan
- `kondisi`: kondisi fisik barang per item (baik / rusak / kurang dari yang dipesan)

### 3c. CREATE `gudang_receiving_photos` (tabel baru)

```sql
CREATE TABLE gudang_receiving_photos (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  receiving_id  BIGINT UNSIGNED NOT NULL,
  path          VARCHAR(255)    NOT NULL,
  caption       VARCHAR(200)    NULL,
  uploaded_by   BIGINT UNSIGNED NOT NULL,
  created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_photo_receiving
    FOREIGN KEY (receiving_id) REFERENCES gudang_receivings(id) ON DELETE CASCADE,
  CONSTRAINT fk_photo_user
    FOREIGN KEY (uploaded_by) REFERENCES master_users(id)
);
```

File foto disimpan di: `storage/app/public/receivings/{grn_number}/{filename}`
Path yang disimpan ke DB: `receivings/{grn_number}/{filename}` (relatif dari `storage/app/public`)

---

## 4. Model Layer

### `Receiving` (update)

```php
protected $fillable = [
    'grn_number', 'po_id', 'supplier_id', 'date', 'notes', 'created_by',
    'status', 'received_by_name', 'supplier_rep_name',
    'kendala', 'closed_at', 'closed_by',
];

protected function casts(): array {
    return [
        'date'      => 'date',
        'closed_at' => 'datetime',
    ];
}

// relasi baru
public function photos(): HasMany     { return $this->hasMany(ReceivingPhoto::class, 'receiving_id'); }
public function closedBy(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }

// helper
public function isOpen(): bool   { return $this->status === 'open'; }
public function isClosed(): bool { return $this->status === 'closed'; }
```

**Fix bug existing:** Ganti `\App\Models\Gudang\Supplier::class` → `\App\Models\Supplier::class` di `supplier()` relation.

### `ReceivingDetail` (update)

Tambah `quantity_ordered` dan `kondisi` ke `$fillable`.

### `ReceivingPhoto` (baru)

```php
class ReceivingPhoto extends Model {
    public $timestamps = false;
    protected $table   = 'gudang_receiving_photos';
    protected $fillable = ['receiving_id','path','caption','uploaded_by','created_at'];

    public function receiving(): BelongsTo { return $this->belongsTo(Receiving::class); }
    public function uploader(): BelongsTo  { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function url(): string {
        return Storage::url($this->path);
    }
}
```

### `PurchaseOrder` (fix)

Ganti `\App\Models\Gudang\Supplier::class` → `\App\Models\Supplier::class`.

---

## 5. Controller Layer

### `ReceivingController` — method baru/diubah

| Method | Route | Keterangan |
|---|---|---|
| `index` | GET `/gudang/receivings` | Tidak berubah besar |
| `create` | GET `/gudang/receivings/create` | Tidak berubah |
| `store` | POST `/gudang/receivings` | Tambah kolom baru, set `status=open` |
| `show` | GET `/gudang/receivings/{id}` | Redesign — inline edit mode jika `open` |
| `update` | PUT `/gudang/receivings/{id}` | **Baru** — edit GRN open, apply delta stok |
| `close` | POST `/gudang/receivings/{id}/close` | **Baru** — tutup GRN, set `closed` |
| `print` | GET `/gudang/receivings/{id}/print` | **Baru** — render BAST view |
| `uploadPhoto` | POST `/gudang/receivings/{id}/photos` | **Baru** — upload 1-n foto |
| `deletePhoto` | DELETE `/gudang/receivings/{id}/photos/{photo}` | **Baru** — hapus foto + file |

#### `update` — logika delta stok

```
foreach (items as item):
    detail = cari ReceivingDetail by product_id
    delta  = item.qty_baru - detail.quantity

    if delta > 0:
        StockService::creditGudang(product, unit, delta, 'receiving_edit', grn_id, user_id)
    elif delta < 0:
        StockService::debitGudang(product, unit, |delta|, 'receiving_edit', grn_id, user_id)
        // validasi: stok tidak boleh minus, jika minus → rollback dan return error

    detail.update(qty_baru, kondisi, notes, hpp_price baru)

grn.update(received_by_name, supplier_rep_name, kendala, notes)
```

#### `close` — validasi sebelum tutup

```
abort_if grn.status != 'open'
abort_if grn.received_by_name kosong → error "Nama penerima wajib diisi sebelum menutup GRN"
abort_if grn.supplier_rep_name kosong → error "Nama perwakilan supplier wajib diisi"

grn.update(status='closed', closed_at=now(), closed_by=auth_id)
logger.log('close', 'gudang.receiving', "GRN ditutup: {grn_number}", grn)
```

#### `uploadPhoto`

```
validate: photos[] required, mimetypes jpeg/png/webp, max 5120kb per file, max 10 foto per GRN

foreach (file in photos[]):
    path = store to 'receivings/{grn_number}/{uuid}.{ext}'
    ReceivingPhoto::create(receiving_id, path, caption, uploaded_by)

return back with success
```

### `PurchaseOrderController` — tambah `print`

```
GET /gudang/po/{id}/print
po.load(['supplier','details.product','details.unit','creator'])
return view('gudang.purchase-orders.print', compact('po'))
```

### `StockService` — tambah `debitGudang`

```php
public function debitGudang(int $productId, int $unitId, int|float $qty,
                             string $type, int $referenceId, int $userId): void
{
    // Validasi: pastikan stok tidak minus setelah debit
    $stock = GudangStock::where('product_id', $productId)->first();
    if (!$stock || $stock->quantity < $qty) {
        throw new \Exception("Stok tidak mencukupi untuk koreksi produk ID {$productId}");
    }

    GudangStock::where('product_id', $productId)
        ->decrement('quantity', $qty);

    GudangStockMovement::create([
        'product_id'   => $productId,
        'unit_id'      => $unitId,
        'type'         => 'out',
        'quantity'     => $qty,
        'reference_type' => $type,
        'reference_id' => $referenceId,
        'created_by'   => $userId,
        'created_at'   => now(),
    ]);
}
```

---

## 6. Routes Baru

```php
// Purchase Orders
Route::get('po/{po}/print', [PurchaseOrderController::class, 'print'])->name('gudang.po.print');

// Receivings
Route::put('receivings/{receiving}', [ReceivingController::class, 'update'])->name('gudang.receiving.update');
Route::post('receivings/{receiving}/close', [ReceivingController::class, 'close'])->name('gudang.receiving.close');
Route::get('receivings/{receiving}/print', [ReceivingController::class, 'print'])->name('gudang.receiving.print');
Route::post('receivings/{receiving}/photos', [ReceivingController::class, 'uploadPhoto'])->name('gudang.receiving.photos.store');
Route::delete('receivings/{receiving}/photos/{photo}', [ReceivingController::class, 'deletePhoto'])->name('gudang.receiving.photos.destroy');
```

---

## 7. View Layer

### `gudang/receivings/show.blade.php` (redesign)

**Jika status `open`:**
- Header card: badge "TERBUKA", tombol **Edit**, tombol **Selesaikan GRN**, tombol **Cetak BAST**
- Edit mode: inline form — qty per item jadi `<input>`, kondisi jadi `<select>`, catatan item bisa diedit
- Field header: `received_by_name`, `supplier_rep_name`, `kendala` bisa diedit
- Foto section: grid foto yang sudah ada (dengan tombol hapus per foto) + form upload foto baru
- Tombol Selesaikan: konfirmasi modal sebelum submit

**Jika status `closed`:**
- Badge "SELESAI", semua field read-only
- Tombol **Cetak BAST** menonjol
- Foto ditampilkan read-only grid
- Tampilkan `closed_at` dan `closed_by`

### `gudang/receivings/print.blade.php` (baru)

Format A4 Portrait, auto-print saat load (`window.print()` dengan delay 600ms).

```
[Logo Gudang Tempua] ── BERITA ACARA SERAH TERIMA BARANG
─────────────────────────────────────────────────────────
No. GRN   : GDG-GRN-2026-001
Tanggal   : 25 Mei 2026
Supplier  : CV. Maju Jaya
Ref PO    : GDG-PO-2026-001 (jika ada)
─────────────────────────────────────────────────────────
No │ Nama Produk  │ Qty PO │ Qty Terima │ Satuan │ Kondisi │ Harga/Unit │ Total
───┼──────────────┼────────┼────────────┼────────┼─────────┼────────────┼──────
 1 │ ...          │  100   │   95       │  kg    │  Baik   │  5.000     │ ...
─────────────────────────────────────────────────────────
                                         TOTAL NILAI: Rp xxx
─────────────────────────────────────────────────────────
Kendala / Catatan:
[isi kendala jika ada, atau "Tidak ada kendala"]
─────────────────────────────────────────────────────────
Foto Bukti Penerimaan:
[thumbnail grid — max 4 per baris, ditampilkan jika ada foto]
─────────────────────────────────────────────────────────
         Penerima Gudang           Perwakilan Supplier
         (tanda tangan)            (tanda tangan)



         ____________________      ____________________
         [received_by_name]        [supplier_rep_name]
─────────────────────────────────────────────────────────
Dicetak: 25 Mei 2026 14:30 oleh Admin Gudang
```

### `gudang/purchase-orders/print.blade.php` (baru)

Format A4 Portrait, struktur serupa — header PO, tabel item, total, tanda tangan pembuat PO.

---

## 8. Urutan Implementasi

1. **Migrasi database** — 3 migration files (alter receivings, alter details, create photos)
2. **Fix bug Supplier** — PurchaseOrder & Receiving model referensi salah
3. **Model layer** — update Receiving, ReceivingDetail; buat ReceivingPhoto; tambah debitGudang ke StockService
4. **Routes** — tambah 5 route baru
5. **ReceivingController** — update store, tambah update/close/uploadPhoto/deletePhoto/print
6. **PurchaseOrderController** — tambah print
7. **View show GRN** — redesign dengan inline edit + foto uploader + tombol Selesaikan
8. **View print BAST** — dokumen BAST A4
9. **View print PO** — Surat Pesanan A4

---

## 9. Checklist Testing

- [ ] Buat GRN dari PO → stok naik → status `open`
- [ ] Buat GRN tanpa PO → stok naik → status `open`
- [ ] Edit GRN open: naikkan qty → delta stok positif diterapkan
- [ ] Edit GRN open: turunkan qty → delta stok negatif diterapkan
- [ ] Edit GRN open: turunkan qty melebihi stok tersedia → ditolak dengan error
- [ ] Upload 3 foto sekaligus → tersimpan di storage dan DB
- [ ] Hapus 1 foto → file terhapus dari disk dan DB
- [ ] Tutup GRN tanpa `received_by_name` → ditolak
- [ ] Tutup GRN valid → status `closed`, tidak bisa diedit lagi
- [ ] Cetak BAST GRN dengan foto → foto tampil di dokumen
- [ ] Cetak BAST GRN tanpa foto → seksi foto tidak muncul
- [ ] Cetak Surat Pesanan PO → format rapi, semua item tampil
- [ ] PO status update ke `partial` / `received` setelah GRN disimpan
