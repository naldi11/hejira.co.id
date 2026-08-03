<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah semua kolom KUANTITAS dari decimal menjadi integer.
 *
 * Kuantitas stok di aplikasi ini selalu bilangan bulat — hanya nilai uang
 * (harga, HPP, total, pajak, diskon) yang boleh desimal. Kolom decimal(15,3)
 * adalah warisan yang terlalu permisif: ia membuka jalan bagi pecahan masuk
 * lewat jalur validasi yang memakai `numeric`, dan memaksa kode PHP menebak-nebak
 * antara (int) yang memotong dan (float) yang menimbulkan galat pembulatan.
 *
 * Aman dijalankan: seluruh 41 kolom kuantitas di database diverifikasi tidak
 * memiliki satu pun nilai pecahan sebelum migrasi ini dibuat, dan nilai
 * tertinggi (15.000) jauh di bawah batas INT.
 *
 * Memakai integer BERTANDA (signed), bukan unsigned: kolom quantity_before pada
 * hendhys_stock_movements memiliki baris historis bernilai -1, dan koreksi stok
 * di masa depan bisa saja menghasilkan nilai negatif sementara.
 */
return new class extends Migration
{
    /**
     * [tabel, kolom, boleh_null, nilai_default, presisi_desimal_untuk_rollback]
     */
    private const COLUMNS = [
        ['gudang_po_details',                 'quantity_ordered',   false, null, [15, 3]],
        ['gudang_po_details',                 'quantity_received',  false, 0,    [15, 3]],
        ['gudang_receiving_details',          'quantity',           false, null, [15, 3]],
        ['gudang_receiving_details',          'quantity_ordered',   true,  null, [15, 3]],
        ['gudang_return_details',             'quantity',           false, null, [15, 3]],
        ['gudang_return_details',             'received_quantity',  true,  null, [15, 3]],
        ['gudang_transfer_out_details',       'quantity',           false, null, [15, 3]],
        ['gudang_transfer_out_details',       'received_quantity',  true,  null, [15, 3]],
        ['gudang_transfer_request_details',   'quantity_requested', false, null, [15, 3]],
        ['gudang_transfer_request_details',   'quantity_approved',  true,  null, [15, 3]],
        ['hendhys_branch_request_details',    'quantity_requested', false, null, [15, 3]],
        ['hendhys_branch_request_details',    'quantity_approved',  true,  null, [15, 3]],
        ['hendhys_pending_details',           'quantity',           false, null, [15, 3]],
        ['hendhys_production_details',        'quantity_produced',  false, null, [15, 3]],
        ['hendhys_return_details',            'quantity',           false, null, [15, 3]],
        ['hendhys_stock_branch',              'quantity',           false, 0,    [15, 3]],
        ['hendhys_stock_branch',              'quantity_return',    false, 0,    [15, 3]],
        ['hendhys_stock_movements',           'quantity',           false, null, [15, 3]],
        ['hendhys_stock_movements',           'quantity_before',    false, null, [15, 3]],
        ['hendhys_stock_movements',           'quantity_after',     false, null, [15, 3]],
        ['hendhys_stock_pusat',               'quantity',           false, 0,    [15, 3]],
        ['hendhys_stock_pusat',               'quantity_return',    false, 0,    [15, 3]],
        ['hendhys_transaction_details',       'quantity',           false, null, [15, 3]],
        ['hendhys_transfer_to_branch_details','quantity',           false, null, [15, 3]],
        ['hendhys_transfer_to_branch_details','received_quantity',  true,  null, [15, 3]],
        ['jihans_gudang_stock',               'quantity',           false, 0,    [15, 3]],
        ['jihans_gudang_stock_movements',     'quantity',           false, null, [15, 3]],
        ['jihans_gudang_stock_movements',     'quantity_before',    false, null, [15, 3]],
        ['jihans_gudang_stock_movements',     'quantity_after',     false, null, [15, 3]],
        ['jihans_pending_details',            'quantity',           false, null, [15, 3]],
        ['jihans_production_session_details', 'quantity',           false, 0,    [10, 2]],
        ['jihans_productions',                'quantity_produced',  false, null, [15, 3]],
        ['jihans_retail_stock',               'quantity',           false, 0,    [15, 3]],
        ['jihans_retail_stock_in_details',    'quantity',           false, null, [15, 3]],
        ['jihans_retail_stock_movements',     'quantity',           false, null, [15, 3]],
        ['jihans_retail_stock_movements',     'quantity_before',    false, null, [15, 3]],
        ['jihans_retail_stock_movements',     'quantity_after',     false, null, [15, 3]],
        ['jihans_transaction_details',        'quantity',           false, null, [15, 3]],
        ['master_product_tiered_prices',      'min_qty',            false, null, [15, 3]],
        ['receipt_confirmation_details',      'expected_qty',       false, null, [15, 2]],
        ['receipt_confirmation_details',      'actual_qty',         false, null, [15, 2]],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as [$table, $column, $nullable, $default, $_]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($column, $nullable, $default) {
                $col = $t->integer($column)->nullable($nullable);
                if ($default !== null) {
                    $col->default($default);
                }
                $col->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as [$table, $column, $nullable, $default, $decimal]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($column, $nullable, $default, $decimal) {
                $col = $t->decimal($column, $decimal[0], $decimal[1])->nullable($nullable);
                if ($default !== null) {
                    $col->default($default);
                }
                $col->change();
            });
        }
    }
};
