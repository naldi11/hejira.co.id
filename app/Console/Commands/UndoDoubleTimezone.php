<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UndoDoubleTimezone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:undo-double-timezone';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Undo the double +7 hours timezone update for shifts and transactions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai undo timezone ganda...');

        // 1. master_cashier_shifts
        // Kurangi 7 jam khusus untuk shift yang terdampak double-update 
        // (yang diciptakan sebelum proses script awal kita jalankan, yaitu hari ini jam 19:00)
        $this->info('Memperbaiki tabel master_cashier_shifts (undo 7 jam)...');
        DB::statement("UPDATE master_cashier_shifts SET 
            opened_at = DATE_SUB(opened_at, INTERVAL 7 HOUR),
            closed_at = DATE_SUB(closed_at, INTERVAL 7 HOUR),
            created_at = DATE_SUB(created_at, INTERVAL 7 HOUR),
            updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR)
            WHERE created_at < '2026-07-04 19:00:00'");

        // 2. jihans_transactions
        // Sangat aman: sinkronisasi langsung dengan tabel pembayaran yang tidak pernah tersentuh
        $this->info('Memperbaiki tabel jihans_transactions...');
        DB::statement("UPDATE jihans_transactions t 
            JOIN jihans_transaction_payments p ON t.id = p.transaction_id
            SET t.created_at = DATE_ADD(p.created_at, INTERVAL 7 HOUR),
                t.updated_at = DATE_ADD(p.updated_at, INTERVAL 7 HOUR),
                t.date = DATE(DATE_ADD(p.created_at, INTERVAL 7 HOUR)),
                t.time = TIME(DATE_ADD(p.created_at, INTERVAL 7 HOUR))");

        // 3. hendhys_transactions
        // Sangat aman: sinkronisasi langsung dengan tabel pembayaran
        $this->info('Memperbaiki tabel hendhys_transactions...');
        DB::statement("UPDATE hendhys_transactions t 
            JOIN hendhys_transaction_payments p ON t.id = p.transaction_id
            SET t.created_at = DATE_ADD(p.created_at, INTERVAL 7 HOUR),
                t.updated_at = DATE_ADD(p.updated_at, INTERVAL 7 HOUR),
                t.date = DATE(DATE_ADD(p.created_at, INTERVAL 7 HOUR)),
                t.time = TIME(DATE_ADD(p.created_at, INTERVAL 7 HOUR))");

        $this->info('Perbaikan selesai! Waktu double-update berhasil dinormalkan.');
    }
}
