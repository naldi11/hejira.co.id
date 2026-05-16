# DATABASE SCHEMA — SISTEM MANAJEMEN BISNIS
## Gudang Tempua | Jihan's Food | Hendhys Brownies

**Platform:** Laravel (PHP)  
**Database:** MySQL — Single Database, prefix-based separation  
**Charset:** utf8mb4  
**Collation:** utf8mb4_unicode_ci  

---

## KONVENSI PENAMAAN

| Prefix | Entitas |
|---|---|
| `master_` | Data global & shared antar entitas |
| `gudang_` | Transaksi Gudang Tempua |
| `jihans_` | Transaksi Jihan's Food |
| `hendhys_` | Transaksi Hendhys Brownies |

**Aturan umum:**
- Semua tabel pakai `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- Semua tabel pakai `created_at` & `updated_at` TIMESTAMP (kecuali log)
- Foreign key ke `master_users` → kolom `created_by`, `updated_by`, `approved_by`
- Harga & amount → `DECIMAL(15,2)`
- Quantity → `DECIMAL(15,3)` (antisipasi pecahan)
- Status selalu pakai ENUM
- Soft delete (`deleted_at`) hanya pada tabel master yang krusial
- **Role & Permission** dikelola sepenuhnya oleh **Spatie Laravel Permission** (`roles`, `permissions`, `model_has_roles`, `role_has_permissions`). Tidak ada tabel `master_roles`, `master_permissions`, `master_role_permissions` — semua ditangani Spatie. Field `entity` dan `branch_id` di `master_users` adalah ekstensi custom untuk kontrol akses berbasis entitas bisnis yang tidak dicakup Spatie.

---

## ═══════════════════════════════════
## MASTER TABLES (10 Tabel)
## ═══════════════════════════════════

### `master_users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(100) | |
| email | VARCHAR(100) UNIQUE | |
| password | VARCHAR(255) | bcrypt |
| entity | ENUM('gudang','jihans','hendhys','owner','all') | Kontrol akses entitas — custom, tidak dicakup Spatie |
| branch_id | BIGINT UNSIGNED FK → master_branches NULL | Wajib untuk kasir_hendhys |
| is_active | TINYINT(1) DEFAULT 1 | |
| remember_token | VARCHAR(100) NULL | |
| last_login_at | TIMESTAMP NULL | |
| created_by | BIGINT UNSIGNED FK → master_users NULL | |
| deleted_at | TIMESTAMP NULL | Soft delete |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

*Role user (owner, admin_gudang, dst) di-assign via Spatie `model_has_roles`. Field `entity` dan `branch_id` hanya untuk middleware custom `CheckEntity` dan `CheckBranch`.*

---

### `master_branches`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(20) UNIQUE | HND-PST, HND-CB1, dst |
| name | VARCHAR(100) | "Hendhys Pusat", "Hendhys Cabang Sukajadi" |
| type | ENUM('pusat','cabang') | |
| address | TEXT NULL | |
| phone | VARCHAR(20) NULL | |
| is_active | TINYINT(1) DEFAULT 1 | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `master_suppliers`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(20) UNIQUE | Auto: SUP-0001 |
| name | VARCHAR(150) | |
| contact_person | VARCHAR(100) NULL | |
| phone | VARCHAR(20) NULL | |
| email | VARCHAR(100) NULL | |
| address | TEXT NULL | |
| notes | TEXT NULL | |
| is_active | TINYINT(1) DEFAULT 1 | |
| deleted_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `master_customers`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(20) UNIQUE | Auto: CST-0001 |
| name | VARCHAR(150) | |
| type | ENUM('retail','agen') | |
| phone | VARCHAR(20) NULL | |
| email | VARCHAR(100) NULL | |
| address | TEXT NULL | |
| notes | TEXT NULL | |
| is_active | TINYINT(1) DEFAULT 1 | |
| deleted_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `master_product_categories`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(100) | Frozen Food, Tortilla, Bakery, Snack, dll |
| entity | ENUM('gudang','jihans','hendhys','all') | Scope kategori |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `master_units`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(50) | Pak, Pcs, Kg, Liter, Lusin |
| abbreviation | VARCHAR(10) | PAK, PCS, KG, LTR |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `master_brands`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(100) | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `master_products`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(20) UNIQUE | Auto: PRD-0001 |
| barcode | VARCHAR(50) UNIQUE NULL | |
| name | VARCHAR(200) | |
| category_id | BIGINT UNSIGNED FK → master_product_categories | |
| unit_id | BIGINT UNSIGNED FK → master_units | Satuan utama |
| brand_id | BIGINT UNSIGNED FK → master_brands NULL | |
| rack | VARCHAR(20) NULL | Lokasi rak gudang |
| jenis | ENUM('frozen','tortilla','bakery','bahan_baku','aksesoris','minuman','snack','selai','property','lainnya') | |
| hpp | DECIMAL(15,2) DEFAULT 0 | HPP — diupdate otomatis saat GRN (last price) |
| selling_price | DECIMAL(15,2) DEFAULT 0 | Harga jual |
| stock_min | INT DEFAULT 0 | Minimum stok warning |
| ppn_type | ENUM('none','include','exclude') DEFAULT 'none' | |
| ppn_rate | DECIMAL(5,2) DEFAULT 11.00 | Persen PPN |
| product_type | ENUM('INV','NON') DEFAULT 'INV' | INV=inventory tracked |
| entity_scope | ENUM('gudang','jihans','hendhys','all') DEFAULT 'all' | Entitas yang bisa jual |
| status | ENUM('active','discontinued') DEFAULT 'active' | |
| notes | TEXT NULL | Misal: EXP date info |
| deleted_at | TIMESTAMP NULL | |
| created_by | BIGINT UNSIGNED FK → master_users NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `master_activity_logs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| user_id | BIGINT UNSIGNED NULL | Bisa null jika sistem |
| user_name | VARCHAR(100) | Snapshot nama saat log |
| user_role | VARCHAR(50) | Snapshot role saat log (Spatie role name) |
| action | VARCHAR(50) | create/update/delete/login/logout/approve/reject/transfer/pos |
| module | VARCHAR(50) | gudang.po / jihans.pos / hendhys.produksi / dll |
| model_type | VARCHAR(100) NULL | App\Models\Gudang\PurchaseOrder |
| model_id | BIGINT UNSIGNED NULL | |
| description | TEXT | Deskripsi human-readable |
| old_data | JSON NULL | Data sebelum perubahan |
| new_data | JSON NULL | Data setelah perubahan |
| ip_address | VARCHAR(45) NULL | |
| created_at | TIMESTAMP | (tidak ada updated_at — log immutable) |

---

### `master_notifications`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| type | VARCHAR(50) | transfer_request / branch_request / low_stock / po_received / dll |
| from_entity | ENUM('gudang','jihans','hendhys','system') | |
| to_entity | ENUM('gudang','jihans','hendhys','owner','all') | |
| to_role | VARCHAR(50) NULL | Target role spesifik (Spatie role name) |
| to_user_id | BIGINT UNSIGNED NULL | Target user spesifik |
| reference_type | VARCHAR(100) NULL | Model class |
| reference_id | BIGINT UNSIGNED NULL | ID record terkait |
| title | VARCHAR(200) | |
| message | TEXT | |
| is_read | TINYINT(1) DEFAULT 0 | |
| read_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |

---

## ═══════════════════════════════════
## GUDANG TABLES (10 Tabel)
## ═══════════════════════════════════

### `gudang_stock`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| product_id | BIGINT UNSIGNED FK → master_products UNIQUE | |
| quantity | DECIMAL(15,3) DEFAULT 0 | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| last_updated | TIMESTAMP | |

---

### `gudang_stock_movements`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| type | ENUM('in','out') | |
| source | ENUM('purchase_receiving','transfer_out','adjustment') | |
| reference_id | BIGINT UNSIGNED NULL | ID dari tabel sumber |
| quantity | DECIMAL(15,3) | Selalu positif |
| quantity_before | DECIMAL(15,3) | Stok gudang sebelum |
| quantity_after | DECIMAL(15,3) | Stok gudang sesudah |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users NULL | |
| created_at | TIMESTAMP | |

---

### `gudang_purchase_orders`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| po_number | VARCHAR(30) UNIQUE | Auto: GDG-PO-20260001 |
| supplier_id | BIGINT UNSIGNED FK → master_suppliers | |
| date | DATE | Tanggal PO |
| expected_date | DATE NULL | Estimasi tiba |
| status | ENUM('draft','sent','partial','received','cancelled') DEFAULT 'draft' | |
| total_amount | DECIMAL(15,2) DEFAULT 0 | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| updated_by | BIGINT UNSIGNED FK → master_users NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `gudang_po_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| po_id | BIGINT UNSIGNED FK → gudang_purchase_orders | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| quantity_ordered | DECIMAL(15,3) | |
| quantity_received | DECIMAL(15,3) DEFAULT 0 | Update saat GRN |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| price | DECIMAL(15,2) | Harga per unit |
| total | DECIMAL(15,2) | quantity_ordered × price |
| notes | TEXT NULL | |

---

### `gudang_receivings`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| grn_number | VARCHAR(30) UNIQUE | Auto: GDG-GRN-20260001 |
| po_id | BIGINT UNSIGNED FK → gudang_purchase_orders NULL | Bisa tanpa PO |
| supplier_id | BIGINT UNSIGNED FK → master_suppliers | |
| date | DATE | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `gudang_receiving_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| receiving_id | BIGINT UNSIGNED FK → gudang_receivings | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| quantity | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| hpp_price | DECIMAL(15,2) | Harga beli aktual — disinkron ke master_products.hpp |
| total | DECIMAL(15,2) | |
| notes | TEXT NULL | |

---

### `gudang_transfer_requests`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| request_number | VARCHAR(30) UNIQUE | Auto: GDG-TRQ-20260001 |
| from_entity | ENUM('jihans','hendhys') | Siapa yang request |
| branch_id | BIGINT UNSIGNED FK → master_branches NULL | Jika dari Hendhys cabang |
| date | DATE | |
| needed_date | DATE NULL | Kapan dibutuhkan |
| status | ENUM('pending','approved','partial','rejected','completed') DEFAULT 'pending' | |
| notes | TEXT NULL | |
| rejection_reason | TEXT NULL | |
| requested_by | BIGINT UNSIGNED FK → master_users | |
| approved_by | BIGINT UNSIGNED FK → master_users NULL | |
| approved_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `gudang_transfer_request_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| request_id | BIGINT UNSIGNED FK → gudang_transfer_requests | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| quantity_requested | DECIMAL(15,3) | |
| quantity_approved | DECIMAL(15,3) NULL | Diisi saat approve |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| notes | TEXT NULL | |

---

### `gudang_transfer_out`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transfer_number | VARCHAR(30) UNIQUE | Auto: GDG-TRF-20260001 |
| request_id | BIGINT UNSIGNED FK → gudang_transfer_requests NULL | |
| to_entity | ENUM('jihans','hendhys') | |
| branch_id | BIGINT UNSIGNED FK → master_branches NULL | Tujuan cabang |
| date | DATE | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `gudang_transfer_out_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transfer_id | BIGINT UNSIGNED FK → gudang_transfer_out | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| quantity | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| hpp_price | DECIMAL(15,2) | Snapshot HPP saat transfer |
| total | DECIMAL(15,2) | |

---

## ═══════════════════════════════════
## JIHANS TABLES (10 Tabel)
## ═══════════════════════════════════

### `jihans_stock`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| product_id | BIGINT UNSIGNED FK → master_products UNIQUE | |
| quantity | DECIMAL(15,3) DEFAULT 0 | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| last_updated | TIMESTAMP | |

---

### `jihans_stock_movements`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| type | ENUM('in','out') | |
| source | ENUM('transfer_gudang','production','pos_sale','adjustment') | |
| reference_id | BIGINT UNSIGNED NULL | ID dari tabel sumber |
| quantity | DECIMAL(15,3) | Selalu positif |
| quantity_before | DECIMAL(15,3) | Stok sebelum |
| quantity_after | DECIMAL(15,3) | Stok sesudah |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users NULL | |
| created_at | TIMESTAMP | |

---

### `jihans_stock_in` *(Penerimaan dari Gudang)*
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| stock_in_number | VARCHAR(30) UNIQUE | Auto: JHS-STI-20260001 |
| transfer_out_id | BIGINT UNSIGNED FK → gudang_transfer_out | Referensi transfer keluar gudang |
| date | DATE | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |

---

### `jihans_stock_in_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| stock_in_id | BIGINT UNSIGNED FK → jihans_stock_in | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| quantity | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| hpp_price | DECIMAL(15,2) | Snapshot HPP dari gudang_transfer_out_details |
| notes | TEXT NULL | |

---

### `jihans_productions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| production_number | VARCHAR(30) UNIQUE | Auto: JHS-PRD-20260001 |
| date | DATE | |
| product_id | BIGINT UNSIGNED FK → master_products | Produk Tortilla |
| size | ENUM('kecil','sedang','besar') | |
| quantity_produced | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `jihans_transactions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transaction_number | VARCHAR(30) UNIQUE | Auto: JHS-TRX-20260001 |
| date | DATE | |
| time | TIME | |
| customer_id | BIGINT UNSIGNED FK → master_customers NULL | |
| customer_name | VARCHAR(150) NULL | Walk-in / snapshot |
| customer_type | ENUM('retail','agen') DEFAULT 'retail' | |
| ppn_type | ENUM('none','include','exclude') DEFAULT 'none' | |
| ppn_rate | DECIMAL(5,2) DEFAULT 11.00 | |
| subtotal | DECIMAL(15,2) DEFAULT 0 | Sebelum diskon & pajak |
| discount_amount | DECIMAL(15,2) DEFAULT 0 | |
| tax_amount | DECIMAL(15,2) DEFAULT 0 | |
| other_costs | DECIMAL(15,2) DEFAULT 0 | |
| grand_total | DECIMAL(15,2) DEFAULT 0 | |
| status | ENUM('paid','pending','cancelled') DEFAULT 'paid' | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `jihans_transaction_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transaction_id | BIGINT UNSIGNED FK → jihans_transactions | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| product_name | VARCHAR(200) | Snapshot nama produk |
| quantity | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| price | DECIMAL(15,2) | Harga saat transaksi |
| discount_percent | DECIMAL(5,2) DEFAULT 0 | |
| discount_amount | DECIMAL(15,2) DEFAULT 0 | |
| total | DECIMAL(15,2) | |

---

### `jihans_transaction_payments`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transaction_id | BIGINT UNSIGNED FK → jihans_transactions | |
| payment_method | ENUM('cash','transfer') | |
| amount | DECIMAL(15,2) | |
| reference_number | VARCHAR(100) NULL | No. referensi transfer |
| bank_name | VARCHAR(100) NULL | |
| notes | TEXT NULL | |
| created_at | TIMESTAMP | |

---

### `jihans_pending_transactions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| pending_number | VARCHAR(30) UNIQUE | Auto: JHS-PND-20260001 |
| date | DATE | |
| customer_id | BIGINT UNSIGNED FK → master_customers NULL | |
| customer_name | VARCHAR(150) NULL | |
| customer_type | ENUM('retail','agen') DEFAULT 'retail' | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `jihans_pending_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| pending_id | BIGINT UNSIGNED FK → jihans_pending_transactions | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| product_name | VARCHAR(200) | |
| quantity | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| price | DECIMAL(15,2) | |
| discount_percent | DECIMAL(5,2) DEFAULT 0 | |
| total | DECIMAL(15,2) | |

---

## ═══════════════════════════════════
## HENDHYS TABLES (16 Tabel)
## ═══════════════════════════════════

### `hendhys_stock_pusat`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| product_id | BIGINT UNSIGNED FK → master_products UNIQUE | |
| quantity | DECIMAL(15,3) DEFAULT 0 | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| last_updated | TIMESTAMP | |

---

### `hendhys_stock_branch`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| branch_id | BIGINT UNSIGNED FK → master_branches | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| quantity | DECIMAL(15,3) DEFAULT 0 | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| last_updated | TIMESTAMP | |

INDEX: UNIQUE(branch_id, product_id)

---

### `hendhys_stock_movements`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| branch_id | BIGINT UNSIGNED FK → master_branches NULL | NULL = pusat |
| product_id | BIGINT UNSIGNED FK → master_products | |
| type | ENUM('in','out') | |
| source | ENUM('transfer_gudang','production','transfer_to_branch','return_from_branch','pos_sale','adjustment') | |
| reference_id | BIGINT UNSIGNED NULL | |
| quantity | DECIMAL(15,3) | |
| quantity_before | DECIMAL(15,3) | |
| quantity_after | DECIMAL(15,3) | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users NULL | |
| created_at | TIMESTAMP | |

---

### `hendhys_stock_in` *(Penerimaan dari Gudang)*
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| stock_in_number | VARCHAR(30) UNIQUE | Auto: HND-STI-20260001 |
| transfer_out_id | BIGINT UNSIGNED FK → gudang_transfer_out | |
| branch_id | BIGINT UNSIGNED FK → master_branches NULL | NULL = pusat |
| date | DATE | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |

---

### `hendhys_productions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| production_number | VARCHAR(30) UNIQUE | Auto: HND-PRD-20260001 |
| date | DATE | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `hendhys_production_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| production_id | BIGINT UNSIGNED FK → hendhys_productions | |
| product_id | BIGINT UNSIGNED FK → master_products | Bolu, Roti, Brownies, dll |
| quantity_produced | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| notes | TEXT NULL | |

---

### `hendhys_branch_requests` *(Request Cabang ke Pusat)*
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| request_number | VARCHAR(30) UNIQUE | Auto: HND-BRQ-20260001 |
| branch_id | BIGINT UNSIGNED FK → master_branches | Cabang yang request |
| date | DATE | |
| needed_date | DATE NULL | |
| status | ENUM('pending','approved','partial','rejected','completed') DEFAULT 'pending' | |
| notes | TEXT NULL | |
| rejection_reason | TEXT NULL | |
| requested_by | BIGINT UNSIGNED FK → master_users | |
| approved_by | BIGINT UNSIGNED FK → master_users NULL | |
| approved_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `hendhys_branch_request_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| request_id | BIGINT UNSIGNED FK → hendhys_branch_requests | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| quantity_requested | DECIMAL(15,3) | |
| quantity_approved | DECIMAL(15,3) NULL | |
| unit_id | BIGINT UNSIGNED FK → master_units | |

---

### `hendhys_transfer_to_branch` *(Distribusi Pusat → Cabang)*
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transfer_number | VARCHAR(30) UNIQUE | Auto: HND-TRF-20260001 |
| request_id | BIGINT UNSIGNED FK → hendhys_branch_requests NULL | |
| branch_id | BIGINT UNSIGNED FK → master_branches | Cabang tujuan |
| date | DATE | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `hendhys_transfer_to_branch_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transfer_id | BIGINT UNSIGNED FK → hendhys_transfer_to_branch | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| quantity | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |

---

### `hendhys_returns_from_branch` *(Retur Produk Cacat)*
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| return_number | VARCHAR(30) UNIQUE | Auto: HND-RTN-20260001 |
| branch_id | BIGINT UNSIGNED FK → master_branches | Cabang pengirim retur |
| date | DATE | |
| reason | TEXT NULL | Alasan retur |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `hendhys_return_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| return_id | BIGINT UNSIGNED FK → hendhys_returns_from_branch | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| quantity | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| condition | VARCHAR(100) NULL | "Hancur", "Expired", dll |
| notes | TEXT NULL | |

---

### `hendhys_transactions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transaction_number | VARCHAR(30) UNIQUE | Auto: HND-TRX-20260001 |
| branch_id | BIGINT UNSIGNED FK → master_branches | Pusat atau cabang |
| date | DATE | |
| time | TIME | |
| customer_id | BIGINT UNSIGNED FK → master_customers NULL | |
| customer_name | VARCHAR(150) NULL | Walk-in / snapshot |
| customer_type | ENUM('retail','agen') DEFAULT 'retail' | |
| ppn_type | ENUM('none','include','exclude') DEFAULT 'none' | |
| ppn_rate | DECIMAL(5,2) DEFAULT 11.00 | |
| subtotal | DECIMAL(15,2) DEFAULT 0 | |
| discount_amount | DECIMAL(15,2) DEFAULT 0 | |
| tax_amount | DECIMAL(15,2) DEFAULT 0 | |
| other_costs | DECIMAL(15,2) DEFAULT 0 | |
| grand_total | DECIMAL(15,2) DEFAULT 0 | |
| status | ENUM('paid','pending','cancelled') DEFAULT 'paid' | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `hendhys_transaction_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transaction_id | BIGINT UNSIGNED FK → hendhys_transactions | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| product_name | VARCHAR(200) | Snapshot |
| quantity | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| price | DECIMAL(15,2) | |
| discount_percent | DECIMAL(5,2) DEFAULT 0 | |
| discount_amount | DECIMAL(15,2) DEFAULT 0 | |
| total | DECIMAL(15,2) | |

---

### `hendhys_transaction_payments`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| transaction_id | BIGINT UNSIGNED FK → hendhys_transactions | |
| payment_method | ENUM('cash','transfer') | |
| amount | DECIMAL(15,2) | |
| reference_number | VARCHAR(100) NULL | |
| bank_name | VARCHAR(100) NULL | |
| notes | TEXT NULL | |
| created_at | TIMESTAMP | |

---

### `hendhys_pending_transactions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| pending_number | VARCHAR(30) UNIQUE | Auto: HND-PND-20260001 |
| branch_id | BIGINT UNSIGNED FK → master_branches | |
| date | DATE | |
| customer_id | BIGINT UNSIGNED FK → master_customers NULL | |
| customer_name | VARCHAR(150) NULL | |
| customer_type | ENUM('retail','agen') DEFAULT 'retail' | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK → master_users | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

---

### `hendhys_pending_details`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| pending_id | BIGINT UNSIGNED FK → hendhys_pending_transactions | |
| product_id | BIGINT UNSIGNED FK → master_products | |
| product_name | VARCHAR(200) | |
| quantity | DECIMAL(15,3) | |
| unit_id | BIGINT UNSIGNED FK → master_units | |
| price | DECIMAL(15,2) | |
| discount_percent | DECIMAL(5,2) DEFAULT 0 | |
| total | DECIMAL(15,2) | |

---

## RINGKASAN TABEL

| Prefix | Jumlah Tabel | Catatan |
|---|---|---|
| `master_` | 10 | Roles & permissions via Spatie (tidak dihitung) |
| `gudang_` | 10 | +gudang_stock_movements |
| `jihans_` | 10 | +jihans_stock_in_details |
| `hendhys_` | 16 | -hendhys_order_to_gudang (redundant) |
| **TOTAL** | **46** | |

*Spatie menambah 4 tabel otomatis: `roles`, `permissions`, `model_has_roles`, `role_has_permissions`.*

---

## NOMOR DOKUMEN AUTO-GENERATE

| Tabel | Format | Contoh |
|---|---|---|
| gudang_purchase_orders | GDG-PO-YYYY#### | GDG-PO-20260001 |
| gudang_receivings | GDG-GRN-YYYY#### | GDG-GRN-20260001 |
| gudang_transfer_requests | GDG-TRQ-YYYY#### | GDG-TRQ-20260001 |
| gudang_transfer_out | GDG-TRF-YYYY#### | GDG-TRF-20260001 |
| jihans_stock_in | JHS-STI-YYYY#### | JHS-STI-20260001 |
| jihans_productions | JHS-PRD-YYYY#### | JHS-PRD-20260001 |
| jihans_transactions | JHS-TRX-YYYY#### | JHS-TRX-20260001 |
| jihans_pending_transactions | JHS-PND-YYYY#### | JHS-PND-20260001 |
| hendhys_stock_in | HND-STI-YYYY#### | HND-STI-20260001 |
| hendhys_productions | HND-PRD-YYYY#### | HND-PRD-20260001 |
| hendhys_branch_requests | HND-BRQ-YYYY#### | HND-BRQ-20260001 |
| hendhys_transfer_to_branch | HND-TRF-YYYY#### | HND-TRF-20260001 |
| hendhys_returns_from_branch | HND-RTN-YYYY#### | HND-RTN-20260001 |
| hendhys_transactions | HND-TRX-YYYY#### | HND-TRX-20260001 |
| hendhys_pending_transactions | HND-PND-YYYY#### | HND-PND-20260001 |
| master_suppliers | SUP-#### | SUP-0001 |
| master_customers | CST-#### | CST-0001 |
| master_products | PRD-#### | PRD-0001 |

---

*Dokumen ini adalah blueprint database. Selanjutnya: Laravel Project Structure & Migration Files.*
