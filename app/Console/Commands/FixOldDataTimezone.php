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
    protected $description = 'Memundurkan waktu -7 jam pada data lama untuk beradaptasi dengan konfigurasi timezone database +07:00 yang baru.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai perbaikan timezone (memundurkan 7 jam pada data lama)...');
        
        // Batas waktu sebelum kita melakukan fix config/database.php ke +07:00 (dalam UTC)
        // Perbaikan dilakukan pada 2026-07-05 sekitar 15:30 UTC
        $threshold = '2026-07-05 15:30:00';

        // 1. master_cashier_shifts
        $this->info('Memperbaiki tabel master_cashier_shifts...');
        DB::statement("UPDATE master_cashier_shifts SET 
            opened_at = DATE_SUB(opened_at, INTERVAL 7 HOUR),
            closed_at = DATE_SUB(closed_at, INTERVAL 7 HOUR),
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE created_at <= ?", [$threshold]);

        // 2. jihans_transactions
        $this->info('Memperbaiki tabel jihans_transactions...');
        DB::statement("UPDATE jihans_transactions SET 
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR),
            date = DATE(DATE_SUB(CONCAT(date, ' ', time), INTERVAL 7 HOUR)),
            time = TIME(DATE_SUB(CONCAT(date, ' ', time), INTERVAL 7 HOUR))
            WHERE created_at <= ?", [$threshold]);

        // 3. hendhys_transactions
        $this->info('Memperbaiki tabel hendhys_transactions...');
        DB::statement("UPDATE hendhys_transactions SET 
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR),
            date = DATE(DATE_SUB(CONCAT(date, ' ', time), INTERVAL 7 HOUR)),
            time = TIME(DATE_SUB(CONCAT(date, ' ', time), INTERVAL 7 HOUR))
            WHERE created_at <= ?", [$threshold]);

        // 4. jihans_retail_stock_movements
        $this->info('Memperbaiki tabel jihans_retail_stock_movements...');
        DB::statement("UPDATE jihans_retail_stock_movements SET 
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR)
            WHERE created_at <= ?", [$threshold]);

        // 5. jihans_gudang_stock_movements
        $this->info('Memperbaiki tabel jihans_gudang_stock_movements...');
        DB::statement("UPDATE jihans_gudang_stock_movements SET 
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR)
            WHERE created_at <= ?", [$threshold]);

        // 6. hendhys_stock_movements
        $this->info('Memperbaiki tabel hendhys_stock_movements...');
        DB::statement("UPDATE hendhys_stock_movements SET 
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR)
            WHERE created_at <= ?", [$threshold]);

        $this->info('Perbaikan selesai! Semua data lama berhasil dikurangi 7 jam dan kembali normal.');
    }
}
