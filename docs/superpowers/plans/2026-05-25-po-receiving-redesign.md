# PO & Penerimaan Barang Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign alur GRN (Berita Acara Penerimaan) dengan status open/closed, delta-stok saat edit, upload foto opsional, dan dokumen cetak BAST + Surat Pesanan PO.

**Architecture:** GRN status `open` (stok langsung naik, bisa edit via delta) → `closed` (terkunci). Foto disimpan di `storage/public/receivings/{grn_number}/`. Print routes terpisah untuk BAST dan Surat Pesanan PO.

**Tech Stack:** Laravel 13 · Alpine.js · Tailwind CSS · StockService (creditGudang/debitGudang sudah ada)

---

## Files yang Diubah/Dibuat

| File | Action |
|---|---|
| `database/migrations/..._alter_gudang_receivings.php` | Create |
| `database/migrations/..._alter_gudang_receiving_details.php` | Create |
| `database/migrations/..._create_gudang_receiving_photos.php` | Create |
| `app/Models/Receiving.php` | Modify — fix Supplier ref, tambah fillable/casts/relations/helpers |
| `app/Models/ReceivingDetail.php` | Modify — tambah quantity_ordered, kondisi ke fillable |
| `app/Models/PurchaseOrder.php` | Modify — fix Supplier ref |
| `app/Models/ReceivingPhoto.php` | Create |
| `routes/gudang.php` | Modify — 6 route baru |
| `app/Http/Controllers/Gudang/ReceivingController.php` | Modify — update store + 5 method baru |
| `app/Http/Controllers/Gudang/PurchaseOrderController.php` | Modify — tambah print |
| `resources/views/gudang/receivings/form.blade.php` | Modify — tambah fields baru |
| `resources/views/gudang/receivings/show.blade.php` | Rewrite — inline edit + foto + close |
| `resources/views/gudang/receivings/print.blade.php` | Create — BAST A4 |
| `resources/views/gudang/purchase-orders/show.blade.php` | Modify — tambah tombol cetak |
| `resources/views/gudang/purchase-orders/print.blade.php` | Create — Surat Pesanan A4 |

---

## Task 1: Migrations

- [ ] Buat 3 migration files dan jalankan migrate

## Task 2: Fix Model Bugs + Update Models

- [ ] Fix Gudang\Supplier di PurchaseOrder & Receiving
- [ ] Update Receiving model (fillable, casts, relations, helpers)
- [ ] Update ReceivingDetail (tambah quantity_ordered, kondisi)
- [ ] Buat ReceivingPhoto model

## Task 3: Routes

- [ ] Tambah 6 routes ke gudang.php

## Task 4: ReceivingController

- [ ] Update store (new fields)
- [ ] Tambah update (delta stok)
- [ ] Tambah close
- [ ] Tambah print
- [ ] Tambah uploadPhoto
- [ ] Tambah deletePhoto

## Task 5: PurchaseOrderController

- [ ] Tambah print method

## Task 6: Views

- [ ] Update form.blade.php (kondisi, received_by_name, supplier_rep_name, kendala)
- [ ] Rewrite show.blade.php (inline edit + foto + close button)
- [ ] Buat receivings/print.blade.php (BAST)
- [ ] Update po/show.blade.php (tambah tombol cetak)
- [ ] Buat purchase-orders/print.blade.php (Surat Pesanan)
