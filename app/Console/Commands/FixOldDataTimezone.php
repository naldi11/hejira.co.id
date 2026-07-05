<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOldDataTimezone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:revert-timezone-shift';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memundurkan waktu -7 jam dan -14 jam pada data lama untuk beradaptasi dengan konfigurasi timezone database +07:00 yang baru secara presisi.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai perbaikan timezone tingkat lanjut...');
        
        // Batas waktu: Eksekusi script dilakukan pada 2026-07-05 15:36:00 UTC (22:36 WIB)
        // Kita gunakan 2026-07-05 16:00:00 sebagai safety threshold UTC
        $threshold = '2026-07-05 16:00:00';

        // 1. master_cashier_shifts (Kelebihan 7 jam karena murni salah simpan string)
        $this->info('Memperbaiki tabel master_cashier_shifts (-7 jam)...');
        DB::statement("UPDATE master_cashier_shifts SET 
            opened_at = DATE_SUB(opened_at, INTERVAL 7 HOUR),
            closed_at = DATE_SUB(closed_at, INTERVAL 7 HOUR),
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE created_at <= ?", [$threshold]);

        // 2. jihans_transaction_payments & hendhys_transaction_payments (Kelebihan 7 jam)
        $this->info('Memperbaiki tabel payments (-7 jam)...');
        DB::statement("UPDATE jihans_transaction_payments SET 
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE created_at <= ?", [$threshold]);
            
        DB::statement("UPDATE hendhys_transaction_payments SET 
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE created_at <= ?", [$threshold]);

        // 3. jihans_transactions & hendhys_transactions (Kelebihan 14 jam karena ditambah oleh app:undo-double-timezone)
        $this->info('Memperbaiki tabel transactions (-14 jam)...');
        DB::statement("UPDATE jihans_transactions SET 
            created_at = DATE_SUB(created_at, INTERVAL 14 HOUR),
            updated_at = DATE_SUB(updated_at, INTERVAL 14 HOUR),
            date = DATE(DATE_SUB(CONCAT(date, ' ', time), INTERVAL 14 HOUR)),
            time = TIME(DATE_SUB(CONCAT(date, ' ', time), INTERVAL 14 HOUR))
            WHERE created_at <= DATE_ADD(?, INTERVAL 7 HOUR)", [$threshold]); // threshold disesuaikan karena data trx sudah +7 jam dari raw

        DB::statement("UPDATE hendhys_transactions SET 
            created_at = DATE_SUB(created_at, INTERVAL 14 HOUR),
            updated_at = DATE_SUB(updated_at, INTERVAL 14 HOUR),
            date = DATE(DATE_SUB(CONCAT(date, ' ', time), INTERVAL 14 HOUR)),
            time = TIME(DATE_SUB(CONCAT(date, ' ', time), INTERVAL 14 HOUR))
            WHERE created_at <= DATE_ADD(?, INTERVAL 7 HOUR)", [$threshold]);

        // 4. Stock Movements (Kelebihan 14 jam karena ditambah oleh app:fix-timezone lama dan tidak di-undo)
        $this->info('Memperbaiki tabel stock movements (-14 jam)...');
        DB::statement("UPDATE jihans_retail_stock_movements SET 
            created_at = DATE_SUB(created_at, INTERVAL 14 HOUR)
            WHERE created_at <= DATE_ADD(?, INTERVAL 7 HOUR)", [$threshold]);

        DB::statement("UPDATE jihans_gudang_stock_movements SET 
            created_at = DATE_SUB(created_at, INTERVAL 14 HOUR)
            WHERE created_at <= DATE_ADD(?, INTERVAL 7 HOUR)", [$threshold]);

        DB::statement("UPDATE hendhys_stock_movements SET 
            created_at = DATE_SUB(created_at, INTERVAL 14 HOUR)
            WHERE created_at <= DATE_ADD(?, INTERVAL 7 HOUR)", [$threshold]);

        $this->info('Perbaikan selesai! Semua jam transaksi dan laci kasir sekarang 100% presisi dengan dunia nyata.');
    }
}
