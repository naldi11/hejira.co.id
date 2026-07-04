<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTimezoneUtcToWib extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-timezone';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perbaiki timestamp UTC lama menjadi WIB dengan menambahkan 7 jam.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai perbaikan timezone dari UTC ke WIB...');
        
        // Batas waktu sebelum kita melakukan fix config/app.php ke WIB
        // Karena perbaikan dilakukan sekitar jam 18:00 WIB (11:00 UTC),
        // Semua data UTC lama pasti memiliki created_at di bawah jam 12:00 pada tanggal 2026-07-04.
        $threshold = '2026-07-04 12:00:00';

        // 1. master_cashier_shifts
        $this->info('Memperbaiki tabel master_cashier_shifts...');
        DB::statement("UPDATE master_cashier_shifts SET 
            opened_at = DATE_ADD(opened_at, INTERVAL 7 HOUR),
            closed_at = DATE_ADD(closed_at, INTERVAL 7 HOUR),
            created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE created_at < ?", [$threshold]);

        // 2. jihans_transactions
        $this->info('Memperbaiki tabel jihans_transactions...');
        DB::statement("UPDATE jihans_transactions SET 
            created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR),
            date = DATE(DATE_ADD(CONCAT(date, ' ', time), INTERVAL 7 HOUR)),
            time = TIME(DATE_ADD(CONCAT(date, ' ', time), INTERVAL 7 HOUR))
            WHERE created_at < ?", [$threshold]);

        // 3. hendhys_transactions
        $this->info('Memperbaiki tabel hendhys_transactions...');
        DB::statement("UPDATE hendhys_transactions SET 
            created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR),
            date = DATE(DATE_ADD(CONCAT(date, ' ', time), INTERVAL 7 HOUR)),
            time = TIME(DATE_ADD(CONCAT(date, ' ', time), INTERVAL 7 HOUR))
            WHERE created_at < ?", [$threshold]);

        // 4. jihans_retail_stock_movements
        $this->info('Memperbaiki tabel jihans_retail_stock_movements...');
        DB::statement("UPDATE jihans_retail_stock_movements SET 
            created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE created_at < ?", [$threshold]);

        // 5. jihans_gudang_stock_movements
        $this->info('Memperbaiki tabel jihans_gudang_stock_movements...');
        DB::statement("UPDATE jihans_gudang_stock_movements SET 
            created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE created_at < ?", [$threshold]);

        // 6. hendhys_stock_movements
        $this->info('Memperbaiki tabel hendhys_stock_movements...');
        DB::statement("UPDATE hendhys_stock_movements SET 
            created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
            WHERE created_at < ?", [$threshold]);

        $this->info('Perbaikan selesai! Semua data lama berhasil dimajukan 7 jam ke WIB.');
    }
}
