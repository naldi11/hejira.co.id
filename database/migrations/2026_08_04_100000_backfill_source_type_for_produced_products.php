<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Tandai produk yang memang diproduksi sendiri sebagai `source_type = 'produced'`.
 *
 * Migrasi 2026_05_23_062305 menambahkan kolom `source_type` dengan default
 * 'purchased' tetapi tidak pernah mengisi ulang data lama. Akibatnya SELURUH
 * produk lama tertandai "beli dari supplier", padahal Hendhys memproduksi
 * ratusan di antaranya. Form Produksi & Prediksi Produksi menyaring tepat kolom
 * itu, sehingga dropdown produknya kosong sama sekali — fitur tidak bisa dipakai.
 *
 * Sumber kebenarannya adalah riwayat produksi nyata (`hendhys_production_details`),
 * bukan tebakan berdasarkan kategori. Kategori tidak bisa dipakai karena 92%
 * produk ber-`entity_scope = 'all'` (dipakai bersama Gudang & Jihans), sehingga
 * menandai satu kategori penuh ikut mengubah produk milik unit lain.
 *
 * Ambang MINIMAL 2 KALI produksi dipakai dengan sengaja. Yang hanya 1 kali
 * kemungkinan besar salah input — di antaranya ada "Kotak Jhon" (kemasan) dan
 * beberapa produk beku yang juga rutin diminta dari Gudang.
 *
 * PENTING: `source_type` berlaku global, bukan per unit. Selain memunculkan
 * produk di form produksi, nilai 'produced' juga MEMBLOKIR produk tersebut dari
 * Transfer Request ke Gudang (lihat TransferRequestController). Itulah sebabnya
 * ambangnya konservatif.
 */
return new class extends Migration
{
    private const MIN_PRODUCTION_COUNT = 2;

    private const BACKUP_TABLE = 'source_type_backfill_backup';

    public function up(): void
    {
        if (! Schema::hasTable('hendhys_production_details') || ! Schema::hasTable('master_products')) {
            return;
        }

        // Tabel bantu supaya rollback mengembalikan PERSIS baris yang diubah,
        // bukan menebak ulang lewat kriteria yang bisa berubah seiring waktu.
        Schema::dropIfExists(self::BACKUP_TABLE);
        Schema::create(self::BACKUP_TABLE, function (Blueprint $t) {
            $t->unsignedBigInteger('product_id')->primary();
            $t->string('previous_source_type', 20);
        });

        $ids = DB::table('hendhys_production_details')
            ->select('product_id')
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) >= ?', [self::MIN_PRODUCTION_COUNT])
            ->pluck('product_id');

        if ($ids->isEmpty()) {
            return;
        }

        // Hanya sentuh yang masih 'purchased'; yang sudah benar dibiarkan apa adanya.
        $targets = DB::table('master_products')
            ->whereIn('id', $ids)
            ->where('source_type', 'purchased')
            ->pluck('id');

        if ($targets->isEmpty()) {
            return;
        }

        DB::table(self::BACKUP_TABLE)->insert(
            $targets->map(fn ($id) => ['product_id' => $id, 'previous_source_type' => 'purchased'])->all()
        );

        DB::table('master_products')->whereIn('id', $targets)->update(['source_type' => 'produced']);
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        foreach (DB::table(self::BACKUP_TABLE)->get() as $row) {
            DB::table('master_products')
                ->where('id', $row->product_id)
                ->update(['source_type' => $row->previous_source_type]);
        }

        Schema::dropIfExists(self::BACKUP_TABLE);
    }
};
